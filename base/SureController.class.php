<?php

use \sure\base\SureException;
use \sure\tools\SureAccessLimit;
use \sure\tools\SureRegex;

/**
 * Controller 基类
 */
class SureController {

	/**
	 * 类型
	 * @var int
	 */
	public $m_iType = WEB;

	/**
	 * 用户卡号
	 * @var string
	 */
	public $m_sCardId = '';

	/**
	 * 用户的密钥
	 * @var string
	 */
	public $m_sUserKey = '';

	/**
	 * log
	 * @var object
	 */
	public $m_log;

	/**
	 * base action 初始化 
	 */
	public function init() {

		spl_autoload_register(array($this, 'SureAutoLoad'));

		// 设置流水号
		$this->setSerialCode();

		$this->m_arrParams = array();
		$this->initAction();

		global $g_sLogPath;
		$g_sLogPath = $this->m_sFilePath;

		global $g_iType;
		$g_iType = $this->m_iType;

		global $g_sIP;

		if ($this->m_iType == WEB || $this->m_iType == WEB_JSON) {
			session_start();
			$g_sIP = $this->getUserIp();

			$sHttp = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

			if (!empty($sHttp)) {
				$sHttp = "http://".$sHttp;
				$this->log(__FILE__, __LINE__, LP_INFO, '----- '.$sHttp.' -----');
			}
		}

		$this->m_arrParams['l'] = isset($_REQUEST['l']) ? $_REQUEST['l'] : 'ch';//默认简体中文
		$sAction = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'indexAction';

		$this->log(__FILE__, __LINE__, LP_INFO, '-----'.$sAction.' start-----');

		if (!isset($_REQUEST['action'])) {
			$this->indexAction();
			return;
		}

		$sMethod = $_REQUEST['action'].'Action';

		if (method_exists($this, $sMethod)) {
			call_user_func(array($this, $sMethod));
		} else {
			throw new SureException("找不到Action");
		}
	}
	
	/**
	 * 主action入口
	 */
	public function startAction() {

		try{
			// 初始化
			$this->init();
		} catch(SureException $e) {
			$this->log(__FILE__, __LINE__, LP_ERROR, '主函数出错----->'.$e);
			$this->echoRet(-10, '网络繁忙，稍后重试');
		}

	}

	/**
	 * 输出结果
	 * @param  int 		$retCode  结果码
	 * @param  string 	$sMsg     提示语
	 * @param  object 	$oRet     对象
	 * @param  string 	$sRetName json对象名
	 */
	function echoRet($retCode, $sMsg, $oRet = '{}', $sRetName = 'retInfo') {

		if (!is_numeric($retCode)) {
			$this->log(__FILE__, __LINE__, LP_ERROR, 'retCode 不是数字');
			exit();
		}

		$sRetNameValue = '';

		if ($this->m_iType == WEB) {
			$sRetNameValue = "var $sRetName = ";
		}

		if ($sRetName == '') {
			$sRetNameValue = "";
		}

		global $g_sSerialCode;

		$sMsg = str_replace("\'", "'", $sMsg);
		$oRet = str_replace(array("\\'", "\'", '/_', '/%'), array("'", "'", '_', '%'), $oRet);
		
		$retInfo =  "{$sRetNameValue} {\"retCode\":\"{$retCode}\", \"retMsg\":\"{$sMsg}\", \"oRet\":{$oRet}, \"sSerialCode\":\"{$g_sSerialCode}\" }";
	    
	    // 输出考虑跨域 设置了主域
		if (!empty($_POST['method_post']) && $this->m_iType == WEB) {
			$retInfo =  "<script type=\"text/javascript\"> window.name = '{$sRetName} = {\"retCode\":{$retCode}, \"retMsg\":\"{$sMsg}\", \"oRet\":{$oRet}, \"sSerialCode\":\"{$g_sSerialCode}\"};'</script>";
		} else if ($this->m_iType == APP) {
			$oRet = str_replace('&quot;', '\"', $oRet);
			$retInfo =  "{$sRetNameValue} {\"retCode\":\"{$retCode}\", \"retMsg\":\"{$sMsg}\", \"oRet\":{$oRet}, \"sSerialCode\":\"{$g_sSerialCode}\" }";
		}

	    echo $retInfo;

	    SURE_LOG(__FILE__, __LINE__, LP_INFO, "------------------");

	    $this->log(__FILE__, __LINE__, LP_INFO, '输出----->'.$retInfo);
	    $this->log(__FILE__, __LINE__, LP_INFO, "-----{$g_sSerialCode} end-----");

	    if ($this->m_iType == MSG) {
	    	$this->log(__FILE__, __LINE__, LP_INFO, '即将return');
	    	return;
	    }

	    exit;
	}

	/**
	 * 过滤sql注入
	 * @param  string $value 字符串
	 * @return string        过滤完成的字符串
	 * @return boolean       是否用于insert
	 */
	private function check_input($value, $bInsert = false) {
		
	    if ( !get_magic_quotes_gpc() ) {
	        $value = stripslashes( $value );
	    }

	    if ( ! is_numeric( $value ) ) {
	        $value =  @mysql_escape_string( $value );
	    }

	    if (!$bInsert) {
			$value = str_replace("%", "/%", $value);
	    }

	    $value = htmlspecialchars($value);

	    return $value;
	}

	/**
	 * 处理前端传入的字符串
	 * @param  array   $arrParams  		需要处理的字符串数组
	 * @param  boolean $bInsert  		是否用于插入
	 * @param  boolean $bCheckSQL  		是否过滤sql注入
	 * @param  boolean $bCheckEmpty  	是否过滤空字符串
	 * @return boolean             		是否出来成功
	 */
	public function checkParamsForString($arrParams, $bInsert = false, $bCheckSQL = true, $bCheckEmpty = true) {
		$iCount = count( $arrParams );

		for ($i = 0; $i < $iCount ; $i++) {

			$sParams = $arrParams[$i];

			if (isset($this->m_arrParams[$sParams])) { //已存在不添加
				continue;
			}

			if (isset($_REQUEST[$sParams])) {

				if ($bCheckSQL) {
					$this->m_arrParams[$sParams] = $this->check_input($_REQUEST[$sParams], $bInsert);
				} else {
					$this->m_arrParams[$sParams] = htmlspecialchars($_REQUEST[$sParams]);
				}

				if ($bCheckEmpty == true && $this->m_arrParams[$sParams] === '') {
					$this->log(__FILE__, __LINE__, LP_ERROR, 'checkParamsForString 过滤完为空字符串----->'.$sParams);
					return false;
				}

			} else {
				$this->log(__FILE__, __LINE__, LP_ERROR, 'checkParamsForString 没有传递----->'.$sParams);
				return false;
			}

		}

		return true;
	}

	/**
	 * 判断是否合法int类型
	 * @param  array 	$arrParams 检查需要的键
	 * @return boolean  	       是否包含不合法的值
	 */
	public function checkParamsForInt( $arrParams ) {
		$iCount = count( $arrParams );

		for ($i = 0 ; $i < $iCount ; $i++) { 

			$sParams = $arrParams[$i];

			if (isset($this->m_arrParams[$sParams])) { //已存在不添加
				continue;
			}

			if (isset($_REQUEST[$sParams]) && is_numeric($_REQUEST[$sParams])) {
				$this->m_arrParams[$sParams] = (int)$_REQUEST[$sParams];
			} else {
				$this->log(__FILE__, __LINE__, LP_ERROR, 'checkParamsForInt 存在问题----->'.$sParams);
				return false;
			}

		}

		return true;
	}

	/**
	 * 判断是否合法float类型
	 * @param  array 	$arrParams 检查需要的键
	 * @return boolean  	       是否包含不合法的值
	 */
	public function checkParamsForFloat($arrParams) {

		$iCount = count($arrParams);

		for ($i = 0; $i < $iCount; $i++) { 

			$sParams = $arrParams[$i];

			if (isset($this->m_arrParams[$sParams])) { //已存在不添加
				continue;
			}

			if (isset($_REQUEST[$sParams])) {
				$this->m_arrParams[$sParams] = floatval($_REQUEST[$sParams]);
			} else {
				$this->log(__FILE__, __LINE__, LP_ERROR, 'checkParamsForFloat 存在问题----->'.$sParams);
				return false;
			}

		}

		return true;
	}

	/**
	 * 分页处理
	 * @param  string  $tbName       表名
	 * @param  array   $arrSearchKey 需要查询的键名
	 * @param  int     $iPageNow     当前的页码
	 * @param  array   $arrRetList   结果数组
	 * @param  int     $iCount       总数
	 * @param  int     $iPageCount   分页总数
	 * @param  int     $iPages       每页的数量
	 * @param  string  $sWhere       查询条件语句
	 * @param  string  $tbKey        表的主键
	 * @return boolean               分页是否成功
	 */
	function getPagesRet($tbName, $arrSearchKey, $iPageNow, & $arrRetList, & $iCount, & $iPageCount, $iPages = 10, $sWhere = '', $tbKey = 'id') {
		
		if (!isset($iCount) || $iCount == 0) {
			$sql = " SELECT COUNT({$tbKey}) AS count FROM {$tbName} {$sWhere}";
			$this->log(__FILE__, __LINE__, LP_INFO, "{$sql}");
			$this->m_conDB->query( $sql , $arrCountRet );

			if( is_array( $arrCountRet ) && count( $arrCountRet ) == 0 ) {
				$this->log(__FILE__, __LINE__, LP_INFO, "执行sql失败 {$sql}");
				return false;
			}

	 		$iCount = (int)$arrCountRet[0]["count"]; //总数
		}

		$iPageCount = (int)($iCount / $iPages); 

		if ($iCount % $iPages != 0) {
			++$iPageCount;
		}

		$iOffset = ($iPageNow - 1) * $iPages;

		if ($iOffset < 1) {
			$iOffset = 0;
		}

		$sSearchKey = '';

		for ($i=0; $i < count($arrSearchKey); $i++) {
			$sSearchKey .= "`".$arrSearchKey[$i]."`,";
		}

		$sSearchKey = substr($sSearchKey, 0, strlen($sSearchKey) - 1);

 		$sql = "SELECT {$sSearchKey} FROM {$tbName} {$sWhere}";
 		$sql .=" LIMIT {$iOffset} , ".$iPages;
 		$this->log(__FILE__, __LINE__, LP_INFO, "{$sql}");
		$iRet = $this->m_conDB->query($sql, $arrRetList);

		if($iRet < 0) {
			$this->log(__FILE__, __LINE__, LP_ERROR, "执行失败： {$sql}");
			return false;
		}
		
		return true;
	}

	/**
	 * 输出文本
	 * @param  string $sMsg 需要输入的文字
	 */
	public function echoText($sMsg) {
		echo $sMsg;
		exit();
	}

	/**
	 * 打印log
	 * @param  string $sFile     文件
	 * @param  string $iLogLevel log的等级
	 * @param  string $sFineLine 行数
	 * @param  string $sLog      log内容
	 */
	public function log($sFile, $sFineLine, $iLogLevel, $sLog) {
		SURE_LOG($sFile, $sFineLine, $iLogLevel, $sLog);
	}

	/**
	 * 获取ip地址
	 * @return ip地址
	 */
	function getUserIp() {
		if(!empty($_SERVER["HTTP_CLIENT_IP"])) 
	       $cip = $_SERVER["HTTP_CLIENT_IP"];
	    else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) 
	       $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	    else if(!empty($_SERVER["REMOTE_ADDR"]))  
	       $cip = $_SERVER["REMOTE_ADDR"];
	    else
	       $cip = "";
	   
	    $arrCip = explode(',', $cip);
	    return urlencode($arrCip[0]);
	}

	/**
	 * 检查参数是否符合字符串
	 * @param  array $arrParamsString 需要检查的字符串
	 */
	function mustParamsForString ($arrParamsString) {
		if (!$this->checkParamsForString($arrParamsString)) {
			$this->echoRet(-100, '参数不合法');
		}
	}

	/**
	 * 检查参数是否符合Int
	 * @param  array $arrParamsInt 需要检查的字符串
	 */
	function mustParamsForInt ($arrParamsInt) {
		if (!$this->checkParamsForInt($arrParamsInt)) {
			$this->echoRet(-100, '参数不合法');
		}
	}

	/**
	 * 检查参数是否符合Float
	 * @param  array $arrParamsFloat 需要检查的字符串
	 */
	function mustParamsForFloat ($arrParamsFloat) {
		if (!$this->checkParamsForFloat($arrParamsFloat)) {
			$this->echoRet(-100, '参数不合法');
		}
	}

	/**
	 * 设置流水号
	 */
	function setSerialCode () {
		$sData = date("YmdHis");
		global $g_sSerialCode;
		$g_sSerialCode = "LOG_".$sData.'_'.md5($sData.rand(1, 10000));
	}

	/**
	 * autoload php file
	 * @param string $sClassName class name
	 */
	function SureAutoLoad ($sClassName) {
		
		$arrFilePath = explode("\\", $sClassName);
		$sFilePath = implode("/", $arrFilePath);		
		$sFilePath = sprintf("/data/%s.class.php", $sFilePath);

		if (!file_exists($sFilePath)) {
			throw new SureException("require_once $sFilePath 失败", 0);
		}

		require_once $sFilePath;
	}

}

/**
 * 控制器启动
 */
function RUN_CONTROLLER($CGI_APP_TYPE) {
	$evalstr = "\$app = new $CGI_APP_TYPE();";
	eval($evalstr);
	$app->startAction();
	unset($app);
}
