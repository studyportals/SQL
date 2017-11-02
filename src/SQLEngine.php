<?php
/**
 * @file SQLEngine.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

use StudyPortals\Exception\ExceptionHandler;
use StudyPortals\Exception\Silenced;

/**
 * SQLEngine.
 *
 * @property string $database
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
abstract class SQLEngine implements SQL, Silenced{

	protected $_database;

	/**
	 * Returns a dynamic property.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */

	public final function __get($name){

		switch($name){

			case 'database':

				return $this->_database;

				break;

			default:

				ExceptionHandler::notice("Invalid property name '$name'");

				return null;
		}
	}

	/**
	 * Apply basic formatting/cleanup to SQL queries.
	 *
	 * <p>This method can be used to prepare SQL statements for human-readable
	 * output. It basically attempts to remove any stray tabs and newlines
	 * introduced by writing SQL queries as multi-line strings inside PHP.</p>
	 *
	 * <p><strong>Note:</strong> This method purely serves a debugging purpose,
	 * it should not be used to format SQL queries intended to be sent to the
	 * database server.</p>
	 *
	 * @param string $sql
	 *
	 * @return string
	 */

	public static function formatQuery($sql){

		$lines = explode(PHP_EOL, $sql);
		$result = [];

		$prev_tabs = 0;
		$insert_tabs = 0;

		foreach($lines as $key => $line){

			$tab_stripped = ltrim($line, "\t");
			$tab_count = strlen($line) - strlen($tab_stripped);

			$line = trim($line);

			if(empty($line)){

				continue;
			}

			// Change indentation level

			if($tab_count > $prev_tabs){

				++$insert_tabs;
			}
			elseif($tab_count < $prev_tabs){

				--$insert_tabs;
			}

			// Exceptional cases (unbalanced tabs; first line)

			if($insert_tabs < 0 || $key == 0 && $tab_count == 0){

				$insert_tabs = 0;
			}

			$result[] = str_repeat("\t", $insert_tabs) . $line;

			$prev_tabs = $tab_count;
		}

		return implode(PHP_EOL, $result);
	}

	/**
	 * Establishes the actual database connection.
	 *
	 * @return void
	 * @throws ConnectionException
	 */

	abstract protected function _connect();
}