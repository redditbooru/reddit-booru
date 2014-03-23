<?php

define('USE_MOCK_DB', true);

require('harness.php');

class DalObject extends Lib\Dal {

    protected $_dbTable = 'test';
    protected $_dbPrimaryKey = 'id';
    protected $_dbMap = [
        'id' => 'table_id',
        'prop1' => 'table_prop1'
    ];

    public $id;
    public $prop1;

}

class DalTest extends PHPUnit_Framework_TestCase {

    public function testBasicQuery() {

        $result = DalObject::query();
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test`');

    }

    public function testQueryWithBasicCondition() {
        $result = DalObject::query([ 'id' => 5 ]);
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test` WHERE `table_id` = :id');
        $this->assertEquals($result->params, [ ':id' => 5 ]);

        // error on invalid column
        $exception = false;
        try {
            $result = DalObject::query([ '; DROP TABLE `bobby`' => 'descending' ]);
        } catch (Exception $e) {
            $exception = true;
        }
        $this->assertTrue($exception);

    }

    public function testQueryWithSort() {
        // single sort
        $result = DalObject::query(null, [ 'id' => 'descending' ]);
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test` ORDER BY `table_id` DESC');

        // Multi sort
        $result = DalObject::query(null, [ 'id' => 'ASC', 'prop1' => 'desc' ]);
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test` ORDER BY `table_id` ASC, `table_prop1` DESC');

        // error on invalid column
        $exception = false;
        try {
            $result = DalObject::query(null, [ '; DROP TABLE `bobby`' => 'descending' ]);
        } catch (Exception $e) {
            $exception = true;
        }
        $this->assertTrue($exception);

    }

    public function testQueryWithLimit() {
        // without offset
        $result = DalObject::query(null, null, 5);
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test` LIMIT 5');

        // throw out non-numeric limit
        $result = DalObject::query(null, null, '; DROP TABLE `bobby`');
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test`');

        // with offset
        $result = DalObject::query(null, null, 5, 5);
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test` LIMIT 5, 5');

        // throw out non-numeric limit
        $result = DalObject::query(null, null, 5, '; DROP TABLE `bobby`');
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test` LIMIT 5');

    }

    public function testQueryAll() {
        $result = DalObject::query([ 'id' => 5, 'prop1' => 'this thing' ], [ 'id' => 'desc' ], 5, 5);
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test` WHERE `table_id` = :id AND `table_prop1` = :prop1 ORDER BY `table_id` DESC LIMIT 5, 5');
        $this->assertEquals($result->params, [ ':id' => 5, ':prop1' => 'this thing' ]);
    }

    public function testQueryIn() {
        $result = DalObject::query([ 'id' => [ 'in' => [ 1, 2, 3 ] ] ]);
        $this->assertEquals($result->query, 'SELECT `table_id`, `table_prop1` FROM `test` WHERE `table_id` IN (:id0, :id1, :id2)');
        $this->assertEquals($result->params, [ ':id0' => 1, ':id1' => 2, ':id2' => 3 ]);
    }

}