<?php

namespace Lib {

    use stdClass;

    /**
     * Basic internal and external event dispatcher
     */
    class Events {

        /**
         * Associative array of events to listeners
         */
        private static $events = [];

        /**
         * Sets things up for an evented ajax call
         */
        public static function beginAjaxEvent() {
            header('Content-Type: application/octet-stream');
        }

        public static function endAjaxEvent() {
            exit;
        }

        /**
         * Sends a JSON object to the client
         * @obj object Object to send
         */
        public static function sendAjaxEvent($eventType, $data) {
            $out = new stdClass;
            $out->eventType = $eventType;
            $out->data = $data;
            $out = json_encode($out);

            // Pad out the request to a minimum of 2K for Chrome
            if (strlen($out) < 4096) {
                $out = str_pad($out, 4096, ' ');
            }
            echo $out;
            flush();
        }

        /**
         * Registers a callback for an event type
         */
        public static function addEventListener($eventType, $callback) {
            if (is_callable($callback)) {
                if (!isset(self::$events[$eventType])) {
                    self::$events[$eventType] = [];
                }
                self::$events[$eventType][] = $callback;
            }
        }

        /**
         * Fires an events and triggers any register event listeners
         */
        public static function fire($eventType, $data = null) {
            if (isset(self::$events[$eventType])) {
                foreach (self::$events[$eventType] as $callback) {
                    $callback($data);
                }
            }
        }

    }

}