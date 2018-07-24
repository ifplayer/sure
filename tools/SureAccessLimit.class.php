<?php

namespace sure\tools;

use \sure\base\SureException;

class SureAccessLimit 
{

	/**
	 * 实例
	 * @var object
	 */
	private static $instance = null;

	/**
	 * 文件路径
	 * @var string
	 */
	private static $m_sFilePath;

	/**
	 * 构造函数
	 * @param string $sFileName 文件名
	 * @param string $sPath     文件路径
	 */
	public function __construct ($sFileName, $sPath) 
	{

		self::$m_sFilePath = $sPath.'/'.$sFileName.".limit.log";

		//创建日志路径
		if (!is_dir($sPath)) {
			if (!mkdir($sPath, 0777)) {
				throw new SureException("Create directory Failure : {$sPath}!", 0);
			}
		}

		//创建日志文件
		if (!file_exists(self::$m_sFilePath)) {

			if (!touch(self::$m_sFilePath)) {
				throw new SureException("Create Log File Failure", 0);
			}

		}
	}

	/**
	 * 限频
	 * @param  int $iKey       限频Key
	 * @param  int $iMaxAccess 最多访问次数
	 * @param  int $iSec       时间内
	 * @return boolean         是否达到限频范围
	 */
	public function checkAccess ($iKey, $iMaxAccess, $iSec) 
	{

		if ($iKey > 999999999) {
			return true;
		}

		$g_iTime = time(); //当前时间戳
		$handle = fopen(self::$m_sFilePath, 'r+'); //打开文件
		$iSeek = ($iKey - 1) * 13; //每个键值占用13个字符，根据键值计算需要越过的字符
		fseek($handle, $iSeek, SEEK_SET); //越过 $iSeek 个字符
		$sOld = fgets($handle, 14);
		$sLastTime = substr($sOld, 0, 10); //上次登录的时间
		$iCount = (int)(substr($sOld, 10, 3)); //访问的次数
		$iLastTime = (int)$sLastTime; //最后一次时间
		$bLimit = false; // 是否已经过期了

		if ($g_iTime - $iLastTime >= $iSec) {
			$bLimit = true; //已经过期
		}

		$bRet = true;

		if ($sLastTime == '          ' || $bLimit) { //第一次创建或已经过期
			$sLastTime = $g_iTime; //当前时间
			$sCount = '1  ';
			$sLimitLog = $sLastTime.$sCount;
		} else {

			if ($iMaxAccess <= $iCount) {
				$bRet = false; //此时超过了频率限制
			}

			$iCount++;

			if($iCount > 999) { //一个页面的访问次数技术最多999
				$iCount = 999;
			}
			
			$sLastTime = $g_iTime;
			$sLimitLog = $sLastTime.$iCount;
		}

		fseek($handle, $iSeek, SEEK_SET);

		if (!fwrite($handle, $sLimitLog)) {
			//写限频失败
			throw new SureException("Write Limit Log to file Error.\n");
	    }

		fclose($handle);
		return $bRet;

	} 

	/**
	 * 获取一个限频对象
	 * @param string $sFileName 文件名
	 * @param string $sPath     文件路径
	 * @return object           限频对象
	 */
	static public function instance ($sFileName, $sPath) 
	{

		if (isset(self::$instance)) {return self::$instance;};

		$instance = new SureAccessLimit($sFileName, $sPath);
		return $instance;

	} 

}
