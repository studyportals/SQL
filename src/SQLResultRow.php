<?php
/**
 * @file SQLResultRow.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

/**
 * This object contains a result row of a SQL query.
 *
 * <p>When a SELECT-query is executed successfully and only one row of results
 * is available, an instance of this object will be returned. You can iterate
 * over this object to get all fields of the result row.</p>
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
class SQLResultRow extends SQLResult implements \Iterator{

	protected $_row = [];
	protected $_field_list = [];

	/**
	 * Get a field from the result row.
	 *
	 * @param string $field
	 *
	 * @return mixed
	 * @throws SQLResultRowException
	 */

	public function __get($field){

		if(in_array($field, $this->_field_list)){

			return $this->_row[$field];
		}
		else{

			throw new SQLResultRowException(
				"Unable to retrieve '$field',
				this field is not present in the SQL Row"
			);
		}
	}

	/**
	 * Rewind the field list to its first element.
	 *
	 * @return boolean
	 */

	public function rewind(){

		return reset($this->_field_list);
	}

	/**
	 * Return the current element value from the field list.
	 *
	 * @return mixed
	 */

	public function current(){

		return current($this->_field_list);
	}

	/**
	 * Return the current element name from the field list.
	 *
	 * @return string
	 */

	public function key(){

		return key($this->_field_list);
	}

	/**
	 * Move pointer to the next element in the field list and return its value.
	 *
	 * @return mixed
	 */

	public function next(){

		return next($this->_field_list);
	}

	/**
	 * Check if the current position in the field list is valid.
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