<?php

/**
 * PDO wrapper class
 */

namespace Lib {

	use PDO;
	use stdClass;

	class Db {

		/**
		 * The handle to the database connection
		 */
		public static $_conn = null;

		/**
		 * The value of the last error message
		 */
		public static $lastError = '';

		/**
		 * The number of calls to the database (for performance profiling)
		 */
		public static $callCount = 0;

		/**
		 * Opens a connection to the database
		 */
		public static function Connect($dsn, $user = '', $pass = '')
		{
			$retVal = false;

			try {
				self::$_conn = new PDO($dsn, $user, $pass);
				self::$_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$retVal = true;
			} catch (PDOException $e) {
				self::$lastError = $e->Message();
			}

			return $retVal;
		}

		/**
		 * Gets the current connection to the database or opens a new one
		 * if the connection hasn't already been opened
		 */
		public static function getConnection()
		{
			// Open the connection if it doesn't already exist
			if (!self::$_conn) {
				self::Connect('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS);
			}

			return self::$_conn;

		}

		/**
		 * Executes a query
		 */
		public static function Query($sql, $params = null)
		{

			$conn = self::getConnection();

			$retVal = null;

			self::$callCount++;

			try {
				$comm = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
				$success = $comm->execute($params);

				switch (strtolower(current(explode(' ', $sql)))) {
					case 'call':
					case 'select':
						$retVal = new stdClass();
						$retVal->count = $comm->rowCount();
						$retVal->comm = $comm;
						break;
					case 'insert':
						$retVal = $conn->lastInsertId();
						break;
					case 'update':
					case 'delete':
						// In case row count is 0, return the success of the query
						$retVal = $comm->rowCount() ?: $success;
						break;
				}

				self::$lastError = $conn->errorInfo();

			} catch (Exception $e) {
				self::$lastError = $e->Message();
				throw $e;
			}

			return $retVal;
		}

		/**
		 * Fetches the next row in a record set
		 */
		public static function Fetch($rs)
		{
			$retVal = null;

			if (is_object($rs) && null != $rs->comm) {
				$retVal = $rs->comm->fetchObject();
			}

			return $retVal;
		}

	}
}