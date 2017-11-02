<?php

/**
* @file SQL\Engines\SQLite.php
*
* @author Thijs Putman <thijs@studyportals.com>
* @copyright © 2004-2009 Thijs Putman, all rights reserved.
* @copyright © 2010-2015 StudyPortals B.V., all rights reserved.
* @version 2.2.2
*/

namespace StudyPortals\SQL;

use StudyPortals\Exception\ExceptionHandler;

/**
 * Connection class for the SQLite3 database.
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */

class SQLite extends SQLEngine implements \Serializable{

	/**
	 * SQLite3 busy-timeout (in milliseconds).
	 *
	 * <p>By default PHP does not set a busy-timeout for SQLite3 database. This
	 * will cause a query to fail immediately when the database-file is busy. In
	 * a (i.e. our ;) concurrent environment this is not really what we would
	 * like to happen. As such here we configure the busy-timeout to use for our
	 * SQLite3 database. Without looking too much further into this I've
	 * "guesstimated" half a second should suffice...</p>
	 *
	 * @var int
	 */

	const BUSY_TIMEOUT = 500;

	/**
	 * Instance of a (connected) SQLite3 database object.
	 *
	 * @var \SQLite3
	 */

	protected $_Connection;

	/**
	 * Open a SQLite3 database file.
	 *
	 * <p>By default SQLite will not create a non existing database file, if
	 * {@link $create_db} is set to <em>true</em> it will create the
	 * database.</p>
	 *
	 * <p>By default the SQLite3-database is opened for reading and writing,
	 * setting the optional {@link $read_only} parameter to <em>true</em> will
	 * open the file read-only. This is useful to prevent locking/busy issues
	 * in cases where one does not need to write to the database.<br>
	 * <strong>N.B.</strong>: The {@link $create_db} and {@link $read_only}
	 * parameters are mutually exclusive. Setting both to <em>true</em> will
	 * cause read-only to be enabled (which prevents automatic database-file
	 * creation).</p>
	 *
	 * @param string $database
	 * @param boolean $create_db
	 * @param boolean $read_only
	 *
	 * @throws SQLException
	 */

	public function __construct(
		$database, $create_db = false, $read_only = false){

		$this->_database = $database;

		$this->_connect($create_db, $read_only);
	}

	/**
	 * Connect to the SQLite3 database (c.q. open the database file).
	 *
	 * @param boolean $create_db
	 * @param boolean $read_only
	 * @return void
	 * @throws SQLException
	 */

	protected function _connect($create_db = false, $read_only = false){

		if($create_db && $read_only){

			ExceptionHandler::notice('SQLite::_connect() was called with both
				$create_db and $read_only enabled: Assuming read-only');

			$create_db = false;
		}

		if(!file_exists($this->_database) && !$create_db){

			throw new SQLException('Unable to load SQLite database file "'
				. basename($this->_database) . '", file not found');
		}

		try{

			if($read_only){

				$options = SQLITE3_OPEN_READONLY;
			}
			else{

				$options = SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE;
			}

			$this->_Connection = new \SQLite3($this->_database, $options);
			$this->_Connection->busyTimeout(static::BUSY_TIMEOUT);
		}
		catch(\Exception $e){

			throw new SQLException($e->getMessage());
		}

		if(!($this->_Connection instanceof \SQLite3)){

			throw new SQLException('Unspecified error while opening
				SQLite database');
		}
	}

	/**
	 * Unserialize (and reconnect) the SQLite instance.
	 *
	 * @param string $data
	 *
	 * @throws SQLException
	 * @return void
	 */

	public function unserialize($data){

		$this->_database = unserialize($data);

		$this->_connect();
	}

	/**
	 * Serialize (and close) the SQLite instance.
	 *
	 * @return string
	 */

	public function serialize(){

		$this->_Connection->close();

		return serialize($this->_database);
	}

	/**
	 * Execute a query against the SQLite3 database.
	 *
	 * <p>For <strong>SELECT</strong>-queries: If the query has a result, this
	 * method returns either {@link SQLResultRow} or {@link SQLResultSet}
	 * instance, depending on the number of results. If no results are available
	 * the method returns <em>false</em>.</p>
	 *
	 * <p>For all other queries: If the query is successful, the amount of
	 * affected rows is returned, else <em>false</em> is returned.</p>
	 *
	 * <p><strong>Note:</strong> The {
	 *
	 * @param string $query
	 * @param boolean $return_set
	 * @param boolean $buffered
	 *
	 * @throws QueryException
	 * @return SQLResult|integer|boolean@see SQL::query()
	 */

	public function query($query, $return_set = false, $buffered = true){

		$query = trim($query);

		$Result = @$this->_Connection->query($query);

		// Formatting takes some time, so explicitly check FireLogger's state

		if(!($Result instanceof \SQLite3Result)){

			$message = sprintf(
				'Invalid query, SQLite returned "%s" (code: %s)',
				$this->_Connection->lastErrorMsg(),
				$this->_Connection->lastErrorCode()
			);

			throw new QueryException($message);
		}

		$matches = [];

		/** @noinspection PhpUnusedLocalVariableInspection */
		$match_count = preg_match('/^([\w]+)(?:[\W]+|$)/i', $query, $matches);
		assert('$match_count === 1');

		$query_type = strtoupper($matches[1]);

		switch($query_type){

			case 'SELECT':
			case 'SHOW':

				/*
				 * Figure out how many rows are in the result-set.
				 *
				 * This is "a bit" brute-force, but there still (as of May 2014)
				 * doesn't appear to be a better way to do this. This wrapper is
				 * intended for convenience, not for optimal performance, so
				 * let's not worry too much about this...
				 */

				$row1 = $Result->fetchArray(SQLITE3_NUM);

				$num_rows = 0;

				if(!empty($row1) && is_array($row1)){

					$num_rows = 1;
					$row2 = $Result->fetchArray(SQLITE3_NUM);

					if(!empty($row2) && is_array($row2)){

						$num_rows = 2;
					}
				}

				$Result->reset();

				if($return_set && $num_rows >= 1){

					return new SQLiteResultSet($Result, $buffered);
				}
				elseif($num_rows == 1){

					return new SQLiteResultRow($Result);
				}
				elseif($num_rows > 1){

					return new SQLiteResultSet($Result, $buffered);
				}
				else{

					return false;
				}

			break;

			case 'DELETE':
			case 'INSERT':
			case 'REPLACE':
			case 'UPDATE':

				return $this->getAffectedRows();

			break;

			default:

				return false;
		}
	}

	/**
	 * Escape a string for use within an SQLite3 query.
	 *
	 * <p>As SQLite3 does not support null-bytes (\0) inside its string data-
	 * type (nor offers a binary data-type) a little trick (courtesy of Google's
	 * Python App Engine) is required:<br>
	 * Apart from escaping the string passed in, this methods converts "\1" to
	 * "\1\2" and "\0" to "\1\1". This encoding is reversible, can be safely
	 * stored inside the SQLite 3 string data-type and does not influence the
	 * sorting of said string. As part of {@link SQLiteResultRow::__construct()}
	 * the encoding process is reversed.</p>
	 *
	 * @param string $string
	 * @return string
	 * @see SQLiteResultRow::__construct()
	 */

	public function escapeString($string){

		// Convert null-bytes to a format safe to store within SQLite 3

		$string = str_replace(["\1", "\0"], ["\1\2", "\1\1"], $string);

		return \SQLite3::escapeString($string);
	}

	/**
	 * Return the ID of the last inserted row.
	 *
	 * @return integer
	 */

	public function getLastID(){

		return $this->_Connection->lastInsertRowID();
	}

	/**
	 * Return number of rows affected by the last INSERT/UPDATE/DELETE-query.
	 *
	 * @return integer
	 */

	public function getAffectedRows(){

		return $this->_Connection->changes();
	}
}