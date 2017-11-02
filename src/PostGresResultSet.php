<?php
/**
 * @file PostGresResultSet.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

/**
 * PostGres Result-set.
 *
 * <p>When a SELECT-query is executed successfully, an instance of this object
 * is returned. You can iterate over this object to get all query results.</p>
 *
 * <p>When iterating over this object, an instance of {@link PostGresResultRow}
 * is returned for every row of the result-set.</p>
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
class PostGresResultSet extends SQLResultSet{

	private $_result;

	/**
	 * Construct a PostGres Result-set.
	 *
	 * Buffering is not supported yet, it's unknown whether
	 * this will cause troubles
	 *
	 * @param resource $result
	 * @param boolean $buffered
	 *
	 * @throws SQLResultSetException
	 */

	public function __construct($result, $buffered = true){

		$this->_result = $result;
		$this->_buffered = (bool) $buffered;

		while(true){

			try{

				$this->_fetched[] = new PostGresResultRow($result);
			}
			catch(SQLResultRowException $e){

				break;
			}
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

		return false;
	}
}