<?php
/**
 * @author Thijs Putman <thijs@studyportals.com>
 * @copyright © 2017 StudyPortals B.V., all rights reserved.
 */

namespace StudyPortals\SQL;

use StudyPortals\Exception\ExceptionHandler;

/**
 * SQLEngineRW
 * SQL Engine with Reader/Writer support.
 */

class SQLEngineRW extends SQLEngine{

	private $_Reader;
	private $_Writer;

    /**
     * Construct a new Reader/Writer SQLEngine.
     *
     * @param SQLEngine $Reader
     * @param SQLEngine $Writer
     */

	public function __construct(SQLEngine $Reader, SQLEngine $Writer){

		$this->_Reader = $Reader;
		$this->_Writer = $Writer;
	}

    /**
     * Get the Reader-instance.
     *
     * @return SQLEngine
     */

	public function getReader(){

		return $this->_Reader;
	}

    /**
     * Get the Writer-instance.
     *
     * @return SQLEngine
     */

	public function getWriter(){

		return $this->_Writer;
	}

	/**
	 * Send a query to the correct SQLEngine
	 *
	 * <p>SELECT-queries are sent to the Reader-instance, all other queries are
	 * send to the Writer-instance.</p>
	 *
	 * @param string $query
	 * @param boolean $return_set
	 * @param boolean $buffered
	 * @return SQLResult
	 */

	public function query($query, $return_set = false, $buffered = true){

		if(strpos(ltrim($query), 'SELECT ') === 0){

			return $this->_Reader->query($query, $return_set, $buffered);
		}
		else{

			return $this->_Writer->query($query, $return_set, $buffered);
		}
	}

	/**
	 * Get ID of the row created by the last INSERT-query.
	 *
	 * @return integer
	 */

	public function getLastID(){

		return $this->_Writer->getLastID();
	}

	/**
	 * Return number of rows affected by the last INSERT/UPDATE/DELETE-query.
	 *
	 * @return integer
	 */
	public function getAffectedRows(){

		return $this->_Writer->getLastID();
	}

	/**
	 * Use Writer's "native" escape function to escape the provided string.
	 *
	 * @param string $string
	 * @return string
	 */

	public function escapeString($string){

		return $this->_Writer->escapeString($string);
	}

    /**
     * Stub.
     *
     * <p>Include to stay compatible with the SQLEngine interface; there is no
     * need, nor any use-case, for calling <em>SQLEngineRW::_connect()</em>. If
     * you should ever want to do this, call the <em>_connect()</em> method on
     * both the <em>Reader</em> and <em>Writer</em> directly.</p>
     *
     * @return void
     */

	protected function _connect(){

		ExceptionHandler::notice('SQLEngineRW::_connect() not supported');
	}
}
