<?php
/**
 * @file SQLiteResultSet.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

/**
 * SQLite3 Result-set.
 *
 * <p>When a SELECT-query is executed successfully, an instance of this object
 * is returned. You can iterate over this object to get all query results.</p>
 *
 * <p>When iterating over this object, an instance of {@link SQLiteResultRow}
 * is returned for every row of the result-set.</p>
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
class SQLiteResultSet extends SQLResultSet{

	/**
	 * Creates the SQLite query result object.
	 *
	 * <p><strong>Note:</strong> The {@link $buffered} argument is ignored; it
	 * always enabled for the SQLite database engine.</p>
	 *
	 * @param \SQLite3Result $result
	 * @param boolean $buffered
	 *
	 * @throws SQLResultSetException
	 */

	function __construct($result, $buffered = true){

		if(!($result instanceof \SQLite3Result)){

			throw new SQLResultSetException(
				'Invalid result-type,
				should be instance of SQLite3Result'
			);
		}

		$this->_fetched = [];
		$this->_buffered = (bool) $buffered;

		while(true){

			try{

				$this->_fetched[] = new SQLiteResultRow($result);
			}
			catch(SQLResultRowException $e){

				// No more results available

				break;
			}
		}

		$result->finalize();

		if(empty($this->_fetched)){

			throw new SQLResultSetException(
				'Unable to read SQLite result,
				result-set appears to be empty'
			);
		}

		reset($this->_fetched);
	}

	/**
	 * Read the next result-row from the result-set.
	 *
	 * @return SQLiteResultRow|false
	 */

	public function next(){

		if(next($this->_fetched) instanceof SQLResultRow){

			return current($this->_fetched);
		}

		return false;
	}
}