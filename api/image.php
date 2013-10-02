<?php

namespace Api {
	
	use Lib;
	use stdClass;
	
	define('HISTOGRAM_BUCKETS', 4);
	define('HISTORGAM_GRANULARITY', 256 / HISTOGRAM_BUCKETS);
	define('CDN_FOLDER', '');
    define('CDN_URL_BASE', 'http://cdn.awwni.me/');
    define('LOCAL_STORAGE', '/var/www/redditbooru-images');

    // Events
    define('IMGEVT_DOWNLOAD_BEGIN', 'IMGEVT_DOWNLOAD_BEGIN');
    define('IMGEVT_DOWNLOAD_COMPLETE', 'IMGEVT_DOWNLOAD_COMPLETE');
    define('IMGEVT_DOWNLOAD_ERROR', 'IMGEVT_DOWNLOAD_ERROR');
    define('IMGEVT_PROCESSING', 'IMGEVT_PROCESSING');
    define('IMGEVT_UPLOAD_BEGIN', 'IMGEVT_UPLOADING');
    define('IMGEVT_UPLOAD_COMPLETE', 'IMGEVT_UPLOAD_COMPLETE');
    define('IMGEVT_UPLOAD_FAILED', 'IMGEVT_UPLOAD_FAILED');
	
	if (!defined('__INCLUDE__')) {
		define('__INCLUDE__', (strlen($_SERVER['DOCUMENT_ROOT']) > 0 ? $_SERVER['DOCUMENT_ROOT'] : getcwd()) . '/');
	}
	
	class Image extends Lib\Dal {
		
		/**
		 * Object property to table map
		 */
		protected $_dbMap = array(
			'id' => 'image_id',
			'postId' => 'post_id',
			'sourceId' => 'source_id',
			'url' => 'image_url',
			'cdnUrl' => 'image_cdn_url',
			'width' => 'image_width',
			'height' => 'image_height',
			'histR1' => 'image_hist_r1',
			'histR2' => 'image_hist_r2',
			'histR3' => 'image_hist_r3',
			'histR4' => 'image_hist_r4',
			'histG1' => 'image_hist_g1',
			'histG2' => 'image_hist_g2',
			'histG3' => 'image_hist_g3',
			'histG4' => 'image_hist_g4',
			'histB1' => 'image_hist_b1',
			'histB2' => 'image_hist_b2',
			'histB3' => 'image_hist_b3',
			'histB4' => 'image_hist_b4',
			'isGood' => 'image_good',
            'contentRating' => 'image_rating'
		);
		
		/**
		 * Database table name
		 */
		protected $_dbTable = 'images';
		
		/**
		 * Table primary key
		 */
		protected $_dbPrimaryKey = 'id';
		
		/**
		 * Path to the local version of the file
		 */
		private $localFile;
		
		/**
		 * GD object of the image
		 */
		private $gdImage = null;
		
		/**
		 * Image ID
		 */
		public $id = 0;
		
		/**
		 * Parent post ID
		 */
		public $postId;
		
		/**
		 * Source ID
		 */
		public $sourceId;
		
		/**
		 * URL of image
		 */
		public $url;
		
		/**
		 * Link to the CDN
		 */
		public $cdnUrl;
		
		/**
		 * Width of image
		 */
		public $width;
		
		/**
		 * Height of image
		 */
		public $height;
		
		/**
		 * Red component 1
		 */
		public $histR1;
		
		/**
		 * Red component 2
		 */
		public $histR2;
		
		/**
		 * Red component 3
		 */
		public $histR3;
		
		/**
		 * Red component 4
		 */
		public $histR4;
		
		/**
		 * Green component 1
		 */
		public $histG1;
		
		/**
		 * Green component 2
		 */
		public $histG2;
		
		/**
		 * Green component 3
		 */
		public $histG3;
		
		/**
		 * Green component 4
		 */
		public $histG4;
		
		/**
		 * Blue component 1
		 */
		public $histB1;
		
		/**
		 * Blue component 2
		 */
		public $histB2;
		
		/**
		 * Blue component 3
		 */
		public $histB3;
		
		/**
		 * Blue component 4
		 */
		public $histB4;
		
		/**
		 * Blue component 4
		 */
		public $isGood = false;
        
        /**
         * Content rating
         */
        public $contentRating;
		
		/**
		 * Downloads and syncs an image to the database
		 */
		public static function createFromImage($url, $postId = null, $sourceId = null) {
		
			$localFile = __INCLUDE__ . 'cache/' . $postId . '_' . md5(microtime(true));
			$retVal = null;
			
			if (self::downloadImage($url, $localFile)) {
				
				$retVal = new Image();
				$retVal->localFile = $localFile;
				$retVal->postId = $postId;
				$retVal->sourceId = $sourceId;
				$retVal->url = self::parseUrl($url);
				
				// Attach the correct file extension
				$retVal->gdImage = self::loadImage($retVal->localFile);
				if (null != $retVal->gdImage) {
					
					// Change the extension based on the image type
					$ext = $retVal->gdImage->mimeType == 'image/jpeg' ? 'jpg' : ($retVal->gdImage->mimeType == 'image/gif' ? 'gif' : 'png');
					rename($retVal->localFile, $retVal->localFile . '.' . $ext);
					$retVal->localFile .= '.' . $ext;
					$retVal->isGood = true;
					
					// Save out the image's histogram
					$retVal->generateHistogram();
					$retVal->getImageDimensions();
					
					if (false !== $postId && $retVal->sync()) {
					
						// Rename and upload to Amazon
						if ($retVal->id) {
							Lib\Events::fire(IMGEVT_UPLOAD_BEGIN);
							$newFile = base_convert($retVal->id, 10, 36) . '.' . $ext;
							rename($retVal->localFile, __INCLUDE__ . 'cache/' . $newFile);
							$retVal->localFile = __INCLUDE__ . 'cache/' . $newFile;
							if (LOCAL_STORAGE) {
								copy($retVal->localFile, LOCAL_STORAGE . '/' . $newFile);
							}
							
                            // Upload to the S3 store if it's not there already
                            $retVal->cdnUrl = CDN_URL_BASE . CDN_FOLDER . $newFile;
                            if (strpos(strtolower($url), 'cdn.awwni.me') === false && AWS_ENABLED) {
                                require_once(__INCLUDE__ . 'lib/S3.php');
                                $s3 = new \S3(AWS_KEY, AWS_SECRET);
                                $data = $s3->inputFile(__INCLUDE__ . 'cache/' . base_convert($retVal->id, 10, 36) . '.' . $ext);
                                if (!$s3->putObject($data, 'cdn.awwni.me', CDN_FOLDER . $newFile, \S3::ACL_PUBLIC_READ)) {
                                    $retVal->cdnUrl = null;
                                }
                            }
                            $retVal->sync();
                            Lib\Events::fire(IMGEVT_UPLOAD_COMPLETE);
						}
						
					}
					
					unlink($retVal->localFile);
					imagedestroy($retVal->gdImage->image);
					
				}
				
			}
			
			return $retVal;
		
		}
		
		/**
		 * Gets images by a source or sources
		 */
		public static function getImagesBySource($vars) {

		}
		
		/**
		 * Gets images by a parent ID
		 */
		public static function getImagesByPostId($vars) {
			
			$retVal = null;
			$postId = Lib\Url::GetInt('postId', false, $vars);
			$externalId = Lib\Url::Get('externalId', false, $vars);
			
			if ($postId || $externalId) {
				
				$cacheKey = 'getImagesByPostId_' . $postId . '_' . $externalId;
				$retVal = Lib\Cache::Get($cacheKey);
				
				if (!$retVal) {
					$params = $postId ? [ ':postId' => $postId ] : [ ':postId' => $externalId ];
					$query = $postId ? 'SELECT * FROM images WHERE post_id = :postId' : 
						'SELECT i.* FROM `images` i INNER JOIN `posts` p ON p.post_id = i.post_id WHERE p.post_external_id = :postId';
					$result = Lib\Db::Query($query, $params);
					if (null != $result && $result->count > 0) {
						$retVal = [];
						while ($row = Lib\Db::Fetch($result)) {
							$retVal[] = new Image($row);
						}
					}
					Lib\Cache::Set($cacheKey, $retVal);
				}
			
			}
			
			return $retVal;
			
		}
		
		/**
		 * Gets images by a source or sources
		 */
		public static function getImagesByUser($vars) {
			
			$retVal = null;
			$user = Lib\Url::Get('user', false, $vars);
			
			if ($user) {
				
				$cacheKey = 'getImagesByUser_' . $user;
				$retVal = Lib\Cache::Get($cacheKey);
				
				if (!$retVal) {			
					$params = array( ':user' => $user );
					$result = Lib\Db::Query('SELECT i.* FROM images i INNER JOIN posts p ON i.post_id = p.post_id WHERE p.post_poster = :user AND i.image_good = 1 ORDER BY p.post_date DESC', $params);
					if (null != $result && $result->count > 0) {
						$retVal = [];
						while ($row = Lib\Db::Fetch($result)) {
							$retVal[] = new Image($row);
						}
					}
					Lib\Cache::Set($cacheKey, $retVal);
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

			Lib\Events::fire(IMGEVT_DOWNLOAD_BEGIN);
			
			$url = self::parseUrl($url);
			if ($url) {
				$file = null;
				
				// Account for local files or URLs
				if ($url{0} === '/' && is_readable($url)) {
					$file = file_get_contents($url);
				} else {
					$file = self::curl_get_contents($url);
				}

				if (!$file) {
					Lib\Events::fire(IMGEVT_DOWNLOAD_ERROR, 'Unable to download file');
				} else if (null != self::_getImageType($file)) {
					$handle = fopen($fileName, 'wb');
					if ($handle) {
						fwrite($handle, $file);
						fclose($handle);
						$retVal = true;
						Lib\Events::fire(IMGEVT_DOWNLOAD_COMPLETE);
					}
				} else {
					Lib\Events::fire(IMGEVT_DOWNLOAD_ERROR, 'Invalid image type');
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
		 * Generates a simplified histogram from the provided image
		 */
		public function generateHistogram() {

			Lib\Events::fire(IMGEVT_PROCESSING);
			$retVal = null;
			if (null == $this->gdImage) {
				if (null == $this->localFile) {
					$tmpFile = tempnam(null, null);
					$this->localFile = $tmpFile;
					self::downloadImage($this->url, $tmpFile);
				}
				$this->gdImage = self::loadImage($this->localFile);
			}
			
			if (null != $this->gdImage && $this->gdImage->image) {
				

				$resampled = imagecreatetruecolor(256, 256);
				imagecopyresampled($resampled, $this->gdImage->image, 0, 0, 0, 0, 256, 256, imagesx($this->gdImage->image), imagesy($this->gdImage->image));
			
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
				for ($i = 0; $i < HISTOGRAM_BUCKETS; $i++) {
					$prop = 'histR' . ($i + 1);
					if (property_exists($this, $prop)) {
						$this->$prop = $red[$i] / $total;
					}
					
					$prop = 'histG' . ($i + 1);
					if (property_exists($this, $prop)) {
						$this->$prop = $green[$i] / $total;
					}
					
					$prop = 'histB' . ($i + 1);
					if (property_exists($this, $prop)) {
						$this->$prop = $blue[$i] / $total;
					}
				}
				
			}
			
			// Clean up
			if (isset($tmpFile)) {
				imagedestroy($this->gdImage->image);
				unlink($tmpFile);
				$this->localFile = null;
				$this->gdImage = null;
			}
			
			return $retVal;

		}
		
		/**
		 * Gets the dimensions of the current image
		 */
		public function getImageDimensions() {
			
			if ($this->url) {
				if (null == $this->gdImage) {
					if (null == $this->localFile) {
						$tmpFile = tempnam(null, null);
						$this->localFile = $tmpFile;
						self::downloadImage($this->url, $tmpFile);
					}
					$this->gdImage = self::loadImage($this->localFile);
				}
				
				if (null != $this->gdImage) {
					$this->width = imagesx($this->gdImage->image);
					$this->height = imagesy($this->gdImage->image);
				}
				
				if (isset($tmpFile)) {
					imagedestroy($this->gdImage->image);
					unlink($tmpFile);
					$this->localFile = null;
					$this->gdImage = null;
				}
				
			}
			
		}
		
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
						$retVal->image = @imagecreatefromjpeg($file);
						break;
					case 'image/png':
						$retVal->image = @imagecreatefrompng($file);
						break;
					case 'image/gif':
						$retVal->image = @imagecreatefromgif($file);
						break;
					default:
						$retVal = null;
				}
				
				if (null != $retVal && null == $retVal->image) {
					$retVal = null;
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
						$obj->url = self::parseUrl($row->image_url);
						$obj->similarity = abs(100 - (100 * ($row->distance / 12)));
						$obj->date = $row->post_date;
						$retVal[] = $obj;
					}
					
				}
			
			}
			
			return $retVal;
		
		}
		
		/**
		 * Given a file, returns the image mime type
		 */
		public static function getImageType($fileName) {
			$retVal = null;
			$handle = fopen($fileName, 'rb');
			if ($handle) {
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
		
		/**
		 * Parses an image URL for specific edge cases
		 */
		public static function parseUrl($url) {
			
			$urlInfo = parse_url($url);
			
			// Check for the host part as we don't need to do anything for local files
			if ($urlInfo !== false && isset($urlInfo['host'])) {
				// Handle deviantArt submissions
				if (strpos($url, 'deviantart.com') !== false || strpos($url, 'fav.me') !== false) {
					$info = json_decode(self::curl_get_contents('http://backend.deviantart.com/oembed?url=' . urlencode($url)));
					if (is_object($info)) {
						$url = $info->url;
					}
				
				// Handle imgur images that didn't link directly to the image
				} elseif (strpos($urlInfo['host'], 'imgur.com') !== false && strpos($urlInfo['path'], '.') === false) {
					$url .= '.jpg';
				} elseif (strpos($urlInfo['host'], 'mediacru.sh') !== false && strpos($urlInfo['path'], '.') === false) {
					$info = json_decode(self::curl_get_contents($url . '.json'));
					if (is_object($info)) {
						$url = 'http://mediacru.sh' . $info->original;
					}
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
            
            // Not the most ethical thing, but fake a referer for pixiv to get around the 403
            if (strpos($url, 'pixiv.net')) {
                curl_setopt($c, CURLOPT_REFERER, 'http://pixiv.net');
            }
            
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_TIMEOUT, 15);
			$retVal = curl_exec($c);
			curl_close($c);

			return $retVal;
		}
	
	}

}