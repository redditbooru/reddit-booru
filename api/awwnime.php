<?php

namespace Lib {
	
	use stdClass;
	
	define('HISTOGRAM_BUCKETS', 4);
	define('HISTORGAM_GRANULARITY', 256 / HISTOGRAM_BUCKETS);
	
	if (!defined('__INCLUDE__')) {
		define('__INCLUDE__', './');
	}
	
	class Awwnime {
		
		/**
		 * Loads a file, determines the image type by scanning the header, and returns a GD object
		 * @param string $file Path to the file to load
		 * @return object Object containing the GD image and the mimeType, null on failure
		 */
		public static function loadImage($file) {

			$retVal = null;
			
			$type = self::getImageType($file);
			
			if (false !== $type) {
				$retVal = new stdClass;
				$retVal->mimeType = $type;
				switch ($type) {
					case 'image/jpeg':
						$retVal->image = imagecreatefromjpeg($file);
						break;
					case 'image/png':
						$retVal->image = imagecreatefrompng($file);
						break;
					case 'image/gif':
						$retVal->image = imagecreatefromgif($file);
						break;
					default:
						$retVal = null;
				}
			}
			
			return $retVal;
			
		}
		
		/**
		 * Given a URL, downloads and saves the output. Does some special case processing depending on where the image is hosted
		 * @param string $url URL to download
		 * @param string $fileName File path to save to
		 * @return bool Whether the image was downloaded successfully
		 */
		public static function downloadImage($url, $fileName) {

			$retVal = false;
			
			$url = self::_parseUrl($url);
			if ($url) {
				$file = self::curl_get_contents($url);
				if (null != self::_getImageType($file)) {
					$handle = fopen($fileName, 'wb');
					if ($handle) {
						fwrite($handle, $file);
						fclose($handle);
						$retVal = true;
					}
				}
			}

			return $retVal;

		}
		
		/**
		 * Gets a list of image URLs from an imgur album
		 */
		public static function getImgurAlbum($id) {
			$data = self::curl_get_contents('http://api.imgur.com/2/album/' . $id . '.json');
			$retVal = null;
			if (strlen($data) > 0) {
				$data = json_decode($data);
				if (isset($data->album) && isset($data->album->images)) {
					$retVal = array();
					foreach ($data->album->images as $image) {
						$retVal[] = $image->links->original;
					}
				}
			}
			return $retVal;
		}
		
		/**
		 * Processes an image from a post
		 */
		public static function processImage($id, $url) {
			
			$fileName = __INCLUDE__ . 'cache/' . $id;
			if (self::downloadImage($url, $fileName)) {
				
				// Attach the correct file extension
				$type = self::getImageType($fileName);
				$ext = $type == 'image/jpeg' ? 'jpg' : ($type == 'image/gif' ? 'gif' : 'png');
				rename($fileName, $fileName . '.' . $ext);
				$fileName .= '.' . $ext;
				
				// Histogram and dump to the database
				$hist = self::generateHistogram($fileName);
				$params = array( ':id' => $id, ':url' => $url );
				$cols = '';
				$vals = '';
				for ($i = 0; $i < HISTOGRAM_BUCKETS; $i++) {
					$params[':red' . $i] = $hist->red[$i];
					$params[':green' . $i] = $hist->green[$i];
					$params[':blue' . $i] = $hist->blue[$i];
					$cols .= ', image_hist_r' . ($i + 1);
					$cols .= ', image_hist_g' . ($i + 1);
					$cols .= ', image_hist_b' . ($i + 1);
					$vals .= ', :red' . $i;
					$vals .= ', :green' . $i;
					$vals .= ', :blue' . $i;
				}
				
				$query = 'INSERT INTO images (post_id, image_url' . $cols . ') VALUES (:id, :url' . $vals . ')';
				$id = Db::Query($query, $params);
				
				// Rename and upload to amazon
				if ($id) {
					$newFile = base_convert($id, 10, 36) . '.' . $ext;
					rename($fileName, __INCLUDE__ . 'cache/' . $newFile);
					if (AWS_ENABLED) {
						$s3 = new \S3(AWS_KEY, AWS_SECRET);
						$data = $s3->inputFile(__INCLUDE__ . 'cache/' . base_convert($id, 10, 36) . '.' . $ext);
						$s3->putObject($data, 'cdn.awwni.me', $newFile, \S3::ACL_PUBLIC_READ);
					}
				}
				
			}
			
		}
		
		/**
		 * Generates a simplified histogram from the provided image
		 */
		public static function generateHistogram($file) {
			
			$retVal = null;
			$img = self::loadImage($file);
			
			if (null != $img && $img->image) {
				
				$img = $img->image;
				$resampled = imagecreatetruecolor(256, 256);
				imagecopyresampled($resampled, $img, 0, 0, 0, 0, 256, 256, imagesx($img), imagesy($img));
				imagedestroy($img);
			
				$width = imagesx($resampled);
				$height = imagesy($resampled);
				$total = $width * $height;
				$red = array(0, 0, 0, 0);
				$green = array(0, 0, 0, 0);
				$blue = array(0, 0, 0, 0);
				for ($x = 0; $x < $width; $x++) {
					for ($y = 0; $y < $height; $y++) {
						$c = imagecolorat($resampled, $x, $y);
						$red[floor(($c >> 16) / HISTORGAM_GRANULARITY)]++;
						$green[floor(($c >> 8 & 0xff) / HISTORGAM_GRANULARITY)]++;
						$blue[floor(($c & 0xff) / HISTORGAM_GRANULARITY)]++;
					}
				}
				imagedestroy($resampled);
				
				$retVal = new stdClass;
				$retVal->red = array();
				$retVal->green = array();
				$retVal->blue = array();
				for ($i = 0; $i < HISTOGRAM_BUCKETS; $i++) {
					$retVal->red[] = $red[$i] / $total;
					$retVal->green[] = $green[$i] / $total;
					$retVal->blue[] = $blue[$i] / $total;
				}
				
			}
			
			return $retVal;

		}
		
		/**
		 * Given an image file, finds similar images in the database
		 * @param string $file Path or URL to the file to check against
		 * @param int $limit Number of matches to return
		 * @return array Array of matched posts, null on error
		 */
		public static function findSimilarImages($file, $limit = 5) {
			
			$limit = !is_int($limit) ? 5 : $limit;
			$retVal = null;
			
			$histogram = self::generateHistogram($file);
			if (null !== $histogram) {
				
				$query = '';
				$params = array();
				for ($i = 1; $i <= HISTOGRAM_BUCKETS; $i++) {
					$params[':red' . $i] = $histogram->red[$i - 1];
					$params[':green' . $i] = $histogram->green[$i - 1];
					$params[':blue' . $i] = $histogram->blue[$i - 1];
					$query .= 'ABS(i.image_hist_r' . $i . ' - :red' . $i . ') + ABS(i.image_hist_g' . $i . ' - :green' . $i . ') + ABS(i.image_hist_b' . $i . ' - :blue' . $i . ') + ';
				}
				
				// Find the top five most similar images in the database
				$result = Db::Query('SELECT i.image_id, i.post_id, p.post_title, i.image_url, p.post_date, ' . $query . '0 AS distance FROM images i INNER JOIN posts p ON p.post_id = i.post_id ORDER BY distance LIMIT ' . $limit, $params);
				if ($result) {
				
					$retVal = array();
					while($row = Db::Fetch($result)) {
						$obj = new stdClass;
						$obj->image_id = $row->image_id;
						$obj->id = $row->post_id;
						$obj->title = $row->post_title;
						$obj->url = self::_parseUrl($row->image_url);
						$obj->similarity = abs(100 - (100 * ($row->distance / 12)));
						$obj->date = $row->post_date;
						$retVal[] = $obj;
					}
					
				}
			
			}
			
			return $retVal;
		
		}
		
		/**
		 * Gets a page listing from the awwnime subreddit
		 */
		public static function getAwwnimePage($pageNum = 0, $afterId = null) {
			
			$pageNum *= 25;
			$retVal = self::curl_get_contents('http://www.reddit.com/r/awwnime/.json?count=' . $pageNum . '&after=' . $afterId);
			if (strlen($retVal) > 0) {
				$retVal = json_decode($retVal);
				if (isset($retVal->data)) {
					$retVal = $retVal->data;
				}
			}
			
			return $retVal;
			
		}
		
		/**
		 * Generates keywords from the provided text
		 */
		public static function getKeywords($text) {
			$stop = '/\b(a|able|about|above|abroad|according|accordingly|across|actually|adj|after|afterwards|again|against|ago|ahead|ain\'t|all|allow|allows|almost|alone|along|alongside|already|also|although|always|am|amid|amidst|among|amongst|an|and|another|any|anybody|anyhow|anyone|anything|anyway|anyways|anywhere|apart|appear|appreciate|appropriate|are|aren\'t|around|as|a\'s|aside|ask|asking|associated|at|available|away|awfully|back|backward|backwards|be|became|because|become|becomes|becoming|been|before|beforehand|begin|behind|being|believe|below|beside|besides|best|better|between|beyond|both|brief|but|by|came|can|cannot|cant|can\'t|caption|cause|causes|certain|certainly|changes|clearly|c\'mon|co|co.|com|come|comes|concerning|consequently|consider|considering|contain|containing|contains|corresponding|could|couldn\'t|course|c\'s|currently|dare|daren\'t|definitely|described|despite|did|didn\'t|different|directly|do|does|doesn\'t|doing|done|don\'t|down|downwards|during|each|edu|eg|eight|eighty|either|else|elsewhere|end|ending|enough|entirely|especially|et|etc|even|ever|evermore|every|everybody|everyone|everything|everywhere|ex|exactly|example|except|fairly|far|farther|few|fewer|fifth|first|five|followed|following|follows|for|forever|former|formerly|forth|forward|found|four|from|further|furthermore|get|gets|getting|given|gives|go|goes|going|gone|got|gotten|greetings|had|hadn\'t|half|happens|hardly|has|hasn\'t|have|haven\'t|having|he|he\'d|he\'ll|hello|help|hence|her|here|hereafter|hereby|herein|here\'s|hereupon|hers|herself|he\'s|hi|him|himself|his|hither|hopefully|how|howbeit|however|hundred|i\'d|ie|if|ignored|i\'ll|i\'m|immediate|in|inasmuch|inc|inc.|indeed|indicate|indicated|indicates|inner|inside|insofar|instead|into|inward|is|isn\'t|it|it\'d|it\'ll|its|it\'s|itself|i\'ve|just|keep|keeps|kept|know|known|knows|last|lately|later|latter|latterly|least|less|lest|let|let\'s|like|liked|likely|likewise|little|look|looking|looks|low|lower|ltd|made|mainly|make|makes|many|may|maybe|mayn\'t|me|mean|meantime|meanwhile|merely|might|mightn\'t|mine|minus|miss|more|moreover|most|mostly|mr|mrs|much|must|mustn\'t|my|myself|name|namely|nd|near|nearly|necessary|need|needn\'t|needs|neither|never|neverf|neverless|nevertheless|new|next|nine|ninety|nobody|non|none|nonetheless|noone|no-one|nor|normally|not|nothing|notwithstanding|novel|now|nowhere|obviously|of|off|often|oh|ok|okay|old|once|one|ones|one\'s|only|onto|opposite|or|other|others|otherwise|ought|oughtn\'t|our|ours|ourselves|out|outside|over|overall|own|particular|particularly|past|per|perhaps|placed|please|plus|possible|presumably|probably|provided|provides|que|quite|qv|rather|rd|re|really|reasonably|recent|recently|regarding|regardless|regards|relatively|respectively|right|round|said|same|saw|say|saying|says|second|secondly|see|seeing|seem|seemed|seeming|seems|seen|self|selves|sensible|sent|serious|seriously|seven|several|shall|shan\'t|she|she\'d|she\'ll|she\'s|should|shouldn\'t|since|six|some|somebody|someday|somehow|someone|something|sometime|sometimes|somewhat|somewhere|soon|sorry|specified|specify|specifying|still|sub|such|sup|sure|take|taken|taking|tell|tends|th|than|thank|thanks|thanx|that|that\'ll|thats|that\'s|that\'ve|the|their|theirs|them|themselves|then|thence|there|thereafter|thereby|there\'d|therefore|therein|there\'ll|there\'re|theres|there\'s|thereupon|there\'ve|these|they|they\'d|they\'ll|they\'re|they\'ve|thing|things|think|third|thirty|this|thorough|thoroughly|those|though|three|through|throughout|thru|thus|till|together|too|took|toward|towards|tried|tries|truly|try|trying|t\'s|twice|two|un|under|underneath|undoing|unfortunately|unless|unlike|unlikely|until|unto|up|upon|upwards|us|use|used|useful|uses|using|usually|v|value|various|versus|very|via|viz|vs|want|wants|was|wasn\'t|way|we|we\'d|welcome|well|we\'ll|went|were|we\'re|weren\'t|we\'ve|what|whatever|what\'ll|what\'s|what\'ve|when|whence|whenever|where|whereafter|whereas|whereby|wherein|where\'s|whereupon|wherever|whether|which|whichever|while|whilst|whither|who|who\'d|whoever|whole|who\'ll|whom|whomever|who\'s|whose|why|will|willing|wish|with|within|without|wonder|won\'t|would|wouldn\'t|yes|yet|you|you\'d|you\'ll|your|you\'re|yours|yourself|yourselves|s|you\'ve|zero)\b/i';
			$retVal = strtolower($text);
			
			// Remove special characters, punctuation, and stop words
			$retVal = htmlspecialchars_decode($retVal);
			$retVal = str_replace(array ('.', '>', '<', '\'', '|', '[', ']', '(', ')', '{', '}', '!', '@', '#', '$', '%', '^', '&', '*', '?', '"', ':', ',', '_'), ' ', $retVal);
			$retVal = str_replace ('  ', ' ', $retVal);
			$retVal = preg_replace($stop, '', $retVal);
			
			// Remove duplicate words, clean up spaces
			$temp = explode(' ', $retVal);
			$retVal = array();
			for ($i = 0, $count = count($temp); $i < $count; $i++) {
				$temp[$i] = trim($temp[$i]);
				if (strlen($temp[$i]) > 1) {
					$place = true;
					for ($j = 0, $c = count($retVal); $j < $c; $j++) {
						if ($retVal[$j] == $temp[$i]) {
							$place = false;
							break;
						}
					}
					if ($place) {
						$retVal[] = $temp[$i];
					}
				}
			}
			
			return implode(' ', $retVal);
			
		}
		
		/**
		 * Given a file, returns the image mime type
		 */
		public static function getImageType($fileName) {
			$retVal = null;
			if (is_readable($fileName)) {
				$handle = fopen($fileName, 'rb');
				$head = fread($handle, 10);
				$retVal = self::_getImageType($head);
				fclose($handle);
			}
			return $retVal;
		}
		
		/**
		 * Determines the image type of the incoming data
		 * @param string $data Data of the image file to determine
		 * @return string Mime type of the image, null if not recognized
		 */
		private static function _getImageType($data) {
		
			$retVal = null;
			if (ord($data{0}) == 0xff && ord($data{1}) == 0xd8) {
				$retVal = 'image/jpeg';
			} else if (ord($data{0}) == 0x89 && substr($data, 1, 3) == 'PNG') {
				$retVal = 'image/png';
			} else if (substr($data, 0, 6) == 'GIF89a' || substr($data, 0, 6) == 'GIF87a') {
				$retVal = 'image/gif';
			}
			
			return $retVal;
		
		}
		
		private static function _parseUrl($url) {
			
			$urlInfo = parse_url($url);
			
			if ($urlInfo !== false) {
				// Handle deviantArt submissions
				if (strpos($url, 'deviantart.com') !== false) {
					$info = json_decode(self::curl_get_contents('http://backend.deviantart.com/oembed?url=' . urlencode($url)));
					if (is_object($info)) {
						$url = $info->url;
					}
				
				// Handle imgur images that didn't link directly to the image
				} elseif ($urlInfo['host'] == 'imgur.com' && strpos($urlInfo['path'], '.') === false) {
					$url .= '.jpg';
				}
			}
			
			return $url;
		
		}
		
		/**
		 * A drop in replacement for file_get_contents. Changes the user-agent to make reddit happy
		 * @param string $url Url to retrieve
		 * @return string Data received
		 */
		private static function curl_get_contents($url) {
			$c = curl_init($url);
			curl_setopt($c, CURLOPT_USERAGENT, 'moe downloader by /u/dxprog');
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_TIMEOUT, 5);
			return curl_exec($c);
		}
	
	}

}