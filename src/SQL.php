<?php

/**
* @file SQL.php
*
* @author Thijs Putman <thijs@studyportals.eu>
* @author Danko Adamczyk <danko@studyportals.com>
* @copyright © 2004-2009 Thijs Putman, all rights reserved.
* @copyright © 2010-2015 StudyPortals B.V., all rights reserved.
* @version 1.1.1
*/

namespace StudyPortals\SQL;

/**
 * SQL interface.
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */

interface SQL{

	/**
	 * Send a query to the database server.
	 *
	 * <p>When the optional parameter {@link $return_set} is set to <em>true
	 * </em>, this method will always return an instance of {@link
	 * SQLResultSet}, even if only one row is present. If not enabled, this
	 * method will in such cases return an instance of {@link SQLResultRow}.</p>
	 *
	 * <p>When the optional parameter {@link $buffered} is set to <em>false
	 * </em>, the result-set returned should be unbuffered. This will greatly
	 * reduce memory usage, but disables all <em>but</em> forward iterating
	 * over the result-set, as  only the active row is kept in memory. This
	 * option only has effect when a result-set is returned.</p>
	 *
	 * @param string $query
	 * @param boolean $return_set
	 * @param boolean $buffered
	 * @return SQLResult
	 * @throws SQLException
	 */

	public function query($query, $return_set = false, $buffered = true);

	/**
	 * Get ID of the row created by the last INSERT-query.
	 *
	 * @return integer
	 */

	public function getLastID();

	/**
	 * Return number of rows affected by the last INSERT/UPDATE/DELETE-query.
	 *
	 * @return integer
	 */

	public function getAffectedRows();

	/**
	 * Use database's "native" escape function to escape the provided string.
	 *
	 * @param string $string
	 * @return string
	 */

	public function escapeString($string);
}