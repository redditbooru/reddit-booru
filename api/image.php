<?php

namespace Api {

    use Aws\S3\S3Client;
    use ImgHasher;
    use Lib;
    use stdClass;

    define('HISTOGRAM_BUCKETS', 4);
    define('HISTORGAM_GRANULARITY', 256 / HISTOGRAM_BUCKETS);

    // Events
    define('IMGEVT_PROCESSING', 'IMGEVT_PROCESSING');
    define('IMGEVT_UPLOAD_BEGIN', 'IMGEVT_UPLOADING');
    define('IMGEVT_UPLOAD_COMPLETE', 'IMGEVT_UPLOAD_COMPLETE');
    define('IMGEVT_UPLOAD_FAILED', 'IMGEVT_UPLOAD_FAILED');

    define('IMGERR_INVALID_FORMAT', 'IMGERR_INVALID_FORMAT');
    define('IMGERR_DOWNLOAD_FAILURE', 'IMGERR_DOWNLOAD_FAILURE');

    define('IMG_RESAMPLE_WIDTH', 256);
    define('IMG_RESAMPLE_HEIGHT', 256);

    if (!defined('__INCLUDE__')) {
        define('__INCLUDE__', (strlen($_SERVER['DOCUMENT_ROOT']) > 0 ? $_SERVER['DOCUMENT_ROOT'] : getcwd()) . '/');
    }

    class Image extends Lib\Dal {

        /**
         * Object property to table map
         */
        protected $_dbMap = array(
            'id' => 'image_id',
            'url' => 'image_url',
            'caption' => 'image_caption',
            'sourceUrl' => 'image_source',
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
            'dHashR' => 'image_dhashr',
            'dHashG' => 'image_dhashg',
            'dHashB' => 'image_dhashb',
            'type' => 'image_type'
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
         * Image ID
         */
        public $id = 0;

        /**
         * URL of image
         */
        public $url;

        /**
         * Image caption
         */
        public $caption;

        /**
         * Source URL
         */
        public $sourceUrl;

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
         * dHash of the image (for the future)
         */
        public $dHashR;
        public $dHashG;
        public $dHashB;

        /**
         * Image type
         */
        public $type;

        /**
         * Creates an image object and its histogram from a URL
         */
        public static function createFromUrl($url) {

            $retVal = null;

            // See if there's a version of this image already in the database
            $image = Image::query([ 'url' => $url ]);
            if ($image && $image->count) {
                $retVal = new Image(Lib\Db::Fetch($image));
            } else {

                $image = Lib\ImageLoader::fetchImage($url);
                if (null !== $image) {
                    $retVal = self::createFromBuffer($image->data);
                    if (null !== $retVal) {
                        $retVal->url = $url;
                        $retVal->type = $image->type;
                        unset($image);
                    } else {
                        $retVal = null;
                    }
                }

            }

            return $retVal;

        }

        /**
         * Given the image data buffer, returns an Image object with histogram information
         */
        public static function createFromBuffer($buffer) {

            $retVal = null;

            Lib\Events::fire(IMGEVT_PROCESSING);
            $image = imagecreatefromstring($buffer);

            if (null !== $image) {

                $startTime = microtime(true);

                $retVal = new Image();
                $retVal->width = imagesx($image);
                $retVal->height = imagesy($image);

                // Do the dHash as well
                $hash = ImgHasher\CalcHash::dHashRGB($image);
                $retVal->dHashR = $hash['r'];
                $retVal->dHashG = $hash['g'];
                $retVal->dHashB = $hash['b'];

                $resampled = imagecreatetruecolor(IMG_RESAMPLE_WIDTH, IMG_RESAMPLE_HEIGHT);
                imagecopyresampled($resampled, $image, 0, 0, 0, 0, IMG_RESAMPLE_WIDTH, IMG_RESAMPLE_HEIGHT, $retVal->width, $retVal->height);
                imagedestroy($image);

                $total = IMG_RESAMPLE_WIDTH * IMG_RESAMPLE_HEIGHT;
                $red = [ 0, 0, 0, 0 ];
                $green = [ 0, 0, 0, 0 ];
                $blue = [ 0, 0, 0, 0 ];
                for ($x = 0; $x < IMG_RESAMPLE_WIDTH; $x++) {
                    for ($y = 0; $y < IMG_RESAMPLE_HEIGHT; $y++) {
                        $c = imagecolorat($resampled, $x, $y);
                        $red[floor(($c >> 16) / HISTORGAM_GRANULARITY)]++;
                        $green[floor(($c >> 8 & 0xff) / HISTORGAM_GRANULARITY)]++;
                        $blue[floor(($c & 0xff) / HISTORGAM_GRANULARITY)]++;
                    }
                }

                imagedestroy($resampled);

                for ($i = 0; $i < HISTOGRAM_BUCKETS; $i++) {
                    $prop = 'histR' . ($i + 1);
                    $retVal->$prop = $red[$i] / $total;

                    $prop = 'histG' . ($i + 1);
                    $retVal->$prop = $green[$i] / $total;

                    $prop = 'histB' . ($i + 1);
                    $retVal->$prop = $blue[$i] / $total;
                }

                Lib\Ga::sendEvent('image', 'process', null, round((microtime(true) - $startTime) * 1000));

                Tracking::trackEvent('process_image', [
                    'width' => $retVal->width,
                    'height' => $retVal->height,
                    'loadTime' => microtime(true) - $startTime
                ]);

            }

            return $retVal;

        }

        /**
         * Takes an ID and type and generates the current CDN filename
         */
        public static function generateFilename($id, $type, $fullUrl = true) {
            return ($fullUrl ? CDN_BASE_URL : '') . base_convert($id, 10, 36) . '.' . $type;
        }

        public function getFilename($fullUrl = true) {
            return self::generateFilename($this->id, $this->type, $fullUrl);
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
         * Saves an image to all the various dumping grounds
         */
        public function _saveImage() {

            $retVal = false;

            if ($this->id && $this->url && $this->type) {

                // Fetch the image. Since we're at this point in the show, there should
                // be data in the mongo cache and performance hit will be minimal
                $image = Lib\ImageLoader::fetchImage($this->url);
                if ($image) {
                    $fileName = $this->getFilename(false);
                    $localPath = LOCAL_IMAGE_PATH . $fileName;

                    if (file_put_contents($localPath, $image->data)) {

                        // Upload to AWS
                        if (AWS_ENABLED) {
                            $s3 = S3Client::factory([
                                'key' => AWS_KEY,
                                'secret' => AWS_SECRET
                            ]);

                            // Figure out the MIME type
                            $mime = '';
                            switch ($this->type) {
                                case IMAGE_TYPE_JPEG:
                                    $mime = 'image/jpeg';
                                    break;
                                case IMAGE_TYPE_PNG:
                                    $mime = 'image/png';
                                    break;
                                case IMAGE_TYPE_GIF:
                                    $mime = 'image/gif';
                                    break;
                            }

                            if ($s3) {
                                $result = $s3->putObject([
                                    'Body' => $image->data,
                                    'Bucket' => AWS_BUCKET,
                                    'CacheControl' => 'max-age=' . AWS_EXPIRATION,
                                    'ACL' => 'public-read',
                                    'Key' => AWS_PATH . $fileName,
                                    'Expires' => time() + AWS_EXPIRATION,
                                    'ContentType' => $mime
                                ]);
                                if ($result) {
                                    $retVal = true;
                                }
                            }
                        }

                    }
                }

            }

            return $retVal;

        }

        /**
         * Syncs this object to the database and saves local and backup copies
         * @override
         */
        public function sync($forceInsert = false) {
            $isInsert = $this->id === null || $this->id === 0;
            $retVal = parent::sync($forceInsert);

            // On successful image insert, save copies
            if ($retVal && $isInsert) {
                $this->_saveImage();
            }

            return $retVal;
        }

    }

}