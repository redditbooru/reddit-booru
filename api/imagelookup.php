<?php

namespace Api {

  use Controller;
  use Lib;
  use stdClass;

  /**
   * Return thisect data class. Mixes only essential items from row, Post, User, and Source
   */
  class ImageLookup extends Lib\Dal {

    protected $_dbMap = [
      'imageId' => 'image_id',
      'sourceId' => 'source_id',
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
    ];

    /**
     * ID of the image
     */
    public $imageId;

    /**
     * ID of the source
     */
    public $sourceId;

    /**
     * Red components
     */
    public $histR1;
    public $histR2;
    public $histR3;
    public $histR4;

    /**
     * Green components
     */
    public $histG1;
    public $histG2;
    public $histG3;
    public $histG4;

    /**
      * Blue components
      */
    public $histB1;
    public $histB2;
    public $histB3;
    public $histB4;

    private static $_tablePrefix = 'img_lookup_';

    /**
     * Table shards
     */
    private static $_shards = [
      'r1', 'r2', 'r3', 'r4',
      'g1', 'g2', 'g3', 'g4',
      'b1', 'b2', 'b3', 'b4'
    ];

    /**
     * Creates a lookup entry for an image with a source
     */
    public static function syncLookupEntry(Image $image, Source $source) {
      $obj = new ImageLookup();

      $obj->imageId = $image->id;
      $obj->sourceId = $source->id;
      foreach (self::$_shards as $shard) {
        $prop = 'hist' . strtoupper($shard);
        $obj->$prop = $image->$prop;
      }

      $obj->_dbTable = self::_getTableShardForImage($image);

      return $obj->sync();
    }

    /**
     * Searches for posts by image and returns an array of matching image IDs and their Euclidian
     * distance to the original.
     */
    public static function reverseLookup(Image $image, array $sources = [], $count = 5) {

      $tableName = self::_getTableShardForImage($image);
      header('X-Lookup-Shard: ' . $tableName);

      $query = 'SELECT image_id';

      $params = [];
      $query .= ', (';
      for ($i = 1; $i <= HISTOGRAM_BUCKETS; $i++) {
        $prop = 'histR' . $i;
        $params[':red' . $i] = $image->$prop;
        $prop = 'histG' . $i;
        $params[':green' . $i] = $image->$prop;
        $prop = 'histB' . $i;
        $params[':blue' . $i] = $image->$prop;
        $query .= 'ABS(`image_hist_r' . $i . '` - :red' . $i . ') + ABS(`image_hist_g' . $i . '` - :green' . $i . ') + ABS(`image_hist_b' . $i . '` - :blue' . $i . ') + ';
      }
      $query .= ' 0) AS distance';

      $query .= ' FROM `' . $tableName . '` ';

      $where = [];

      if ($sources) {
        $tmpList = [];
        $i = 0;
        foreach ($sources as $source) {
          $params[':source' . $i] = $source;
          $tmpList[] = ':source' . $i;
          $i++;
        }
        $where[] = 'source_id IN (' . implode(',', $tmpList) . ')';
      }

      if (count($where)) {
        $query .= 'WHERE ' . implode(' AND ', $where) . ' ';
      }

      $query .= 'ORDER BY distance LIMIT ' . ($count * 2);
      $startTime = microtime(true);
      $result = Lib\Db::Query($query, $params);
      header('X-Lookup-Time: ' . (microtime(true) - $startTime));

      $time = time();
      $retVal = [];
      if ($result && $result->count) {
        while($row = Lib\Db::Fetch($result)) {
          $retVal[] = (object)[
            'imageId' => (int) $row->image_id,
            'distance' => (float) $row->distance
          ];
        }
      }

      foreach ($params as $key => $val) {
        $query = str_replace($key, $val, $query);
      }

      return $retVal;
    }

    /**
     * Determines which table shard to use for the passed image
     */
    private static function _getTableShardForImage(Image $image) {
      $max = -1;
      $maxShard = '';

      foreach (self::$_shards as $shard) {
        $prop = 'hist' . strtoupper($shard);
        if ($image->$prop > $max) {
          $max = $image->$prop;
          $maxShard = $shard;
        }
      }

      return self::$_tablePrefix . $maxShard;
    }

  }

}