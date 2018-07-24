<?php

namespace sure\tools;

use \sure\db\SureDBProxyManager;
use \sure\base\SureException;

/**
 * 全局log对象
 * @var object
 */
static $g_logger = null;

/**
 * Sure框架中Log类
 */
class SureLogger {

	/**
	 * 文件路径
	 * @var string
	 */
	public $m_sFilePath;

	/**
	 * 日志信息
	 * @var string
	 */
	public $m_sLogExInfo;

	private $m_sPath;

	private $m_sFileName;

	/**
	 * 构造函数
	 * @param string $sPath     路径
	 * @param string $sFileName 文件名
	 */
	public function __construct($sPath, $sFileName) {
		$this->m_sPath = $sPath;
		$this->m_sFileName = $sFileName;
		$this->init();
	}

	public function init () {

		global $g_dtDataYMD;

		$g_dtDataYMD = date("YmdH");

		global $g_sFileName;
		$sLogFileName = basename($g_sFileName, '.php');
		$this->m_sFilePath = $this->m_sPath.'/'.$sLogFileName.$g_dtDataYMD.".log";

		//创建日志路径
		if (!file_exists($this->m_sPath)) {
			if (!mkdir($this->m_sPath, 0777, true)) {
				throw new SureException("Create directory Failure : {$this->m_sPath}", __LINE__);
			}
		}

		//创建日志文件
		if ( !file_exists( $this->m_sFilePath ) ) {
			if ( !touch( $this->m_sFilePath ) ) {
				throw new SureException( "Create Log File Failure" , __LINE__);
			}
		}

	}

	/**
	 * 写log
	 * @param  string $sFile     文件
	 * @param  int    $iLogLevel log类型
	 * @param  string $sFineLine 行数
	 * @param  string $sMsg      内容
	 */
	public function writeLog($sFile, $sFineLine, $iLogLevel, $sMsg) {

		$sData = date("Y-m-d H:i:s");

		global $g_iType;
		global $g_sIP;
		global $g_sSerialCode;
		
		if (!isset($this->m_sLogExInfo)) {
			
			if ($g_iType == APP) {

				$v = isset($_REQUEST["v"]) != false ? $_REQUEST["v"] : '';
				$i = isset($_REQUEST["i"]) != false ? $_REQUEST["i"] : '';
				$j = isset($_REQUEST["j"]) != false ? $_REQUEST["j"] : '';
				$w = isset($_REQUEST["w"]) != false ? $_REQUEST["w"] : '';
				$t = isset($_REQUEST["t"]) != false ? $_REQUEST["t"] : '';
				$d = isset($_REQUEST["d"]) != false ? $_REQUEST["d"] : '';

				$this->m_sLogExInfo = "[版本号:{$v}][地区:{$i}][经度:{$j}][纬度:{$w}][类型:{$t}][设备号:{$d}][$g_sSerialCode]";
				
			} else {
				$this->m_sLogExInfo = "[IP:{$g_sIP}][$g_sSerialCode]";
			}

		}

		global $g_sCardId;

		if (!empty($g_sCardId) && !strpos($this->m_sLogExInfo, $g_sCardId)) {
			$this->m_sLogExInfo = $this->m_sLogExInfo."[{$g_sCardId}]";
		}

		static $iLogIndex = 0;
		$iLogIndex++;

		$sLog = '['.$sData.']'.'[index '.$iLogIndex."][$sFile][{$iLogLevel}][line:".$sFineLine."]".$this->m_sLogExInfo.$sMsg."\r\n";

		$handle = fopen( $this->m_sFilePath , "a+" );

	    //写日志
	    if( !fwrite( $handle , $sLog ) ) {
			//写日志失败
			fclose($handle);
	    	throw new SureException( "Write Log to file Error.\n" );
	    }

	    //关闭文件
	    fclose($handle);
	}

	/**
	 * 发送邮件告警
	 * @param  string $sMail    邮箱
	 * @param  string $sType    告警类型
	 * @param  string $cContent 内容
	 */
	static public function logWarning ($sMail, $sType, $sContent) {

		$dbCon = SureDBProxyManager::getDBProxyManager(DB_QFQ);

		$iTime = time();

		$sInsertSql = "INSERT INTO dbQfq.tbMailWarning SET `sMail` = '$sMail', `sType` = '$sType', `sContent` = '$sContent', `iTime` = '$iTime'";
		SURE_LOG(__FILE__, __LINE__, LP_INFO, '$sInsertSql ---> ' . $sInsertSql);

		$iInsert = $dbCon->insert($sInsertSql);
		SURE_LOG(__FILE__, __LINE__, LP_INFO, '$iInsert ---> ' . $iInsert);

		if ($iInsert > 0) {
			SURE_LOG(__FILE__, __LINE__, LP_INFO, "---插入数据，成功，共（{$iInsert}）条");
		} else {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "---插入数据，失败---");
		}

	}

	function __destruct () {
	}


}

?>