<?php

namespace sure\db;

use \sure\base\SureException;

/**
 * Sure框架中处理DB
 */
class SureDBMysql {
	
	/**
	 * db对象
	 * @var object
	 */
	private $m_dbInstance;

	/**
	 * 构造函数
	 * @param string  $host     远程地址
	 * @param string  $user     用户
	 * @param string  $pwd      密码
	 * @param string  $database db名
	 * @param integer $port     端口
	 */
	function __construct($host = '', $user = '', $pwd = '', $database = '', $port = 3306) {

		if($database == '') {
			$database = 'dbTest';
		}

		if ($user == '') {
			$user = 'root';
		}

		if ($pwd == '') {
			$pwd  = '123456';
		}

		if ($host == '') {
			$host = '127.0.0.1';
		}

		if ($port == '') {
			$port = 3306;
		}

		$this->m_dbInstance = @mysql_connect("{$host}:{$port}", $user, $pwd, true);

		mysql_query("set names utf8");

		if (!$this->m_dbInstance) {
			throw new SureException("Create Connect Failure".mysql_error() , 0 );
		}

		if (!mysql_select_db($database, $this->m_dbInstance)) {
			throw new SureException("Select db Failure".mysql_error() , 0 );
		}

	}

	/**
	 * 过滤sql注入
	 * @param  string &$sVal 字符串
	 */
	private function check_input(&$sVal) {
		
	    if (!get_magic_quotes_gpc()) {
	        $sVal = stripslashes($sVal);
	    }

	    if (!is_numeric($sVal)) {
	        $sVal = @mysql_escape_string($sVal);
	        //$sVal = str_replace("_", "/_", $sVal);
			//$sVal = str_replace("%", "/%", $sVal);
			//$sVal = htmlspecialchars($sVal);
	    }
	}

	/**
	 * 查询
	 * @param  string $sql     sql语句
	 * @param  array  &$result 结果
	 * @return int             行数
	 */
	public function query($sql, &$result) {

		/*
		if (!preg_match("/^select\s([A-Za-z,`\s.])+\sfrom/i", $sql)) {
			throw new SureException("检查sql是否出现问题，是否出现*", 0);
		}*/

		$this->checkStr($sql);

		$result = array();
		$selArr = mysql_query($sql, $this->m_dbInstance);

		if ( ! $selArr ) {
			return false;
		}

		while ($row = mysql_fetch_array($selArr, MYSQL_ASSOC)) {

			foreach (array_keys($row) as $sKey) {
				$this->check_input($row[$sKey]);
			}			

			array_push($result, $row);
		}

		$num_rows = count($result);

		mysql_free_result( $selArr );

		return $num_rows;

	}

	/**
	 * 插入
	 * @param  string $sql sql语句
	 * @return int  	   影响行数     
	 */
	public function insert($sql) {
		mysql_query($sql, $this->m_dbInstance);
		return mysql_affected_rows($this->m_dbInstance);
	}

	/**
	 * 插入，并获得主键ID
	 * @param  string $sql sql语句
	 * @return int         主键id
	 */
	public function insertForPK($sql) {
		mysql_query($sql, $this->m_dbInstance);
		return mysql_insert_id($this->m_dbInstance);
	}

	/**
	 * 删除
	 * @param  string $sql sql语句
	 * @return int         影响行数
	 */
	public function delete($sql) {
		 mysql_query($sql, $this->m_dbInstance);
		 return mysql_affected_rows($this->m_dbInstance);
	}

	/**
	 * 更新
	 * @param  string $sql sql语句
	 * @return int         影响行数
	 */
	public function update($sql) {
		 mysql_query($sql, $this->m_dbInstance);

		 $iRet = mysql_affected_rows($this->m_dbInstance);

		 if ($iRet == 0 && $this->getErrorNo() != 0) {
		 	$iRet = -1;
		 }

		 return $iRet;
	}

	/**
	 * 对sql语句进行检查
	 * @param  string $sql sql语句
	 */
	public function checkStr($sql) {

    	$sWhere = stristr($sql, 'WHERE');

    	if (empty($sWhere)) {
    		return;
    	}

    	$sWhere = stristr($sql, "'");

    	if (!empty($sWhere)) {
    		return;
    	}

    	throw new SureException("sql query error:[{$sql}] \n");
	}

	/**
	 * 获取错误码
	 * @return int 0为正常，其余失败
	 */
	public function getErrorNo () {
		return mysql_errno($this->m_dbInstance);
	}

	/**
	 * 获取mysql错误信息
	 * @return string 错误信息
	 */
	public function getErrorMsg () {
		return mysql_error($this->m_dbInstance);
	}

	/**
	 * 选择数据库
	 * @param  boolean 是否连接数据库成功
	 */
	public function selectDatabase ($sDatabase) {
		return mysql_select_db($sDatabase, $this->m_dbInstance);
	}

	/**
	 * 析构函数
	 */
	function __destruct() {

	    if ($this->m_dbInstance) {
            mysql_close($this->m_dbInstance);
            unset($this->m_dbInstance);
            $this->m_dbInstance = null;
	    }

	}

}

