<?php
/**
 * @file SQLResultSet.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

/**
 * This object contains the result set of a SQL query.
 *
 * <p>When a SELECT-query is executed successfully and multiple rows are
 * available in the result set, an instance of this object will be returned.
 * You can iterate over this object to get all query results. The individual
 * rows will be returned as instances of the {@link SQLResultRow}.</p>
 *
 * @package Sgraastra.Framework
 * @subpackage SQL
 */
abstract class SQLResultSet extends SQLResult implements \Iterator,
	\Serializable{

	protected $_buffered = true;
	protected $_fetched = [];

	/**
	 * Construct a new result-set.
	 *
	 * @param resource $result
	 * @param boolean $buffered
	 *
	 * @return SQLResultSet
	 * @see SQL::Query()
	 */

	abstract function __construct($result, $buffered = true);

	/**
	 * Unserialize the SQLResultSet.
	 *
	 * @param string $data
	 *
	 * @return void
	 */

	public function unserialize($data){

		$this->_fetched = unserialize($data);
	}

	/**
	 * Serialize the SQLResultSet.
	 *
	 * @return string
	 * @see SQLResultSet::fetchAllRows()
	 */

	public function serialize(){

		$this->fetchAllRows();

		return serialize($this->_fetched);
	}

	/**
	 * Fetches all the results from the provided SQL result pointer.
	 *
	 * <p>This method fetches all the non-fetched results from the SQL-result
	 * pointer. This enables cloning or serializing the object since it is no
	 * longer dependant on the result pointer.</p>
	 *
	 * <p>Note that this method is automatically called when the object is
	 * serialized.</p>
	 *
	 * @return void
	 */

	public function fetchAllRows(){

		/** @noinspection PhpVoidFunctionResultUsedInspection */
		while($this->next()){
			;
		}
	}

	/**
	 * Rewinds the result set to its first element.
	 *
	 * @return boolean
	 */

	public function rewind(){

		return reset($this->_fetched);
	}

	/**
	 * Returns the current row from the result set.
	 *
	 * @return SQLResultRow
	 */

	public function current(){

		return current($this->_fetched);
	}

	/**
	 * Returns the key for the current row in the result set.
	 *
	 * @return integer
	 */

	public function key(){

		return key($this->_fetched);
	}

	/**
	 * Checks whether the current position in the result set is valid.
	 *
	 * @return boolean
	 */

	public function valid(){

		if($this->current() !== false){

			return true;
		}
		else{

			return false;
		}
	}
}