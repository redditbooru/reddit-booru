<?php

namespace Api {

  use Lib;

  class Tracking extends Lib\Dal {

    protected $_dbTable = 'tracking';
    protected $_dbMap = [
      'event' => 'tracking_event',
      'date' => 'tracking_date',
      'data' => 'tracking_data'
    ];

    /**
     * The name of the tracking event
     */
    public $event;

    /**
     * The unix timestamp the event was logged
     */
    public $date;

    /**
     * Data pertaining to the tracking event
     */
    public $data;

    /**
     * Logs a tracking event
     *
     * @param string $eventName The name of the event
     * @param object $data Any data associated with the tracking event to log
     * @return boolean Success of the tracking log
     */
    public static function trackEvent($eventName, $data = null) {
      $event = new Tracking();
      $event->event = $eventName;
      $event->date = time();
      $event->data = json_encode($data);
      return $event->sync();
    }

  }

}