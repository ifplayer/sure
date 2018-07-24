<?php

namespace sure\db;

use \sure\base\SureException;

/**
 * Sure框架中处理DB
 */
class SureDBMysqli {
	
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
			$database = 'dbQfq';
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

		$this->m_dbInstance = new \mysqli("{$host}:{$port}", $user, $pwd, $database);

		if ($this->m_dbInstance->connect_errno !== 0) {
    		throw new SureException("Create Connect Failure".$this->m_dbInstance->connect_error, 0);
		}

		if (!$this->m_dbInstance->set_charset("utf8")) {
			throw new SureException("设置mysql字符集失败".$this->m_dbInstance->connect_error, 0);
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
		
		$ret = $this->m_dbInstance->query($sql);

		if ($ret == false) {
			return -1;
		}

		while ($row = $ret->fetch_array(MYSQLI_ASSOC)) {

			foreach (array_keys($row) as $sKey) {
				$this->check_input($row[$sKey]);
			}		

			array_push($result, $row);
		}

		$ret->free();

		return count($result);

	}

	/**
	 * 插入
	 * @param  string $sql sql语句
	 * @return int  	   影响行数     
	 */
	public function insert($sql) {
		
		$result = $this->m_dbInstance->query($sql);

		if ($result === false) {
			SURE_LOG(__FILE__, __LINE__, LP_DEBUG, $sql.' --> 执行失败'.$this->m_dbInstance->connect_error);
			return -1;
		}

		$iRet = $this->m_dbInstance->affected_rows;
		return $iRet;

	}

	/**
	 * 插入，并获得主键ID
	 * @param  string $sql sql语句
	 * @return int         主键id
	 */
	public function insertForPK($sql) {
		$result = $this->m_dbInstance->query($sql);

		if ($result === false) {
			SURE_LOG(__FILE__, __LINE__, LP_DEBUG, $sql.' --> 执行失败'.$this->m_dbInstance->connect_error);
			return -1;
		}

		$iRet = $this->m_dbInstance->insert_id;
		return $iRet;
	}

	/**
	 * 删除
	 * @param  string $sql sql语句
	 * @return int         影响行数
	 */
	public function delete($sql) {
		
		$result = $this->m_dbInstance->query($sql);

		if ($result === false) {
			SURE_LOG(__FILE__, __LINE__, LP_DEBUG, $sql.' --> 执行失败'.$this->m_dbInstance->connect_error);
			return -1;
		}

		$iRet = $this->m_dbInstance->affected_rows;
		return $iRet;
	}

	/**
	 * 更新
	 * @param  string $sql sql语句
	 * @return int         影响行数
	 */
	public function update($sql) {
		 
		$result = $this->m_dbInstance->query($sql);

		if ($result === false) {
			SURE_LOG(__FILE__, __LINE__, LP_DEBUG, $sql.' --> 执行失败'.$this->m_dbInstance->connect_error);
			return -1;
		}

		$iRet = $this->m_dbInstance->affected_rows;
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
		return $this->m_dbInstance->connect_errno;
	}

	/**
	 * 获取mysql错误信息
	 * @return string 错误信息
	 */
	public function getErrorMsg () {
		return $this->m_dbInstance->connect_error;
	}

	/**
	 * 选择数据库
	 * @param  boolean 是否连接数据库成功
	 */
	public function selectDatabase ($sDatabase) {
		
		$bRet = $this->m_dbInstance->select_db($sDatabase);

		if ($result === false) {
			SURE_LOG(__FILE__, __LINE__, LP_DEBUG, "数据库选择失败".$this->m_dbInstance->connect_error);
			return -1;
		}

		return $bRet;

	}

	/**
	 * 析构函数
	 */
	function __destruct() {

	    if ($this->m_dbInstance) {
            $this->m_dbInstance->close();
            unset($this->m_dbInstance);
            $this->m_dbInstance = null;
	    }

	}

}

