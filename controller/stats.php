<?php

namespace Controller {

	use Lib;
	use stdClass;

	class Stats {

		public static function render() {
			$action = Lib\Url::Get('action', 'keywordRankings');
            $cacheKey = Lib\Cache::createCacheKey('Api.Post.' . $action, [
            	'user',
            	'minDate',
            	'maxData',
            	'sourceId'
        	], $_GET);
			$retVal = Lib\Cache::getInstance()->fetch(function() {
                $retVal = null;
                switch ($action) {
                    case 'keywordScores':
                        $retVal = self::getKeywordsScore($_GET);
                        break;
                    case 'keywordAvgScores':
                        $retVal = self::getKeywordsAvgScore($_GET);
                        break;
                    case 'keywordRankings':
                    default:
                        $retVal = self::getKeywordRanks($_GET);
                        break;
                }
                return $retVal;
            }, $cacheKey);

			header('Content-Type: text/javascript');
			echo json_encode($retVal);
			exit;
		}

        /**
         * Returns a list of the highest ranking keywords
         */
        public static function getKeywordRanks($vars) {
    		$data = self::_getKeywordsData($vars);
            return self::_getCommonPhrases($data);
        }

        /**
         * Returns the scores of common keywords
         */
		public static function getKeywordsScore($vars) {
			return self::_keywordScores($vars);
		}

		public static function getKeywordsAvgScore($vars) {
			return self::_keywordScores($vars, true);
		}

		private static function _keywordScores($vars, $avg = false) {
			$retVal = [];
			$data = self::_getKeywordsData($vars);
			$phrases = self::_getCommonPhrases($data);

			foreach($phrases as $phrase => $total) {
				$score = self::_getPhraseScoreSum($data, $phrase);
				$retVal[$phrase] = $avg ? round($score->score / $score->count) : $score->score;
			}

			arsort($retVal);
			return $retVal;
		}

        private static function _getKeywordsData($vars) {
            $user = Lib\Url::Get('user', null, $vars);
            $minDate = Lib\Url::Get('minDate', null, $vars);
            $maxDate = Lib\Url::Get('maxDate', null, $vars);
            $sourceId = Lib\Url::GetInt('sourceId', null, $vars);
            $cacheKey = Lib\Cache::createCacheKey('Api.Post._getKeywordsData', [
            	'user',
            	'minDate',
            	'maxData',
            	'sourceId'
        	], $vars);

            $cache = Lib\Cache::getInstance();
            $retVal = $cache->get($cacheKey);
            if (false === $retVal) {
                $query = 'SELECT post_id, post_keywords, post_score FROM posts p';
                $where = [];
                $params = [];

                if ($user) {
                    $query .= ' INNER JOIN users u ON p.user_id = u.user_id';
                    $where[] = 'u.user_name = :userName';
                    $params[':userName'] = $user;
                }

                if (null !== $minDate) {
                    $minDate = !is_numeric($minDate) ? strtotime($minDate) : $minDate;
                    $where[] = 'p.post_date >= :minDate';
                    $params[':minDate'] = $minDate;
                }

                if (null !== $maxDate) {
                    $maxDate = !is_numeric($maxDate) ? strtotime($maxDate) : $maxDate;
                    $where[] = 'p.post_date <= :maxDate';
                    $params[':maxDate'] = $maxDate;
                }

                if (null !== $sourceId) {
                	$where[] = 'p.source_id = :sourceId';
                	$params[':sourceId'] = $sourceId;
                }

                $query .= ' WHERE ' . implode(' AND ', $where);
                $result = Lib\Db::Query($query, $params);
                if ($result && $result->count) {
                    $retVal = [];
                    while ($row = Lib\Db::Fetch($result)) {
                        $obj = new stdClass;
                        $obj->keywords = $row->post_keywords;
                        $obj->score = (int) $row->post_score;
                        $retVal[$row->post_id] = $obj;
                    }
                    $cache->set($cacheKey, $retVal);
                }
            }
            return $retVal;
        }

        /**
         * Returns a list of common phrases in a list of phrases ordered by usage
         */
        private static function _getCommonPhrases($data) {

            $phrases = [];

            foreach($data as $id => $item) {

                $title = explode(' ', trim($item->keywords));
                $found = 0;
                $words = 1;
                $titleLen = count($title);
                $localPhrases = [];

                for ($i = 0; $i < $titleLen; $i++) {
                    $lastPhrase = '';
                    $phrase = '';
                    for ($j = 0, $wordsLeft = $titleLen - $i; $j < $wordsLeft; $j++) {
                        $phrase = trim($phrase . ' ' . $title[$i + $j]);

                        if (strlen($phrase) > 2 && self::_hasSimilarPhrases($phrase, $id, $data)) {
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

                        $lastPhrase = $phrase;
                    }
                }

                // Remove duplicate phrase chunks leaving only the longest
                foreach ($localPhrases as $needle => $nCount) {
                    if ($localPhrases[$needle] > 0) {
                        foreach ($localPhrases as $haystack => $hCount) {
                            if ($needle != $haystack && strpos($haystack, $needle) !== false) {
                                $localPhrases[$needle] = 0;
                                break;
                            }
                        }
                    }
                }

                $phrases = self::_mergePhrases($localPhrases, $phrases);

            }

            $phrases = array_filter($phrases, function($a) { return $a > 0; });
            arsort($phrases);

            return $phrases;

        }

        private static function _hasSimilarPhrases($keyphrase, $postId, &$data) {
            foreach ($data as $id => $item) {
                if ($postId != $id && strpos($item->keywords, $keyphrase) !== false) {
                    return true;
                }
            }
            return false;
        }

        private static function _mergePhrases($arr, $phrases) {
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

        private static function _getPhraseScoreSum($data, $phrase) {
			$retVal = new stdClass;
			$retVal->score = 0;
			$retVal->count = 0;
        	foreach ($data as $id => $item) {
        		if (false !== strpos($item->keywords, $phrase)) {
        			$retVal->score += $item->score;
        			$retVal->count++;
        		}
        	}

        	return $retVal;
        }

	}

}