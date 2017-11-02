<?php
/**
 * @file SQLiteResultRow.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

/**
 * SQLite3 Result-set.
 *
 * <p>If a SELECT-query is executed successfully and only one result-row is
 * available, an instance of this object is returned. You can iterate over this
 * object to get all fields of the result-row.</p>
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
class SQLiteResultRow extends SQLResultRow{

	/**
	 * Create a new SQLite3 Result-row.
	 *
	 * @param \SQLite3Result $Result
	 *
	 * @throws SQLResultRowException
	 */

	public function __construct(\SQLite3Result $Result){

		$this->_row = $Result->fetchArray(SQLITE3_ASSOC);

		if(!is_array($this->_row)){

			throw new SQLResultRowException(
				'Unable to read row from
				SQLite query result'
			);
		}

		// Convert null-bytes back to their original value

		foreach($this->_row as $key => $value){

			$this->_row[$key] =
				str_replace(["\1\1", "\1\2"], ["\0", "\1"], $value);
		}

		$this->_field_list = array_keys($this->_row);
	}
}