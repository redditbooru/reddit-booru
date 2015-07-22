<?php

define('RED_EOL', PHP_EOL . PHP_EOL);
define('RB_DB', 'reddit-booru');

function getSingleRecord($query, $params = null) {
    $retVal = false;
    $result = Db::Query($query, $params);
    if ($result) {
        $retVal = Db::Fetch($result);
    }
    return $retVal;
}

function createHeader($title) {
    return '| **' . $title . '**' . PHP_EOL . '----' . RED_EOL;
}

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
    $result = Db::Query('SELECT post_id, post_keywords FROM `' . RB_DB . '`.posts WHERE source_id = :sourceId AND post_date BETWEEN :dateStart AND :dateEnd AND post_visible = 1', [ ':sourceId' => $sourceId, ':dateStart' => $dateStart, ':dateEnd' => $dateEnd ]);
    while ($row = Db::Fetch($result)) {
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

function getKeywordsScore($keywords, $dateLimit, $count, $sourceId) {

    $retVal = array();

    foreach($keywords as $keyword => $total) {
        $dictOkay = true;
        if ($keyword !== 'original' && strpos($keyword, ' ') === false) {
            $dictionary = getSingleRecord('SELECT COUNT(1) AS total FROM awwnime.dictionary WHERE word = "' . $keyword . '"');
            $dictOkay = $dictionary->total == 0;
        }

        if ($dictOkay) {
            $params = [ ':sourceId' => $sourceId ];
            $query = 'SELECT SUM(post_score) AS total FROM `' . RB_DB . '`.posts WHERE source_id = :sourceId AND post_visible = 1 AND ' . $dateLimit . ' AND post_title REGEXP "[^a-zA-Z0-9]' . $keyword . '[^a-zA-Z0-9]"';

            // Exclude any items where this set of keywords might be within another set of keywords
            foreach ($keywords as $phrase => $total) {
                if ($phrase !== $keyword && strpos($phrase, $keyword) !== false) {
                    $query .= 'AND post_title NOT REGEXP "[^a-zA-Z0-9]' . $phrase . '[^a-zA-Z0-9]"';
                }
            }
            $score = getSingleRecord($query, $params);

            $retVal[$keyword] = (int)$score->total;
        }
    }

    arsort($retVal);

    return array_slice($retVal, 0, $count);

}

function getKeywordsAverage($keywords, $dateLimit, $averagePosts, $count, $sourceId) {

    $retVal = array();
    $averagePosts = round($averagePosts);
    foreach($keywords as $keyword => $total) {

        // Don't dictionary check multiple words or "original"
        $dictOkay = true;
        if ($keyword !== 'original' && strpos($keyword, ' ') === false) {
            $dictionary = getSingleRecord('SELECT COUNT(1) AS total FROM awwnime.dictionary WHERE word = "' . $keyword . '"');
            $dictOkay = $dictionary->total == 0;
        }

        if ($dictOkay) {
            $params = [ ':sourceId' => $sourceId ];
            $query = 'SELECT SUM(post_score) AS total, COUNT(1) AS count FROM `' . RB_DB . '`.posts WHERE source_id = :sourceId AND post_visible = 1 AND ' . $dateLimit . ' AND post_title REGEXP "[^a-zA-Z0-9]' . $keyword . '[^a-zA-Z0-9]"';

            // Exclude any items where this set of keywords might be within another set of keywords
            foreach ($keywords as $phrase => $total) {
                if ($phrase !== $keyword && strpos($phrase, $keyword) !== false) {
                    $query .= 'AND post_title NOT REGEXP "[^a-zA-Z0-9]' . $phrase . '[^a-zA-Z0-9]"';
                }
            }
            $query .= ' HAVING count >= ' . $averagePosts;
            $score = getSingleRecord($query, $params);
            if ($score) {
                $retVal[$keyword] = new stdClass;
                $retVal[$keyword]->count = $score->count;
                $retVal[$keyword]->total = $score->total;
            }
        }
    }

    uasort($retVal, function($a, $b) {
        return ($a->count > 0 ? $a->total / $a->count : 0) > ($b->count > 0 ? $b->total / $b->count : 0) ? -1 : 1;
    });

    return array_slice($retVal, 0, $count);

}

function getMonthStats($sourceId, $sourceName, $dateStart, $dateEnd) {

    $dateLimit = 'post_date BETWEEN ' . $dateStart . ' AND ' . $dateEnd . ' AND post_visible = 1 AND source_id = ' . $sourceId;

    // Get the number of posts and number of posters
    $postCount = getSingleRecord('SELECT COUNT(1) AS total FROM `' . RB_DB . '`.posts WHERE ' . $dateLimit);
    $posterCount = getSingleRecord('SELECT COUNT(DISTINCT user_id) AS total FROM `' . RB_DB . '`.posts WHERE ' . $dateLimit);
    $averagePosts = false;
    if ($postCount && $posterCount) {
        $averagePosts = $postCount->total / $posterCount->total;
    }
    $upvotes = getSingleRecord('SELECT SUM(post_score) AS total FROM `' . RB_DB . '`.posts WHERE ' . $dateLimit);

    // User stuff
    $mostPosts = getSingleRecord('SELECT COUNT(1) AS total, u.user_name AS post_poster FROM `' . RB_DB . '`.posts p INNER JOIN `' . RB_DB . '`.users u ON u.user_id = p.user_id WHERE ' . $dateLimit . ' GROUP BY p.user_id ORDER BY total DESC LIMIT 1');
    if ($averagePosts) {
        $highestAverge = getSingleRecord('SELECT COUNT(1) AS total, AVG(post_score) AS average, u.user_name AS post_poster FROM `' . RB_DB . '`.posts p INNER JOIN `' . RB_DB . '`.users u ON u.user_id = p.user_id WHERE ' . $dateLimit . ' GROUP BY p.user_id HAVING total >= ' . $averagePosts . ' ORDER BY average DESC LIMIT 1');
    }

    // Get count of new users
    $newUsers = getSingleRecord('SELECT COUNT(DISTINCT user_id) AS total FROM `' . RB_DB . '`.posts WHERE user_id NOT IN (SELECT DISTINCT user_id FROM `' . RB_DB . '`.posts WHERE user_id IS NOT NULL AND post_date < :dateStart AND source_id = :sourceId) AND post_date >= :dateStart AND source_id = :sourceId AND post_visible = 1', [ ':dateStart' => $dateStart, ':sourceId' => $sourceId ]);

    $post = 'Hi, everybody!' . RED_EOL;
    $post .= 'My name is Ai and I\'m an advanced artificial intelligence here to make your stay here on ' . $sourceName . ' more fun \\^o\\^! It\'s time again for my montly statistics report, so for the month of ' . date('F', $dateStart) . ', here\'s what happened!' . RED_EOL;

    if ($averagePosts) {
        $post .= createHeader('Generic numbers for the month');
        $post .= 'This month, there were **' . $postCount->total . '** posts posted by **' . $posterCount->total . '** people. This means that for each user who posted, they did so an average of **' . round($averagePosts) . '** times.' . RED_EOL;
    }

    if ($newUsers) {
        $post .= 'We had **' . $newUsers->total . '** new users post this month. Let me be the first (or second?) to say "Welcome!" :D' . RED_EOL;
    }

    if ($upvotes) {
        $post .= 'As of me posting this, the combined scores of all posts for the month was **' . $upvotes->total . '**';
        if ($averagePosts) {
            $post .= ' for an average score of **' . round($upvotes->total / $postCount->total) . '** per submission';
        }
        $post .= '.' . RED_EOL;
    }

    $post .= createHeader('Top posts');
    $result = Db::Query('SELECT p.*, u.user_name AS post_poster FROM `' . RB_DB . '`.posts p INNER JOIN `' . RB_DB . '`.users u ON u.user_id = p.user_id WHERE ' . $dateLimit . ' ORDER BY post_score DESC LIMIT 3');
    $topPosts = '';
    $places = [ 'And, the coveted spot of First Place goes to', 'Second', 'Third' ];
    $i = 0;
    while ($row = Db::Fetch($result)) {
        $topPosts = '**' . $places[$i] . '**' . RED_EOL . '[' . $row->post_title . '](http://www.reddit.com/' . $sourceName . '/comments/' . $row->post_external_id . ') by [' . $row->post_poster . '](http://www.reddit.com/u/' . $row->post_poster . ') with a score of **' . $row->post_score . '**' . RED_EOL . $topPosts;
        $i++;
    }
    $post .= $topPosts;

    $post .= createHeader('User stats');
    $result = Db::Query('SELECT COUNT(1) AS total, u.user_name AS post_poster FROM `' . RB_DB . '`.posts p INNER JOIN `' . RB_DB . '`.users u ON u.user_id = p.user_id WHERE ' . $dateLimit . ' GROUP BY p.user_id ORDER BY total DESC LIMIT 5');
    if ($result && $result->count > 0) {
        $post .= 'The most prolific posters this month were: ' . RED_EOL;
        while ($row = Db::Fetch($result)) {
            $post .= '* [' . $row->post_poster . '](http://www.reddit.com/u/' . $row->post_poster . ') with **' . $row->total . ' posts**' . PHP_EOL;
        }
    }

    if ($averagePosts) {
        $result = Db::Query('SELECT SUM(p.post_score) AS score, u.user_name AS post_poster FROM `' . RB_DB . '`.posts p INNER JOIN `' . RB_DB . '`.users u ON u.user_id = p.user_id WHERE ' . $dateLimit . ' GROUP BY p.user_id ORDER BY score DESC LIMIT 5');
        if ($result) {
            $post .= PHP_EOL . 'The users with the highest scores were: ' . RED_EOL;
            while ($row = Db::Fetch($result)) {
                $post .= '* [' . $row->post_poster . '](http://www.reddit.com/u/' . $row->post_poster . ') with a score of **' . $row->score . '**' . PHP_EOL;
            }
        }
    }

    if ($averagePosts) {
        $result = Db::Query('SELECT COUNT(1) AS total, AVG(post_score) AS score, u.user_name AS post_poster FROM `' . RB_DB . '`.posts p INNER JOIN `' . RB_DB . '`.users u ON u.user_id = p.user_id WHERE ' . $dateLimit . ' GROUP BY p.user_id HAVING total >= ' . $averagePosts . ' ORDER BY score DESC LIMIT 5');
        if ($result) {
            $post .= PHP_EOL . 'And finally, the users with the highest average scores, who posted at or above the average amount of submissions: ' . RED_EOL;
            while ($row = Db::Fetch($result)) {
                $post .= '* [' . $row->post_poster . '](http://www.reddit.com/u/' . $row->post_poster . ') submitted **' . $row->total . '** posts with an average score of **' . round($row->score) . '**' . PHP_EOL;
            }
            $post .= PHP_EOL . 'Let\'s all give a small bow to these people in particular for their quality submissions! \\^_\\^' . RED_EOL;
        }
    }

    // Get keywords
    $keywords = getHighestKeywords($sourceId, $dateStart, $dateEnd);

    if ($keywords) {
        $post .= createHeader('What was posted about') . 'Here\'s a list of what was popular to submit this month in ' . $sourceName . ': ' . RED_EOL;
        $count = 0;
        foreach ($keywords as $phrase => $num) {
            if ($count < 5) {
                $post .= '* **' . $phrase . '** with about ' . $num . ' submissions' . PHP_EOL;
            } else {
                break;
            }
            $count++;
        }

        $post .= PHP_EOL . 'Here\'s what received the highest scores: ' . RED_EOL;
        $scores = getKeywordsScore($keywords, $dateLimit, 5, $sourceId);
        foreach ($scores as $phrase => $num) {
            $post .= '* **' . $phrase . '** with a score of ' . $num . PHP_EOL;
        }

        $post .= PHP_EOL . 'Last, but not least, what averaged the highest score: ' . RED_EOL;
        $scores = getKeywordsAverage($keywords, $dateLimit, $averagePosts, 5, $sourceId);
        foreach ($scores as $phrase => $num) {
            $post .= '* **' . $phrase . '** with a score of about ' . round($num->total / $num->count) . PHP_EOL;
        }

        $post .= PHP_EOL . 'I don\'t entirely understand all of the phrases and shows you guys use, so I apologize if something looks weird. \'_\'*' . RED_EOL . RED_EOL;

    }

    $post .= 'This concludes my report. Ja ne~!';
    return $post;

}

function postCountForUser($user, $topic) {
    $result = Db::Query('SELECT COUNT(1) AS total FROM `' . RB_DB . '`.posts p INNER JOIN `' . RB_DB . '`.users u ON u.user_id = p.user_id WHERE source_id = 1 AND u.user_name = "' . $user . '" AND post_title LIKE "%' . $topic . '%"');
    $retVal = 0;
    if ($result && $result->count > 0) {
        $row = Db::Fetch($result);
        $retVal = $row->total;
    }
    return $retVal;
}

function lastUpdatedPost($lastUpdated, $bot) {

    $year = date('Y', $lastUpdated);
    $month = date('m', $lastUpdated);
    $dateStart = strtotime($year . '/' . $month . '/01');

    if ($month == 12) {
        $dateEnd = strtotime($year + 1 . '/01/01') - 1;
    } else {
        $dateEnd = strtotime($year . '/' . ($month + 1) . '/01') - 1;
    }

    $result = Db::Query('SELECT * FROM `' . RB_DB . '`.sources WHERE source_enabled = 1 AND source_generate_report = 1');
    while ($row = Db::Fetch($result)) {
        echo 'Stats post for ', $row->source_name, PHP_EOL;
        echo '--------------------------------------', PHP_EOL;
        $post = getMonthStats($row->source_id, $row->source_name, $dateStart, $dateEnd);

        $bot->Submit('Ai-tan\'s Statistical Report for the Month of ' . date('F', $dateStart), $post, $row->source_subdomain, 'self');

        // Sleep for two minutes to appease the reddit gods
        sleep(120);
    }

    $bot->Save();

}

function runAiTan($bot) {

    // Check for the once a month post
    $month = date('m');
    $lastMonth = date('m', $bot->lastUpdated);
    if ($month != $lastMonth) {
        lastUpdatedPost($bot->lastUpdated, $bot);
        return 'made monthly post';
    }
    return 'nothing new';
}
