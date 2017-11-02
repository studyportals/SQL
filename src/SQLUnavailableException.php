<?php
/**
 * @file SQLUnavailableException.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

use StudyPortals\Exception\HTTPException;

/**
 * SQLUnavailableException
 *
 * @package StudyPortals\Framework\SQL
 * @subpackage SQL
 */
class SQLUnavailableException extends QueryException implements HTTPException{

	/**
	 * Get the status-code of this exception.
	 *
	 * @return integer
	 */

	public function getStatusCode(){

		return 503;
	}

	/**
	 * Get the message that belongs to this status-code.
	 *
	 * @return string
	 */

	public function getStatusMessage(){

		return 'Service Unavailable';
	}
}