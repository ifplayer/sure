<?php

$g_sFileName = '';

/**
 * 打印日志路径 非action类会需要用到
 * @var string
 */
$g_sLogPath  = '';

/**
 * 卡号
 * @var string
 */
$g_sCardId = '';

/**
 * IP
 * @var string
 */
$g_sIP = '';

/**
 * 是否改变log
 * @var boolean
 */
$g_bChangeLog = false;

/**
 * 请求序列号
 * @var string
 */
$g_sSerialCode = '';

/**
 * 发送日志邮件
 * @var string
 */
$g_sLogMail = '';

require_once '/data/sure/define/SureDefine.inc.php';
require_once '/data/sure/define/SureSessionDefine.inc.php';
require_once '/data/sure/base/SureController.class.php';

use \sure\tools\SureLogger;
use \sure\tools\SureLogstashLog;

/**
 * 请求类型
 * @var int
 */
$g_iType = WEB;

global $glog;

/**
 * 设置失败日志发送邮件地址
 * @param string $sMail 地址
 */
function SURE_SET_MAIL ($sMail) {
	global $g_sLogMail;
	$g_sLogMail = $sMail;
}

// 写loger
function SURE_LOG ($sFile, $sFineLine, $iLogLevel, $sLog, $bReset = false) {

	if ($iLogLevel == LP_DEBUG && !SURE_ENV_IS_DEBUG()) {
		// 是正式环境并且是DEBUG
		return;
	}
	
	global $g_sLogPath;

	if (empty($g_sLogPath)) {
		throw new SureException("日志路径未被初始化", 0);
	}

	$sFileName = basename($sFile, '.php');
	
	global $glog;

	if ($bReset) {
		// 重新创建
		$glog = NULL;
	}

	global $g_bChangeLog;

	if ($glog == NULL || $g_bChangeLog) {
		$glog = new SureLogger($g_sLogPath, $sFileName);
		$g_bChangeLog = false;
	}

	global $g_sLogMail;
	if ($iLogLevel == LP_FATAL && !empty($g_sLogMail)) {
		$sMailContent = sprintf("[%s][line:%s]%s", $sFile, $sFineLine, $sLog);
		SureLogger::logWarning($g_sLogMail, "日志－－错误警告", $sMailContent);
	}

	$glog->writeLog($sFile, $sFineLine, $iLogLevel, $sLog);

}

/**
 * logstash写日志
 * @param string  $sFile     文件路径
 * @param int     $sFineLine 日志行号
 * @param int     $iLogLevel 日志级别
 * @param string  $sLog      日志内容
 */
function SURE_ELK_LOG ($sFile, $sFineLine, $iLogLevel, $sLog, $bReset = false) {

	if ($iLogLevel == LP_DEBUG && !SURE_ENV_IS_DEBUG()) {
		// 是正式环境并且是DEBUG
		return;
	}

	global $g_sLogPath;
	global $g_logstashLog;

	if (!isset($g_logstashLog)) {
		$g_logstashLog = new SureLogstashLog($g_sLogPath, $sFile);
	}

	$g_logstashLog->writeLog($sFile, $sFineLine, $iLogLevel, $sLog);

}

/**
 * 设置日志路径
 * @param string $sLogPath  日志路径
 * @param string $sFileName 日志文件前缀名
 */
function SURE_LOG_SET_PATH ($sLogPath, $sFileName) {
	global $g_sFileName;
	$g_sFileName = $sFileName;

	global $g_sLogPath;
	$g_sLogPath = $sLogPath;

	global $glog;
	$glog = NULL;
}

/**
 * 设置日志路径
 * @param string $sLogPath  日志路径
 * @param string $sFileName 日志文件前缀名
 */
function SURE_ELK_LOG_SET_PATH ($sLogPath, $sFileName) {
	global $g_sFileName;
	$g_sFileName = $sFileName;

	global $g_sLogPath;
	$g_sLogPath = $sLogPath;

	global $g_logstashLog;
	$g_logstashLog = NULL;
}

/**
 * 修改log日志路径
 * @param string $sLogPath  日志路径
 * @param string $sFileName 日志文件前缀名
 */
function SURE_LOG_CHANGE_PATH ($sLogPath, $sFileName) {

	global $g_sLogPath;
	$g_sLogPath = $sLogPath;

	global $g_sFileName;
	$g_sFileName = $sFileName;

	global $g_bChangeLog;
	$g_bChangeLog = true;
	
}

/**
 * 是否测试环境
 */
function SURE_ENV_IS_DEBUG () {

	global $g_phpCfg;

	//配置文件地址
	if (!isset($g_phpCfg)) {
		$g_phpCfg = parse_ini_file('/data/cfg/php.cfg', true);		
	}

	return (boolean)$g_phpCfg['lib']['debug'];
}
