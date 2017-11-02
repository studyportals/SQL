<?php

/**
 * @file PostGres.php
 *
 * @author Simon Nouwens <simon@studyportals.com>
 * @copyright © 2016 StudyPortals B.V., all rights reserved.
 * @version 0.1.0
 */

namespace StudyPortals\SQL;

/**
 * Connection class for the PostGres database (uses pg internally).
 * This requires the php_pgsql extension
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
class PostGres extends SQLEngine implements \Serializable
{
    protected $_server;
    protected $_username;
    protected $_password;
    protected $_port;

    protected $_connection;
    protected $_result;

    /**
     * Creates a new PostGres connection object.
     *
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string $database
     * @param int $port
     * @throws ConnectionException
     */

    public function __construct(
        $server,
        $username,
        $password,
        $database,
        $port = 5439
    ) {

        $this->_server = $server;
        $this->_username = $username;
        $this->_password = $password;
        $this->_port = $port;
        $this->_database = $database;

        $this->_connect();
    }

    /**
     * Connect to the PostGres server.
     *
     * @return void
     * @throws ConnectionException
     */

    protected function _connect()
    {
        $connectionString = "host={$this->_server} port={$this->_port} " .
            "dbname={$this->_database} user={$this->_username} " .
            "password={$this->_password}";
        $this->_connection = \pg_connect($connectionString);
        if (!$this->_connection) {
            throw new ConnectionException('Failed to connect to PostGres');
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

    public function unserialize($data)
    {

        list(
            $this->_server,
            $this->_username,
            $this->_password,
            $this->_database,
            $this->_port,
            ) = unserialize($data);

        $this->_connect();
    }

    /**
     * Function serialize.
     *
     * @return string
     */

    public function serialize()
    {

        @\pg_close($this->_connection);

        $data = [
            $this->_server,
            $this->_username,
            $this->_password,
            $this->_database,
            $this->_port,
        ];

        return serialize($data);
    }

    /**
     * Create a PostGres instance from a connection-string.
     *
     * @param string $sql_string user:password@server:port/database
     * @return PostGres
     */

    public static function connectFromString($sql_string)
    {
        list($username, $sql_string) = explode(':', trim($sql_string), 2);

        $password = substr($sql_string, 0, strrpos($sql_string, '@'));

        list($server, $database) =
            explode('/', substr(strrchr($sql_string, '@'), 1));
        $host = explode(":", $server)[0];
        $port = explode(":", $server)[1];
        return new self($host, $username, $password, $database, $port);
    }

    /**
     * Send a query to the PostGres server.
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
     * @throws ConnectionException
     * @see SQL::query()
     */

    public function query($query, $return_set = false, $buffered = true)
    {
        $this->_result = null;

        $query = trim($query);

        $this->_result = @pg_query($this->_connection, $query);

        // Invalid Query

        if (!$this->_result) {

            $error_message = @\pg_last_error($this->_connection); //NOSONAR

            throw new ConnectionException('An error occurred while
					attempting to query the PostGres server: ' . $error_message);

        } // Valid Query

        else {

            $num_rows = @\pg_num_rows($this->_result);
            $matches = [];

            /** @noinspection PhpUnusedLocalVariableInspection */
            $match_count = preg_match(
                '/^([\w]+)(?:[\W]+|$)/i', $query, $matches);
            assert('$match_count === 1');

            $query_type = strtoupper($matches[1]);

            switch ($query_type) {

                case 'SELECT':
                case 'SHOW':
                case 'EXPLAIN':
                case 'DESCRIBE':

                    if ($return_set && $num_rows >= 1) {

                        return new PostGresResultSet($this->_result, $buffered);
                    } elseif ($num_rows == 1) {

                        return new PostGresResultRow($this->_result);
                    } elseif ($num_rows > 1) {

                        return new PostGresResultSet($this->_result, $buffered);
                    } else {

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
	 * Should return an ID of the row created by the last INSERT-query.
	 *
	 * @throws \Exception
	 */

    public function getLastID()
    {
        throw new \Exception("Last ID not implemented for postGres");
    }

    /**
     * Return number of rows affected by the last INSERT/UPDATE/DELETE-query.
     *
     * @return integer
     */

    public function getAffectedRows()
    {

        return (int)@\pg_affected_rows($this->_connection);
    }

    /**
     * Escape all "dangerous" characters.
     *
     * @param string $string
     * @return string
     */

    public function escapeString($string)
    {
        return \pg_escape_string($this->_connection, $string);
    }
}