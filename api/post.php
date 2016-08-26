<?php

namespace Api {

    use Controller;
    use Lib;
    use stdClass;

    define('POST_SEARCH_COUNT', 25);

    class Post extends Lib\Dal {

        /**
         * Number of user galleries to display per page
         */
        const GALLERIES_PER_PAGE = 15;

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

        public function sync($forceInsert = false) {
            $this->dateUpdated = time();
            return parent::sync($forceInsert);
        }

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
                $this->nsfw = $obj->nsfw;
                $this->visible = $obj->visible;
            }
        }

        /**
         * Creates an instance of Post from a row returned from the reddit API
         */
        public static function createFromRedditObject($obj) {
            $retVal = new Post();
            $retVal->externalId = $obj->id;
            $retVal->title = $obj->title;

            // Reddit is entity encoding their URLs... don't know why, but they are...
            $retVal->link = html_entity_decode($obj->url);

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

        public function setKeywordsFromTitle() {
            $this->keywords = self::generateKeywords($this->title);
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

        /**
         * Returns all posts that link to the post in question
         */
        public function getLinkedPosts() {
            $id = $this->id;
            return Lib\Cache::fetch(function() use ($id) {

                $retVal = null;

                $result = Lib\Db::Query('CALL proc_GetLinkedPosts(:id)', [ ':id' => $id ]);
                if ($result && $result->count) {
                    $retVal = [];
                    while ($row = Lib\Db::Fetch($result)) {
                        $retVal[] = new PostData($row);
                    }
                }

                return $retVal;

            }, 'Api::Post::getLinkedPosts_' . $id);
        }

        /**
         * Returns a list of galleries (and their images) created by a user
         */
        public static function getUserGalleries($userId, $page = 1) {

            $page = is_numeric($page) ? $page : 1;
            $cacheKey = 'Api:PostData:getUserGalleries_' . $userId . '_' . $page;
            $retVal = Lib\Cache::get($cacheKey);

            if (false === $retVal) {
                $numGalleries = Post::getCount([ 'userId' => $userId, 'link' => [ 'LIKE' => '%redditbooru.com%' ] ]);
                $numPages = ceil($numGalleries / self::GALLERIES_PER_PAGE);
                $page = $page > $numGalleries ? $numGalleries : $page;

                // Get the correct set of posts plus an image for each
                $query = 'SELECT p.*, i.* FROM `posts` p INNER JOIN `post_images` pi ON pi.`post_id` = p.`post_id` ';
                $query .= 'INNER JOIN `images` i ON i.`image_id` = pi.`image_id` WHERE p.`user_id` = :userId AND ';
                $query .= 'p.`post_link` LIKE "%.redditbooru.com%" GROUP BY p.`post_id` ORDER BY p.`post_date` DESC ';
                $query .= 'LIMIT ' . (($page - 1) * self::GALLERIES_PER_PAGE) . ', ' . self::GALLERIES_PER_PAGE;

                $posts = [];
                $result = Lib\Db::Query($query, [ ':userId' => $userId ]);
                if ($result && $result->count) {
                    while ($row = Lib\Db::Fetch($result)) {
                        $post = new Post($row);
                        if ($post->isSelfLinked()) {
                            $post->linkedPosts = $post->getLinkedPosts();
                            $post->galleryLink = $post->getGalleryUrl();
                            $image = new Image($row);
                            // I really need to sort this stuff out. Thumbs should probably be an "api"
                            $post->posterImage = Controller\Thumb::createThumbFilename($image->getFilename(true));
                            $posts[] = $post;
                        }
                    }
                }

                $retVal = (object)[
                    'results' => $posts,
                    'paging' => (object)[
                        'current' => $page,
                        'total' => $numPages
                    ]
                ];

                Lib\Cache::Set($cacheKey, $retVal);

            }

            return $retVal;

        }

        /**
         * Extracts the post ID from a redditbooru link
         */
        public static function getPostIdFromUrl($url) {
            $retVal = null;

            if (preg_match('/^http[s]?:\/\/([\w\.]+)?redditbooru\.com\/gallery\/([\w]+)(\/[\w-]+)?/is', $url, $matches)) {
                if (count($matches) === 4) {
                    $retVal = base_convert($matches[2], 36, 10);
                } else {
                    $retVal = (int) $matches[2];
                }
            }

            return $retVal;
        }

        /**
         * Whether or not this post links to itself
         */
        public function isSelfLinked() {
            $id = self::getPostIdFromUrl($this->link);
            return $this->id == $id;
        }

        /**
         * Gets the gallery URL for this post
         */
        public function getGalleryUrl() {
            return self::createGalleryUrl($this->id, $this->title);
        }

        /**
         * Creates a gallery URL
         */
        public static function createGalleryUrl($postId, $title) {
            $title = preg_replace('/[\W]/', ' ', $title);
            $title = preg_replace('/[\s]+/', '-', trim($title));
            return '/gallery/' . base_convert($postId, 10, 36) . '/' . strtolower($title);
        }

    }
}