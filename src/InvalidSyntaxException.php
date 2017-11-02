<?php
/**
 * @file InvalidSyntaxException.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

/**
 * @class InvalidSyntaxException
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
class InvalidSyntaxException extends QueryBuilderException{

	/**
	 * Construct a new InvalidSyntaxException.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $Previous
	 * @param integer $query_line
	 */

	public function __construct($message = '', $code = 0,
		\Exception $Previous = null, $query_line = -1){

		parent::__construct($message, $code, $Previous);

		if($query_line >= 0){

			$this->_message .= ' on line ' . (int) $query_line;
		}
	}

	/**
	 * Set the line number (of the query) the exception occured on.
	 *
	 * @param integer $query_line
	 *
	 * @return void
	 */

	public function setQueryLine($query_line){

		if($query_line >= 0){

			$this->message .= ' on line ' . (int) $query_line;
		}
	}
}