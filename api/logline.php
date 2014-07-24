<?php

namespace Api {
    
    define('LOG_IP', 1);
    define('LOG_DATE', 2);
    define('LOG_VERB', 3);
    define('LOG_PATH', 4);
    define('LOG_HTTP_CODE', 5);
    define('LOG_SIZE', 6);
    define('LOG_REFERER', 7);
    define('LOG_USER_AGENT', 8);

    class LogLine {

        /**
         * IP address
         */
        public $ip;

        /**
         * Date
         */
        public $date;

        /**
         * Request verb
         */
        public $verb;

        /**
         * Request path
         */
        public $requestPath;

        /**
         * HTTP status code
         */
        public $httpCode;

        /**
         * Size of content
         */
        public $size;

        /**
         * Referer
         */
        public $referer;

        /**
         * User agent
         */
        public $userAgent;

        /**
         * Constructor
         * @param string $line An HTTP log line to parse
         */
        public function __construct($line = null) {
            if ($line) {
                $this->parseLine($line);
            }
        }

        /**
         * Fills in log details from an HTTP log line
         */
        public function parseLine($line) {
            if (preg_match('/([\d\.]+) \S+ \S+ \[([^\]]+)\] \"(GET|POST|HEAD) ([^\s]+) HTTP\/1\.[\d]\" ([\d]{3}) ([\d]+) \"([^\"]+)\" \"([^\"]+)\"/is', $line, $matches)) {
                $this->ip = $matches[LOG_IP];
                $this->date = strtotime($matches[LOG_DATE]);
                $this->verb = $matches[LOG_VERB];
                $this->requestPath = $matches[LOG_PATH];
                $this->httpCode = (int) $matches[LOG_HTTP_CODE];
                $this->size = (int) $matches[LOG_SIZE];
                $this->referer = $matches[LOG_REFERER] != '-' ? $matches[LOG_REFERER] : null;
                $this->userAgent = $matches[LOG_USER_AGENT];
            }
        }

    }

}