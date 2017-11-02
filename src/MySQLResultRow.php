<?php
/**
 * @file MySQLResultRow.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

/**
 * MySQL Result-row.
 *
 * <p>If a SELECT-query is executed successfully and only one result-row is
 * available, an instance of this object is returned. You can iterate over this
 * object to get all fields of the result-row.</p>
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
class MySQLResultRow extends SQLResultRow{

	protected $_row = [];
	protected $_field_list = [];

	/**
	 * Construct a MySQL Result-row.
	 *
	 * @param resource \mysqli_result
	 *
	 * @throws SQLResultRowException
	 */

	public function __construct($result){

		$this->_row = @mysqli_fetch_array($result, MYSQLI_ASSOC);

		if(!is_array($this->_row)){

			throw new SQLResultRowException(
				'Unable to read row from
				MySQL query result'
			);
		}

		$this->_field_list = array_keys($this->_row);
	}
}