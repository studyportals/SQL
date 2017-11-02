<?php

/**
 * @file QueryBuilder.php
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @author Rob van den Hout <vdhout@studyportals.eu>
 * @copyright © 2005-2009 Thijs Putman, all rights reserved.
 * @copyright © 2010-2014 StudyPortals B.V., all rights reserved.
 * @version 1.4.1
 */

namespace StudyPortals\SQL;

use StudyPortals\Exception\ExceptionHandler;

/**
 * @class QueryBuilder
 * The QueryBuilder is used to easily build and execute complex SQL queries.
 *
 * @package StudyPortals.Framework
 * @subpackage SQL
 */

class QueryBuilder{

	/**
	 * In MySQL, the "identifier quote character" is a backtick; in ANSI SQL it
	 * is a double-quote. Use this constant to switch between MySQL and "normal"
	 * SQL.
	 *
	 * @var string
	 * @link http://dev.mysql.com/doc/refman/5.1/en/identifiers.html
	 */

	const IDENTIFIER_QUOTE = '`';

	protected $_query = [];
	protected $_parameters = [];

	protected $_values = [];

	/**
	 * Creates a new QueryBuilder.
	 *
	 * @param string $query_string
	 *
	 * @throws InvalidSyntaxException
	 */

	public function __construct($query_string = ''){

		$this->_parseQuery($query_string);
	}

	/**
	 * Add a parameter to the query.
	 *
	 * @param string $name
	 * @param mixed $value
	 */

	public function __set($name, $value){

		try{

			$this->_checkParameter($name, $value);
		}
		catch(QueryBuilderException $e){}

		$this->_values[$name] = $value;
	}

	/**
	 * Check name and value to ensure they are sane (from an SQL perspective).
	 *
	 * <p>This method always returns void; it will throw a {@link
	 * QueryBuilderException} when either the name or value passed in is not
	 * acceptable for its intended use in the SQL query.</p>
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 * @throws QueryBuilderException
	 */

	protected function _checkParameter($name, $value){

		if(!isset($this->_parameters[$name]) || !is_array($this->_parameters[$name])){

			throw new QueryBuilderException("Unable to set parameter '$name',
				unknown parameter specified");
		}

		if(is_resource($value)){

			throw new QueryBuilderException("Unable to set parameter '$name',
				cannot use a resource as value");
		}

		if($this->_parameters[$name]['identifier']){

			if(!is_scalar($value)){

				throw new QueryBuilderException("Unable to set parameter '$name',
					identifier should be a scalar");
			}
		}

		if(!empty($this->_parameters[$name]['force-type'])){

			$forced_type = $this->_parameters[$name]['force-type'];

			if(!$this->_checkType($value, $forced_type)){

				throw new QueryBuilderException("Unable to set parameter '$name',
					it (or one of its elements) is not of type $forced_type");
			}
		}
	}

	/**
	 * Check the type of the provided value.
	 *
	 * <p>Checks whether the provided {@link $value} is of the provided {@link
	 * $type}. When {@link $value} is an array the check is performed on all
	 * elements of the array. The check fails if any of the array-elements (or
	 * the value itself) are not of the specified type.</p>
	 *
	 * @param mixed $value
	 * @param string $type [int|float|bool]
	 * @return boolean
	 */

	protected function _checkType($value, $type){

		// Integers and floats are allowed be NULL

		if($value === null && $type != 'bool'){

			return true;
		}

		switch($type){

			case 'int':

				$reducer = function($result, $value){

					if(!is_int($value)){

						$result = false;
					}

					return $result;
				};

			break;

			case 'float':

				$reducer = function($result, $value){

					if(!is_float($value)){

						$result = false;
					}

					return $result;
				};

			break;

			case 'bool':

				$reducer = function($result, $value){

					if(!is_bool($value)){

						$result = false;
					}

					return $result;
				};

			break;

			default:

				ExceptionHandler::notice("Invalid property type $type");

				return false;
		}

		if(is_array($value)){

			$result = array_reduce($value, $reducer, true);
		}
		else{

			$result = $reducer(true, $value);
		}

		return $result;
	}

	/**
	 * Get a parameter value.
	 *
	 * @param string $name
	 * @return mixed
	 */

	public function __get($name){

		if(isset($this->_values[$name])){

			assert('is_array($this->_parameters[$name])');

			return $this->_values[$name];
		}

		ExceptionHandler::notice("Query-parameter '$name' does not exist");

		return null;
	}

	/**
	 * Reset the QueryBuilder to its initial state.
	 *
	 * <p>This method clears all values previously set in this instance of the
	 * QueryBuilder.</p>
	 *
	 * @return void
	 */

	public function reset(){

		$this->_values = [];
	}

	/**
	 * Append a string to the query string of the QueryBuilder.
	 *
	 * @param string $query_string
	 *
	 * @throws InvalidSyntaxException
	 * @return void
	 */

	public function appendQueryString($query_string){

		ExceptionHandler::notice('Deprecated: use append()');
		$this->append($query_string);
	}

	/**
	 * Append a string to the query string of the QueryBuilder.
	 *
	 * @param string $query_string
	 *
	 * @throws InvalidSyntaxException
	 * @return void
	 */

	public function append($query_string){

		if(trim($query_string) == '') return;

		$query_string = " $query_string";

		$this->_parseQuery($query_string, true);
	}

	/**
	 * Parse a query string for QueryBuilder markers.
	 *
	 * <p>Fills the QueryBuilder::_$query property with the parsed query and
	 * QueryBuilder::$_parameters with the parameter markers found in the query.</p>
	 *
	 * <p>When {@link $append} is set to <i>true</i>, the parsed query is appended
	 * to the information present in the instance; otherwise the instance is
	 * cleared of any previous query information.</p>
	 *
	 * @param string $query
	 * @param boolean $append
	 * @return void
	 * @throws InvalidSyntaxException
	 */

	protected function _parseQuery($query, $append = false){

		if(!$append){

			$this->reset();

			$this->_query = [];
			$this->_parameters = [];
		}

		$query = trim($query);
		$query = "$query ";
		$length = strlen($query);
		$line = 0;

		$state = $empty_state = [
			'marker' => '',
			'in_marker' => false,
			'brace' => '',
			'in_brace' => false];

		$query_text = '';
		$result = [];

		// When appending to the existing query, a space should be included

		if($append) $this->_query[] = 'T  ';

		for($i = 0; $i < $length; $i++){

			switch($query[$i]){

				// Parameter markers

				case '@':
				case '#':
				case '$':

					// End

					if($state['in_marker']){

						if($state['in_marker'] != $query[$i]){

							throw new InvalidSyntaxException('Marker mismatch ' .
								"expected '{$state['in_marker']}'
								found '{$query[$i]}'", 0, null, $line);
						}

						try{

							$result = $this->_parseMarker($state['in_marker'],
							$state['marker'], $state['brace']);
						}
						catch(InvalidSyntaxException $e){

							$e->setQueryLine($line);
							throw $e;
						}

						$state = $empty_state;
					}

					// Start

					else{

						$state['in_marker'] = $query[$i];

						$this->_query[] = "T $query_text";
						$query_text = '';
					}

					break;

					// Type-hint start

				case '[':

					if($state['in_marker']){

						if($state['in_marker'] == '#'){

							throw new InvalidSyntaxException('Marker \'#\' does
								not support type-hints', 0, null, $line);
						}
						elseif($state['in_brace'] || $state['brace'] != ''){

							throw new InvalidSyntaxException('Unexpected type-hint
								encountered', 0, null, $line);
						}

						$state['in_brace'] = true;
					}

					else $query_text .= $query[$i];

					break;

					// Type-hint end

				case ']':

					if($state['in_marker']){

						if(!$state['in_brace']){

							throw new InvalidSyntaxException('Unexpected ]
								encountered', 0, null, $line);
						}

						$state['in_brace'] = false;
					}

					else $query_text .= $query[$i];

					break;

					// Token separators

				/** @noinspection PhpMissingBreakStatementInspection */
				case "\n":

					$line++;

				case "\r":
				case "\t":
				case ' ':
				case ')':
				case '(':
				case ',':

					if($state['in_marker']){

						// Reconstruct the stuff we just "parsed" away

						$query_text .= $state['in_marker'];

						if(!empty($state['brace'])){

							$query_text .= "[{$state['brace']}]";
						}

						$query_text .= $state['marker'];

						$state = $empty_state;
					}

					$query_text .= $query[$i];

					break;

					// Default

				default:

					if($state['in_marker']){

						if($state['in_brace']){

							$state['brace'] .= $query[$i];
						}
						else{

							$state['marker'] .= $query[$i];
						}
					}
					else{

						$query_text .= $query[$i];
					}
			}

			// Process parameter

			if(!empty($result)){

				if(isset($this->_parameters[$result['parameter']])){

					assert('$this->_parameters[$result[\'parameter\']][\'force-type\'] == $result[\'force-type\']');

					$uid = $this->_parameters[$result['parameter']]['uid'];
				}
				else{

					$uid = $result['uid'];
				}

				$this->_query[] = "P $uid";

				$this->_parameters[$result['parameter']] = [
					'uid' => $uid,
					'force-type' => $result['force-type'],
					'identifier' => $result['identifier']];

				$result = [];
			}
		}

		// Collect final token (to force this is always text, a space is appended)

		if(trim($query_text) != ''){

			$this->_query[] = "T $query_text";
		}
	}

	/**
	 * Parse the contents of a QueryBuilder marker.
	 *
	 * @param string $marker
	 * @param string $parameter
	 * @param string $brace
	 * @return array
	 * @throws InvalidSyntaxException
	 */

	protected function _parseMarker($marker, $parameter, $brace){

		$brace = strtolower($brace);

		$result = [
			'uid' => md5(uniqid($parameter, true)),
			'force-type' => null,
			'identifier' => false
		];

		// Integer quick-marker (#parameter# => @[int]parameter@)

		if($marker == '#'){

			assert('$brace == \'\'');
			$brace = 'int';
		}

		// Identifier quick-marker ($parameter$ => @[ident]parameter@)

		elseif($marker == '$'){

			assert('$brace == \'\'');
			$brace = 'identifier';
		}

		switch($brace){

			case 'int':
			case 'float':
			case 'bool':

				$result['force-type'] = $brace;

				break;

			case 'ident':
			case 'identifier':

				$result['identifier'] = true;

				break;

			default:

				if($brace != ''){

					throw new InvalidSyntaxException("Invalid type-hint '$brace' encountered");
				}
		}

		if($parameter == ''){

			throw new InvalidSyntaxException('Invalid parameter name');
		}

		$result['parameter'] = $parameter;

		return $result;
	}

	/**
	 * Composes the query present in this QueryBuilder instance.
	 *
	 * @param SQL $SQL
	 * @return string
	 * @throws QueryBuilderException
	 */

	public function compose(SQL $SQL){

		$tokens = [];
		$query_string = '';

		foreach($this->_values as $name => $value){

			// Throws an exception on invalid name/value

			$this->_checkParameter($name, $value);

			assert('is_array($this->_parameters[$name])');

			$prepared_value = $this->_prepareValue($value, $SQL,
				$this->_parameters[$name]['identifier']);
			$tokens[$this->_parameters[$name]['uid']] = $prepared_value;
		}

		foreach($this->_query as $query_part){

			list($token, $value) = explode(' ', $query_part, 2);

			switch($token){

				case 'P':

					if(isset($tokens[$value])){

						$query_string .= $tokens[$value];
					}

					break;

				case 'T':

					$query_string .= $value;

					break;

				default: ExceptionHandler::notice("Invalid token $token");
			}
		}

		return $query_string;
	}

	/**
	 * Prepares a variable to be inserted into an SQL Query.
	 *
	 * @param mixed $value
	 * @param SQL $SQL
	 * @param boolean $identifier Use the value as an SQL "identifier"
	 * @return string
	 * @throws QueryBuilderException
	 */

	protected function _prepareValue($value, SQL $SQL, $identifier = false){

		// Identifier should be string (purely a sanity-check, enforced elsewhere)

		assert('!(!is_string($value) && $identifier)');

		// String

		if(is_string($value)){

			// NULL

			if(strtoupper($value) == 'NULL'){

				$value = 'NULL';
			}

			// Identifier string

			elseif($identifier){

				/*
				 * Surround the identifier with "identifier quote characters"
				 * (either backtick or double quote, depending on SQL mode) and
				 * escape all further occurences of said character with itself.
				 */

				$quote = self::IDENTIFIER_QUOTE;
				$value = str_replace($quote, $quote . $quote, $value);

				$value = $quote . $value . $quote;
			}

			// Regular string

			else{

				$value = '\'' . $SQL->escapeString($value) . '\'';
			}
		}

		// Integer

		elseif(is_int($value)){

			$value = (string) $value;
		}

		// Floating-point

		elseif(is_float($value)){

			$value = (string) $value;

			// Ensure no (other) invalid characters are present

			assert('!preg_match(\'/[^\d.e+\-]/i\', $value)');
			$value = preg_replace('/[^\d.e+\-]/i', '', $value);
		}

		// Boolean (interpreted as INT(1))

		elseif(is_bool($value)){

			// Don't convert directly to string, (int) false === ''!

			$value = (string) ((int) $value);
		}

		// NULL

		elseif($value === null){

			$value = 'NULL';
		}

		// Array (interpret either as SET or as a list of ID's)

		elseif(is_array($value)){

			$is_string = null;

			$callback = function($value) use($SQL, &$is_string){

				// Values should be either string or integer

				assert('is_string($value) || is_int($value)');
				if(!is_int($value)) $value = (string) $value;

				// All values should be of the same type, no "mixed bags"

				assert('$is_string === null || is_string($value) === $is_string');

				if(is_string($value)) $is_string = true;

				return $SQL->escapeString($value);
			};

			array_map($callback, $value);

			$value = implode(',', $value);

			if($is_string) $value = "'{$value}'";
		}

		// Object

		elseif(is_object($value)){

			$value = '\'' . $SQL->escapeString(serialize($value)) . '\'';
		}

		// Invalid

		else{

			throw new QueryBuilderException('Data of type "' . gettype($value)
				. '" cannot be used in an SQL-query');
		}

		return $value;
	}

	/**
	 * Execute the query and reset the object's state.
	 *
	 * <p>For details on the optional arguments to this method, see the
	 * documentation of the {@link SQL::Query()} method.</p>
	 *
	 * @param SQL $SQL
	 * @param boolean $return_set
	 * @param boolean $buffered
	 *
	 * @throws QueryBuilderException
	 * @return SQLResult
	 * @see SQL::Query()
	 */

	public function execute(SQL $SQL, $return_set = false, $buffered = true){

		$query_string = $this->compose($SQL);

		$this->reset();

		return $SQL->Query($query_string, $return_set, $buffered);
	}
}