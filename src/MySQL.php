<?php

/**
 * @file MySQL.php
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @author Danko Adamczyk <danko@studyportals.com>
 * @copyright © 2004-2009 Thijs Putman, all rights reserved.
 * @copyright © 2010-2015 StudyPortals B.V., all rights reserved.
 * @version 2.4.0
 */

namespace StudyPortals\SQL;

/**
 * Connection class for the MySQL database (uses mysqli internally).
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */

class MySQL extends SQLEngine implements \Serializable{

	/**
	 * List of InnoDB error codes.
	 * @see http://dev.mysql.com/doc/refman/5.6/en/error-messages-server.html
	 */

	const ER_LOCK_WAIT_TIMEOUT = 1025;

	protected $_server;
	protected $_username;
	protected $_password;

	protected $_connection;

	/**
	 * @var resource
	 */
	protected $_result;

	/**
	 * Creates a new MySQL connection object.
	 *
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param boolean $persistent
	 * @throws ConnectionException
	 */

	public function __construct(
		$server, $username, $password, $database, $persistent = true){

		$this->_server		= $server;
		$this->_username	= $username;
		$this->_password	= $password;
		$this->_database	= $database;

		if($persistent){

			$this->_server = "p:{$this->_server}";
		}

		$this->_connect();
	}

	/**
	 * Connect to the MySQL server.
	 *
	 * @return void
	 * @throws ConnectionException
	 */

	protected function _connect(){

		$this->_connection = @mysqli_connect(
			$this->_server, $this->_username, $this->_password);

		if(!@mysqli_select_db($this->_connection, $this->_database)){

			// Connection established, but unable to access the database

			if(!$this->_connection){

				throw new ConnectionException('Could not open the
					requested database');
			}
			else{

				throw new ConnectionException('Could not connect to the
					MySQL server');
			}
		}
	}

	/**
	 * Function unserialize.
	 *
	 * @param string $data
	 *
	 * @throws ConnectionException
	 * @return void
	 */

	public function unserialize($data){

		list(
			$this->_server,
			$this->_username,
			$this->_password,
			$this->_database
		) = unserialize($data);

		$this->_connect();
	}

	/**
	 * Function serialize.
	 *
	 * @return string
	 */

	public function serialize(){

		@mysqli_close($this->_connection);

		$data = [
			$this->_server,
			$this->_username,
			$this->_password,
			$this->_database
		];

		return serialize($data);
	}

	/**
	 * Create a MySQL instance from a connection-string.
	 *
	 * @param string $sql_string user:password@server:port/database
	 * @return mySQL
	 */

	public static function connectFromString($sql_string){

		list($username, $sql_string) = explode(':', trim($sql_string), 2);

		$password = substr($sql_string, 0, strrpos($sql_string, '@'));

		list($server, $database) =
			explode('/', substr(strrchr($sql_string, '@'), 1));

		return new self($server, $username, $password, $database);
	}

	/**
	 * Send a query to the MySQL server.
	 *
	 * <p>For <strong>SELECT</strong>-queries: If the query has a result, this
	 * method returns either {@link SQLResultRow} or {@link SQLResultSet}
	 * instance, depending on the number of results. If no results are available
	 * the method returns <em>false</em>.</p>
	 *
	 * <p>For all other queries: If the query is successful, the amount of
	 * affected rows is returned, else <em>false</em> is returned.</p>
	 *
	 * @param string $query
	 * @param boolean $return_set
	 * @param boolean $buffered
	 * @return SQLResult|integer|boolean
	 * @throws QueryException
	 * @throws ConnectionException
	 * @see SQL::query()
	 */

	public function query($query, $return_set = false, $buffered = true){

		$this->_result = null;

		$query = trim($query);

		$this->_result = @mysqli_query($this->_connection, $query);

		// Invalid Query

		if(!$this->_result){

			$error_no = @mysqli_errno($this->_connection); //NOSONAR
			$error_message = @mysqli_error($this->_connection); //NOSONAR

			if(empty($error_message)){

				throw new ConnectionException('An unknown error occurred while
					attempting to query the MySQL server');
			}
			else{

				switch($error_no){

					case static::ER_LOCK_WAIT_TIMEOUT:

						throw new SQLUnavailableException("Invalid Query,
							MySQL returned error {$error_no}: {$error_message}");

					break;

					default:

						throw new QueryException("Invalid Query,
							MySQL returned error {$error_no}: {$error_message}");

					break;
				}
			}
		}

		// Valid Query

		else{

			$num_rows = @mysqli_num_rows($this->_result);
			$matches = [];

			/** @noinspection PhpUnusedLocalVariableInspection */
			$match_count = preg_match(
				'/^([\w]+)(?:[\W]+|$)/i', $query, $matches);
			assert('$match_count === 1');

			$query_type = strtoupper($matches[1]);

			switch($query_type){

				case 'SELECT':
				case 'SHOW':
				case 'EXPLAIN':
				case 'DESCRIBE':

					if($return_set && $num_rows >= 1){

						return new MySQLResultSet($this->_result, $buffered);
					}
					elseif($num_rows == 1){

						return new MySQLResultRow($this->_result);
					}
					elseif($num_rows > 1){

						return new MySQLResultSet($this->_result, $buffered);
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

				case 'START':
				case 'COMMIT':
				case 'ROLLBACK':
				case 'SET':

					return true;

				break;

				default:

					return false;
			}
		}
	}

	/**
	 * Return ID of the row created by the last INSERT-query.
	 *
	 * @return integer
	 */

	public function getLastID(){

		return (int) mysqli_insert_id($this->_connection);
	}

	/**
	 * Return number of rows affected by the last INSERT/UPDATE/DELETE-query.
	 *
	 * @return integer
	 */

	public function getAffectedRows(){

		return (int) @mysqli_affected_rows($this->_connection);
	}

	/**
	 * Escape all "dangerous" characters.
	 *
	 * @param string $string
	 * @return string
	 */

	public function escapeString($string){

		return mysqli_real_escape_string($this->_connection, $string);
	}
}