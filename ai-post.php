<?php

/**
 * Self-posting Ai-tan
 * An experiment in reddit and machine learning
 */

require('lib/aal.php');

define('SOURCE_AWWNIME', 1);
define('SOURCE_GELBOORU', 12);
define('KEYWORD_LIMIT', 10);

function mergePhrases($arr, $phrases) {
	foreach ($arr as $key => $val) {
		if ($val > 0) {
			if (isset($phrases[$key])) {
				$phrases[$key] += $val;
			} else {
				$phrases[$key] = $val;
			}
		}
	}
	return $phrases;
}

function getPostData($sourceId, $dateStart, $dateEnd) {
	$retVal = [];
	$result = Lib\Db::Query('SELECT post_id, post_keywords FROM posts WHERE source_id = :sourceId AND post_date BETWEEN :dateStart AND :dateEnd', [ ':sourceId' => $sourceId, ':dateStart' => $dateStart, ':dateEnd' => $dateEnd ]);
	while ($row = Lib\Db::Fetch($result)) {
		$retVal[$row->post_id] = $row->post_keywords;
	}
	return $retVal;
}

function getSimilarPostCount($keyphrase, $postId, &$data) {
	$retVal = 0;
	if (strlen(trim($keyphrase)) > 0) {
		foreach ($data as $id => $keywords) {
			if ($postId != $id && strpos($keywords, $keyphrase) !== false) {
				$retVal++;
			}
		}
	}
	return $retVal;
}

function getHighestKeywords($sourceId, $dateStart, $dateEnd) {
	
	$phrases = [];
	$data = getPostData($sourceId, $dateStart, $dateEnd);
	
	foreach($data as $id => $keywords) {

		$title = explode(' ', trim($keywords));
		$found = 0;
		$words = 1;
		$titleLen = count($title);
		$localPhrases = [];

		for ($i = 0; $i < $titleLen; $i++) {
			$lastPhrase = '';
			$phrase = '';
			for ($j = 0, $wordsLeft = $titleLen - $i; $j < $wordsLeft; $j++) {
				$phrase .= ' ' . trim($title[$i + $j]);
				$phrase = trim($phrase);
				$doCheck = true;

				if ($doCheck) {
					$count = getSimilarPostCount($phrase, $id, $data);
					if ($count > 0 && strlen($phrase) > 2) {
						if (!isset($localPhrases[$phrase])) {
							$localPhrases[$phrase] = 1;
						} else {
							$localPhrases[$phrase]++;
						}
						if (isset($localPhrases[$lastPhrase])) {
							$localPhrases[$lastPhrase]--;
							if ($localPhrases[$lastPhrase] <= 0) {
								unset($localPhrases[$lastPhrase]);
							}
						}
					} else {
						break;
					}
				}
				$lastPhrase = $phrase;
			}
		}

		// Remove duplicate phrase chunks leaving only the longest
		foreach ($localPhrases as $needle => $nCount) {
			foreach ($localPhrases as $haystack => $hCount) {
				if ($needle != $haystack && strpos($haystack, $needle) !== false) {
					$localPhrases[$needle] = 0;
				}
			}
		}

		$phrases = mergePhrases($localPhrases, $phrases);
		
	}

	$phraseCount = count($phrases);
	$phrases = array_filter($phrases, function($a) { return $a > 0; });
	arsort($phrases);
	
	return $phrases;

}

date_default_timezone_set('America/Chicago');
$dateStart = strtotime('2013/04/01');
$dateEnd = strtotime('2013/04/30');

// Get most used keywords/phrases, limit the list
$keywords = getHighestKeywords(SOURCE_AWWNIME, $dateStart, $dateEnd);
$keywords = array_splice($keywords, 0, KEYWORD_LIMIT);
echo 'Using the following keywords: ';
print_r($keywords);

// Get the 100 most recent gelbooru posts that match the keywords
$params = [];
$where = [];
$i = 0;
foreach ($keywords as $keyword => $count) {
	$params[':keyword' . $i] = '%' . $keyword . '%';
	$where[] = 'post_keywords LIKE :keyword' . $i;
	$i++;
}

$posts = [];
$query = 'SELECT post_id, post_keywords FROM posts WHERE source_id = ' . SOURCE_GELBOORU . ' AND (' . implode(' OR ', $where) . ') ORDER BY post_date DESC LIMIT 100';
$result = Lib\Db::Query($query, $params);
while ($row = Lib\Db::Fetch($result)) {

	$rank = 0;
	foreach ($keywords as $keyword => $count) {
		if (strpos($row->post_keywords, $keyword) !== false) {
			// Ranking for the keyword is the number of times it appeared in the time period
			// TODO - make this based off of score instead
			$rank += $count;
		}
	}

	$posts[$row->post_id] = $rank;

}

arsort($posts);

$where = [];
$params = [];
$i = 0;
foreach ($posts as $id => $rank) {
	$params[':id' . $id] = (int) $id;
	$where[] = ':id' . $id;
	$i++;
}

// Get the gelbooru IDs and pull the image data from the gelbooru API
// TODO - use interanally stored histogram data instead of going to API
$images = [];
$query = 'SELECT post_external_id, post_link, post_id FROM posts WHERE post_id IN (' . implode(',', $where) . ')';
$result = Lib\Db::Query($query, $params);
while ($row = Lib\Db::Fetch($result)) {
	$xml = simplexml_load_file('http://gelbooru.com/index.php?page=dapi&s=post&q=index&id=' . $row->post_external_id);
	// Only safe images
	// TODO - make this controlled by the source (e.g. questionable for r/pantsu, explicit for r/ecchi)
	if ($xml->post->attributes()->rating == 's') {
		$image = (string) $xml->post->attributes()->sample_url;
		$repost = Api\Post::reverseImageSearch([ 'imageUri' => $image, 'sources' => SOURCE_AWWNIME ]);
		if (isset($repost->results)) {
			$rank = 0;
			
			// Similarity of 98% or more is considered a repost and will have a rank of 0
			// It should probably be thrown out entirely
			if (round($repost->results[0]->similarity) < 98) {
				foreach ($repost->results as $item) {
					// Rank is the the closeness percentage times the score
					// The idea is, high similarity should give more weight to score, especially if that score is also high
					$rank += $item->similarity / 100 * $item->score;
				}
				$rank /= count($repost->results);
			}
			$out = new stdClass;
			$out->url = $image;
			
			// Add the ranking from above to the ranking from the keywords
			$out->rank = $rank + $params[':id' . $row->post_id];

			$out->keywords = (string) $xml->post->attributes()->tags;
			$out->source = $row->post_link;
			$images[] = $out;
		}
	}
}

usort($images, function($a, $b) {
	return $a->rank < $b->rank ? 1 : -1;
});

// TODO - figure out a better way of making titles. Maybe derivitive off of titles that share the keywords?
// That sounds difficult
for ($i = 0; $i < 3; $i++) {

	$toPost = $images[$i];
	$tags = [];
	foreach ($keywords as $keyword => $rank) {
		if (strpos($toPost->keywords, $keyword) !== false) {
			$tags[] = $keyword;
		}
	}

	$title = 'Ai-tan\'s Automagic Moe Offerings: [' . implode('/', $tags) . ']';
	echo $title, PHP_EOL;
	echo $toPost->url, PHP_EOL;
	echo $toPost->source, PHP_EOL;
	echo '---------------------------------------', PHP_EOL;

}