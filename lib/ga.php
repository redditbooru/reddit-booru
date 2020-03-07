<?php

namespace Lib {

  use Ramsey\Uuid\Uuid;

  class Ga {
    const MAX_BATCH_OBJECTS = 20;

    private static $clientId;

    /**
     * Fires a singular GA event
     */
    public static function sendEvent(
      $category,
      $action,
      $label = null,
      $value = null
    ) {
      $event = self::createEventObj($category, $action, $label, $value);
      return self::_sendEvents('collect', [ $event ]);
    }

    /**
     * Fires multiple GA events
     */
    public static function sendEvents(array $events) {
      $retVal = [];

      // GA has a limit of 20 events per request, so send in batches of 20
      for ($i = 0; $i < count($events); $i += self::MAX_BATCH_OBJECTS) {
        $retVal[] = self::_sendEvents(
          'batch', array_slice($events, $i, self::MAX_BATCH_OBJECTS)
        );
      }

      return $retVal;
    }

    /**
     * Creates a GA event object from passed parameters
     */
    public static function createEventObj(
      $category,
      $action,
      $label = null,
      $value = null
    ) {
      // Generate an ID for this session if one doesn't exist
      if (!self::$clientId) {
        self::$clientId = Uuid::uuid4()->toString();
      }

      $retVal = [
        'v' => 1,
        'tid' => GA_ID,
        'cid' => self::$clientId,
        't' => 'event',
        'ec' => $category,
        'ea' => $action
      ];

      if ($label) {
        $retVal['el'] = $label;
      }

      if ($value && is_numeric($value)) {
        $retVal['ev'] = $value;
      }

      return $retVal;
    }

    /**
     * Sends an array of event objects to GA via the selected endpoint
     */
    private static function _sendEvents($method, array $events) {
      $c = curl_init('https://www.google-analytics.com/collect');

      $events = array_map(function($event) {
        $values = [];
        foreach ($event as $key => $value) {
          $values[] = urlencode($key) . '=' . urlencode($value);
        }
        return implode('&', $values);
      }, $events);

      $events = implode("\r\n", $events) . "\r\n";

      curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($c, CURLOPT_POST, strlen($events));
      curl_setopt($c, CURLOPT_POSTFIELDS, $events);

      $retVal = curl_exec($c);
      curl_close($c);

      return $retVal;
    }

  }
}