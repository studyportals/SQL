<?php
/**
 * @file MySQLResultSet.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

/**
 * MySQL Result-set.
 *
 * <p>When a SELECT-query is executed successfully, an instance of this object
 * is returned. You can iterate over this object to get all query results.</p>
 *
 * <p>When iterating over this object, an instance of {@link MySQLResultRow}
 * is returned for every row of the result-set.</p>
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
class MySQLResultSet extends SQLResultSet{

	private $_result;

	/**
	 * Construct a MySQL Result-set.
	 *
	 * @param resource $result
	 * @param boolean $buffered
	 *
	 * @throws SQLResultSetException
	 */

	public function __construct($result, $buffered = true){

		$this->_result = $result;
		$this->_buffered = (bool) $buffered;

		try{

			$this->_fetched[] = new MySQLResultRow($this->_result);
		}
		catch(SQLResultRowException $e){

			throw new SQLResultSetException(
				'Unable to read MySQL result,
				result is invalid'
			);
		}
	}

	/**
	 * Read next result-row from the result-set.
	 *
	 * @return SQLResultRow|false
	 */

	public function next(){

		if(next($this->_fetched) instanceof SQLResultRow){

			return current($this->_fetched);
		}
		else{

			try{

				$mySQLResultRow = new MySQLResultRow($this->_result);
			}
			catch(SQLResultRowException $e){

				return false;
			}

			// Clear fetch buffer in case of an unbuffered result-set

			if(!$this->_buffered){

				$this->_fetched = [];
			}

			$this->_fetched[] = $mySQLResultRow;

			return $mySQLResultRow;
		}
	}
}