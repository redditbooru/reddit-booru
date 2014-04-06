<?php

namespace Api {
    
    use Lib;
    use stdClass;
    
    define('POST_SEARCH_COUNT', 25);
    
    class Post extends Lib\Dal {
    
        /**
         * Object property to table map
         */
        protected $_dbMap = array(
            'id' => 'post_id',
            'sourceId' => 'source_id',
            'externalId' => 'post_external_id',
            'dateCreated' => 'post_date',
            'dateUpdated' => 'post_updated',
            'title' => 'post_title',
            'link' => 'post_link',
            'userId' => 'user_id',
            'keywords' => 'post_keywords',
            'score' => 'post_score',
            'visible' => 'post_visible',
            'nsfw' => 'post_nsfw'
        );
        
        /**
         * Database table name
         */
        protected $_dbTable = 'posts';
        
        /**
         * Table primary key
         */
        protected $_dbPrimaryKey = 'id';
        
        /**
         * ID of the post
         */
        public $id = 0;
        
        /**
         * Post source
         */
        public $sourceId;
        
        /**
         * ID of external entity (reddit post, booru id, etc)
         */
        public $externalId;
        
        /**
         * Date the post was created (unix timestamp)
         */
        public $dateCreated;
        
        /**
         * Date the database record was last updated
         */
        public $dateUpdated;
        
        /**
         * Title of the post
         */
        public $title;
        
        /**
         * Post link
         */
        public $link;
        
        /**
         * Poster
         */
        public $userId;
        
        /**
         * Date the post was created (unix timestamp)
         */
        public $keywords;
        
        /**
         * Post's score/rating
         */
        public $score;

        /**
         * Is this post NSFW
         */
        public $nsfw;
        
        /**
         * Whether this post is visible to the public or not
         */
        public $visible = true;
        
        private function __copy($obj) {
            if ($obj instanceOf Post) {
                $this->id = $obj->id;
                $this->sourceId = $obj->sourceId;
                $this->externalId = $obj->externalId;
                $this->dateCreated = $obj->dateCreated;
                $this->dateUpdated = $obj->dateUpdated;
                $this->title = $obj->title;
                $this->link = $obj->link;
                $this->userId = $obj->userId;
                $this->keywords = $obj->keywords;
                $this->score = $obj->score;
                $this->processed = $obj->processed;
                $this->meta = $obj->meta;
            }
        }
        
        /**
         * Gets a record by its external ID
         */
        public static function getByExternalId($id, $sourceId) {
            
            $retVal = null;
            $params = [ ':externalId' => $id, ':sourceId' => $sourceId ];
            $result = Lib\Db::Query('SELECT * FROM `posts` WHERE post_external_id = :externalId AND source_id = :sourceId', $params);
            if (null != $result && $result->count > 0) {
                $row = Lib\Db::Fetch($result);
                $retVal = new Post($row);
                $retVal->meta = json_decode($retVal->meta);
            }
            return $retVal;
            
        }
        
        /**
         * Returns all unprocessed results of a specific type
         */
        public static function getUnprocessed($sourceId) {
            
            $retVal = null;
            $params = array( ':processed' => false, ':sourceId' => $sourceId );
            $result = Lib\Db::Query('SELECT * FROM `posts` WHERE post_processed = :processed AND source_id = :sourceId ORDER BY post_date DESC', $params);
            if (null != $result && $result->count > 0) {
                $retVal = array();
                while ($row = Lib\Db::Fetch($result)) {
                    $retVal[] = new Post($row);
                }
            }
            return $retVal;
            
        }
        
        /**
         * Searches through posts for stuff
         */
        public static function searchPosts($vars) {
            
            $retVal = null;
            
            $cacheKey = 'Post_searchPosts_' . implode('_', $vars);
            $retVal = Lib\Cache::Get($cacheKey);
            
            if (false === $retVal) {
            
                $id = Lib\Url::GetInt('id', null, $vars);
                $user = Lib\Url::Get('user', false, $vars);
                $getImages = Lib\Url::GetBool('getImages', $vars);
                $getSource = Lib\Url::GetBool('getSource', $vars);
                $sort = Lib\Url::Get('sort', 'desc', $vars);
                $count = Lib\Url::GetInt('count', POST_SEARCH_COUNT, $vars);
                $afterDate = Lib\Url::GetInt('afterDate', null, $vars);
                $externalId = Lib\Url::Get('externalId', null, $vars);
                $keywords = Lib\Url::Get('keywords', null, $vars);
                $ratios = Lib\Url::Get('ratios', null, $vars);
                $sources = Lib\Url::Get('sources', null, $vars);
                
                $params = [];
                
                $query = 'SELECT * FROM `posts` p WHERE ';
                
                if ($id) {
                    $params[':id'] = $id;
                    $query .= 'post_id = :id AND ';
                }
                
                if ($user) {
                    $params[':user'] = $user;
                    $query .= 'post_poster = :user AND ';
                }
                
                if ($afterDate) {
                    $params[':afterDate'] = $afterDate;
                    $opr = $sort == 'asc' ? '>' : '<';
                    $query .= 'post_date ' . $opr . ' :afterDate AND ';
                }
                
                if ($externalId) {
                    $params[':externalId'] = $externalId;
                    $query .= 'post_external_id = :externalId AND ';
                }
                
                if ($keywords) {
                    $params[':keywords'] = '%' . str_replace(' ', '%', $keywords) . '%';
                    $query .= 'post_keywords LIKE :keywords AND ';
                }
                
                if ($sources) {
                    $sources = !is_array($sources) ? explode(',', $sources) : $sources;
                    $tmpList = [];
                    $i = 0;
                    foreach ($sources as $source) {
                        $params[':source' . $i] = $source;
                        $tmpList[] = ':source' . $i;
                        $i++;
                    }
                    $query .= 'source_id IN (' . implode(',', $tmpList) . ') AND ';
                }
                
                if ($ratios) {
                    $ratios = !is_array($ratios) ? explode(',', $ratios) : $ratios;
                    $tmpList = [];
                    $i = 0;
                    foreach ($ratios as $ratio) {
                        $params[':ratio' . $i] = $ratio;
                        $tmpList[] = ':ratio' . $i;
                        $i++;
                    }
                    $query .= '(SELECT COUNT(1) FROM images WHERE post_id = p.post_id AND ROUND(image_width / image_height, 2) IN (' . implode(',', $tmpList) . ')) > 0 AND ';
                }
                
                $sort = $sort == 'asc' ? 'ASC' : 'DESC';
                $query .= 'post_visible = 1 ORDER BY post_date ' . $sort . ' LIMIT ' . $count;
                
                $result = Lib\Db::Query($query, $params);
                if (null != $result && $result->count > 0) {
                    $retVal = [];
                    while ($row = Lib\Db::Fetch($result)) {
                        $obj = new Post($row);
                        if ($getSource) {
                            $obj->source = Source::getById([ 'sourceId' => $obj->sourceId ]);
                        }
                        
                        if ($getImages) {
                            $obj->images = Image::getImagesByPostId([ 'postId' => $obj->id ]);
                            if ($ratios) {
                                
                                $images = [];
                                if (is_array($obj->images) && count($obj->images) > 0) {                            
                                    foreach ($obj->images as $image) {
                                        foreach ($ratios as $ratio) {
                                            if ($image->width > 0 && $image->height > 0 && round($image->width / $image->height, 2) == $ratio) {
                                                $images[] = $image;
                                            }
                                        }
                                    }
                                    
                                }
                                
                                $obj->images = $images;
                                if (count($images) == 0) {
                                    $obj = null;
                                }
                                
                            }
                        }
                        
                        if (null != $obj) {
                            $retVal[] = $obj;
                        }
                        
                    }
                }
                
                Lib\Cache::Set($cacheKey, $retVal);
            
            }
            
            return $retVal;
        
        }
        
        /**
         * Searches for posts by image
         */
        public static function reverseImageSearch($vars) {
        
            $image = Lib\Url::Get('image', null, $vars);
            $file = Lib\Url::Get('imageUri', null, $vars);
            $count = Lib\Url::GetInt('count', 5, $vars);
            $getSource = Lib\Url::GetBool('getSource', $vars);
            $getUser = Lib\Url::GetBool('getUser', $vars);
            $sources = Lib\Url::Get('sources', null, $vars);
            $getCount = Lib\Url::GetBool('getCount', $vars);
            $maxRating = Lib\Url::Get('maxRating', 0, $vars);
            $sourceSearch = false;

            // If an image object was passed in, serialize and hash it for the cache key
            if ($image instanceof Image) {
                $vars['image'] = md5(json_encode($image));
            }

            $cacheKey = 'Post_reverseImageSearch_' . implode('_', $vars);
            $retVal = Lib\Cache::Get($cacheKey);
            
            if ((null != $file || $image instanceof Image) && false === $retVal) {
            
                if (!($image instanceof Image)) {
                    $image = Image::createFromImage($file, false);
                }

                if (null !== $image) {

                    $query = 'SELECT i.image_id, i.image_url, i.image_cdn_url, i.image_width, i.image_height, i.source_id, i.distance';
                    $query .= ', p.post_id, p.post_external_id, p.post_title, p.post_date, p.post_score, p.user_id';
                    if ($getSource) {
                        $query .= ', s.source_name, s.source_baseurl, s.source_content_rating';
                    }
                    
                    if ($getCount) {
                        $query .= ', (SELECT COUNT(1) FROM images WHERE post_id = p.post_id) AS count ';
                    }

                    if ($getUser) {
                        $query .= ', u.user_name';
                    }
                    
                    $params = array();
                    $innerQuery = 'SELECT image_id, image_url, image_cdn_url, image_width, image_height, source_id, post_id, ';
                    for ($i = 1; $i <= HISTOGRAM_BUCKETS; $i++) {
                        $prop = 'histR' . $i;
                        $params[':red' . $i] = $image->$prop;
                        $prop = 'histG' . $i;
                        $params[':green' . $i] = $image->$prop;
                        $prop = 'histB' . $i;
                        $params[':blue' . $i] = $image->$prop;
                        $innerQuery .= 'ABS(image_hist_r' . $i . ' - :red' . $i . ') + ABS(image_hist_g' . $i . ' - :green' . $i . ') + ABS(image_hist_b' . $i . ' - :blue' . $i . ') + ';
                    }
                    
                    $innerQuery .= '0 AS distance FROM images ';
                    if ($sources) {
                        $innerQuery .= 'WHERE ';
                        if ($sources === 'source') {
                            $sources = 12; // To be replaced with a query that get's all booru sources
                            $getSource = false;
                            $sourceSearch = true;
                            $query .= ', p.post_link, p.post_meta ';
                        }
                        $sources = !is_array($sources) ? explode(',', $sources) : $sources;
                        $tmpList = [];
                        $i = 0;
                        foreach ($sources as $source) {
                            $params[':source' . $i] = $source;
                            $tmpList[] = ':source' . $i;
                            $i++;
                        }
                        $innerQuery .= ' source_id IN (' . implode(',', $tmpList) . ')';
                    }
                    $innerQuery .= ' ORDER BY distance LIMIT ' . ($count * 2); 
                    
                    // Find the top five most similar images in the database
                    $query .= ' FROM (' . $innerQuery . ') AS i INNER JOIN posts p ON p.post_id = i.post_id ';
                    if ($getSource) {
                        $query .= 'INNER JOIN sources s ON s.source_id = i.source_id ';
                    }

                    if ($getUser) {
                        $query .= 'INNER JOIN users u ON u.user_id = p.user_id ';
                    }

                    $query .= 'WHERE p.post_visible = 1 ORDER BY i.distance, p.post_date DESC LIMIT ' . $count;
                    $result = Lib\Db::Query($query, $params);
                    
                    $time = time();
                    if ($result) {
                    
                        $retVal = new stdClass;
                        $retVal->original = $file;
                        $retVal->sourceSearch = $sourceSearch;
                        while($row = Lib\Db::Fetch($result)) {
                            $retVal->results[] = $row;
                        }
                        
                    }
                
                }

                Lib\Cache::Set($cacheKey, $retVal);
                
            }
            
            return $retVal;
            
        }
        
        /**
         * Creates an instance of Post from a row returned from the reddit API
         */
        public static function createFromRedditObject($obj) {
            $retVal = new Post();
            $retVal->externalId = $obj->id;
            $retVal->title = $obj->title;
            $retVal->link = $obj->url;
            
            if ($obj->author !== '[deleted]') {
                $userId = User::getByName($obj->author);
                if ($userId) {
                    $retVal->userId = $userId->id;
                }
            } else {
                $retVal->userId = null;
                $retVal->visible = 0;
            }

            $retVal->score = $obj->score;
            $retVal->dateCreated = $obj->created_utc;
            $retVal->dateUpdated = time();
            $retVal->keywords = self::generateKeywords($retVal->title . ' ' . $obj->link_flair_text);
            $retVal->nsfw = $obj->over_18 ? 1 : 0;
            return $retVal;
        }
        
        /**
         * Generates keywords from the provided text
         */
        public static function generateKeywords($text) {
            $stop = '/\b(a|able|about|above|abroad|according|accordingly|across|actually|adj|after|afterwards|again|against|ago|ahead|ain\'t|all|allow|allows|almost|alone|along|alongside|already|also|although|always|am|amid|amidst|among|amongst|an|and|another|any|anybody|anyhow|anyone|anything|anyway|anyways|anywhere|apart|appear|appreciate|appropriate|are|aren\'t|around|as|a\'s|aside|ask|asking|associated|at|available|away|awfully|back|backward|backwards|be|became|because|become|becomes|becoming|been|before|beforehand|begin|behind|being|believe|below|beside|besides|best|better|between|beyond|both|brief|but|by|came|can|cannot|cant|can\'t|caption|cause|causes|certain|certainly|changes|clearly|c\'mon|co|co.|com|come|comes|concerning|consequently|consider|considering|contain|containing|contains|corresponding|could|couldn\'t|course|c\'s|currently|dare|daren\'t|definitely|described|despite|did|didn\'t|different|directly|do|does|doesn\'t|doing|done|don\'t|down|downwards|during|each|edu|eg|eight|eighty|either|else|elsewhere|end|ending|enough|entirely|especially|et|etc|even|ever|evermore|every|everybody|everyone|everything|everywhere|ex|exactly|example|except|fairly|far|farther|few|fewer|fifth|first|five|followed|following|follows|for|forever|former|formerly|forth|forward|found|four|from|further|furthermore|get|gets|getting|given|gives|go|goes|going|gone|got|gotten|greetings|had|hadn\'t|half|happens|hardly|has|hasn\'t|have|haven\'t|having|he|he\'d|he\'ll|hello|help|hence|her|here|hereafter|hereby|herein|here\'s|hereupon|hers|herself|he\'s|hi|him|himself|his|hither|hopefully|how|howbeit|however|hundred|i\'d|ie|if|ignored|i\'ll|i\'m|immediate|in|inasmuch|inc|inc.|indeed|indicate|indicated|indicates|inner|inside|insofar|instead|into|inward|is|isn\'t|it|it\'d|it\'ll|its|it\'s|itself|i\'ve|just|keep|keeps|kept|know|known|knows|last|lately|later|latter|latterly|least|less|lest|let|let\'s|like|liked|likely|likewise|look|looking|looks|low|lower|ltd|made|mainly|make|makes|many|may|maybe|mayn\'t|me|mean|meantime|meanwhile|merely|might|mightn\'t|mine|minus|miss|more|moreover|most|mostly|mr|mrs|much|must|mustn\'t|my|myself|name|namely|nd|near|nearly|necessary|need|needn\'t|needs|neither|never|neverf|neverless|nevertheless|new|next|nine|ninety|nobody|non|none|nonetheless|noone|no-one|nor|normally|not|nothing|notwithstanding|novel|now|nowhere|obviously|of|off|often|oh|ok|okay|old|once|one|ones|one\'s|only|onto|opposite|or|other|others|otherwise|ought|oughtn\'t|our|ours|ourselves|out|outside|over|overall|own|particular|particularly|past|per|perhaps|placed|please|plus|possible|presumably|probably|provided|provides|que|quite|qv|rather|rd|re|really|reasonably|recent|recently|regarding|regardless|regards|relatively|respectively|right|round|said|same|saw|say|saying|says|second|secondly|see|seeing|seem|seemed|seeming|seems|seen|self|selves|sensible|sent|serious|seriously|seven|several|shall|shan\'t|she|she\'d|she\'ll|she\'s|should|shouldn\'t|since|six|some|somebody|someday|somehow|someone|something|sometime|sometimes|somewhat|somewhere|soon|sorry|specified|specify|specifying|still|sub|such|sup|sure|take|taken|taking|tell|tends|th|than|thank|thanks|thanx|that|that\'ll|thats|that\'s|that\'ve|the|their|theirs|them|themselves|then|thence|there|thereafter|thereby|there\'d|therefore|therein|there\'ll|there\'re|theres|there\'s|thereupon|there\'ve|these|they|they\'d|they\'ll|they\'re|they\'ve|thing|things|think|third|thirty|this|thorough|thoroughly|those|though|three|through|throughout|thru|thus|till|together|too|took|toward|towards|tried|tries|truly|try|trying|t\'s|twice|two|un|under|underneath|undoing|unfortunately|unless|unlike|unlikely|until|unto|up|upon|upwards|us|use|used|useful|uses|using|usually|v|value|various|versus|very|via|viz|vs|want|wants|was|wasn\'t|way|we|we\'d|welcome|well|we\'ll|went|were|we\'re|weren\'t|we\'ve|what|whatever|what\'ll|what\'s|what\'ve|when|whence|whenever|where|whereafter|whereas|whereby|wherein|where\'s|whereupon|wherever|whether|which|whichever|while|whilst|whither|who|who\'d|whoever|whole|who\'ll|whom|whomever|who\'s|whose|why|will|willing|wish|with|within|without|wonder|won\'t|would|wouldn\'t|yes|yet|you|you\'d|you\'ll|your|you\'re|yours|yourself|yourselves|s|you\'ve|zero)\b/i';
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

    }
}