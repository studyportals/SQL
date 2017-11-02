<?php
/**
 * @file UpdateQueryBuilder.php
 *
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @version 1.0.0
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

/**
 * @class UpdateQueryBuilder
 * Streamlines the creation of UPDATE queries using the QueryBuilder.
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */
class UpdateQueryBuilder extends QueryBuilder{

	protected $_table;
	protected $_fields = [];
	protected $_insert;
	/** @noinspection PhpMissingParentConstructorInspection */

	/**
	 * Create a new UPDATE type of query.
	 *
	 * <p> Note that the {@link $table} parameter is <b>not</b> filtered to prevent
	 * SQL-injections! This is table name and it is assumed to "clean". If this
	 * value is received from an external source, apply filtering <i>before</i>
	 * passing it into this method.</p>
	 *
	 * <p>You can use the same syntax for the `condition` argument as you can
	 * for the `query` argument of the normal QueryBuilder class.</p>
	 *
	 * <p>Leaving $condition empty will trigger an insert query.</p>
	 *
	 * @param string $table
	 * @param string $condition WHERE-statement for the update query
	 *
	 * @throws InvalidSyntaxException
	 */

	public function __construct($table, $condition = ''){

		$this->_table = $table;
		$this->_insert = empty($condition);

		$this->_parseQuery($condition);
	}

	/**
	 * Add a field update to the query.
	 *
	 * <p>The {@link $value} parameter is escaped to prevent SQL-injections.
	 * Note that the {@link $field} parameter is <b>not</b> filtered! This is
	 * table field/column name and is assumed to "clean". If this value is also
	 * received from an external source, apply filtering <i>before</i> passing
	 * it into this method.</p>
	 *
	 * @param string $field
	 * @param mixed $value
	 */

	public function addField($field, $value){

		$this->_fields[$field] = $value;
	}

	/**
	 * Composes a query from the QueryBuilder.
	 *
	 * <p>Extends the base method by also parsing the update fields
	 * into the query.</p>
	 *
	 * @param SQL $SQL
	 *
	 * @throws \StudyPortals\SQL\QueryBuilderException
	 * @return string
	 */

	public function compose(SQL $SQL){

		if($this->_insert){

			return $this->_composeInsertQuery($SQL);
		}
		else{

			return $this->_composeUpdateQuery($SQL);
		}
	}

	/**
	 * Composes an insert query with an on duplicate key update part.
	 *
	 * @param SQL $SQL
	 *
	 * @return string
	 * @throws QueryBuilderException
	 */

	protected function _composeInsertQuery(SQL $SQL){

		$query_pre = "INSERT INTO `$this->_table`";

		if(empty($this->_fields)){

			throw new QueryBuilderException(
				'No fields specified for SQL query'
			);
		}

		$fields = [];
		$updates = [];

		foreach($this->_fields as $field => $value){

			$prepared = $this->_prepareValue($value, $SQL);

			$fields[$field] = $prepared;
			$updates[] = "$field = $prepared";
		}

		$query_fields = '(' . implode(', ', array_keys($fields)) . ')';
		$query_values = '(' . implode(', ', $fields) . ')';
		$query_updates = implode(', ', $updates);

		return "{$query_pre}
			{$query_fields} VALUES {$query_values}
			ON DUPLICATE KEY UPDATE {$query_updates}
		";
	}

	/**
	 * Composes an update query.
	 *
	 * @param SQL $SQL
	 *
	 * @return string
	 * @throws QueryBuilderException
	 */

	protected function _composeUpdateQuery(SQL $SQL){

		$condition = parent::compose($SQL);

		$query_pre = "UPDATE `$this->_table` SET ";
		$query_post = " WHERE $condition";

		$fields = [];

		if(empty($this->_fields)){

			throw new QueryBuilderException(
				'No fields specified for SQL query'
			);
		}

		foreach($this->_fields as $field => $value){

			$fields[] = "$field = " . $this->_prepareValue($value, $SQL);
		}

		return $query_pre . implode(', ', $fields) . $query_post;
	}
}