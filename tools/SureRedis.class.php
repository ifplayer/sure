<?php

namespace sure\tools;

use \sure\tools\SureCurl;

/**
*  
*  创建人：owen
*  创建时间：2015-3-26
*  修改时间：2015-3-26
*/

/**
* Redis管理类
*/

// 用于消息推送的缓存数据
if (!defined('MSGACTION')) {
	define("MSGACTION", "MSGACTION_");
}

class SureRedis {

	// redis
	static $oRedis = null;

	/**
	 * 获取一个Redis对象
	 * @param  int $iIndex 数据库索引
	 * @return Redis redis对象
	 */
	static public function getInstance ($iIndex = 0) {

		if (isset(self::$oRedis)) {
			return self::$oRedis;
		}

		//配置文件地址
		$phpCfg = parse_ini_file('/data/cfg/php.cfg', true);

		self::$oRedis = new \Redis();

		self::$oRedis->open($phpCfg['redis_share']['ip'], $phpCfg['redis_share']['port']);
		self::$oRedis->auth($phpCfg['redis_share']['pwd']);
		self::$oRedis->select($iIndex);

		return self::$oRedis;
	}

	/**
	 * 获取全新的Redis对象
	 * @return object redis对象
	 */
	static public function getNewRedis ($iIndex = 0) {

		//配置文件地址
		$phpCfg = parse_ini_file('/data/cfg/php.cfg', true);

		$redis = new \Redis();
		$redis->open($phpCfg['redis_share']['ip'], $phpCfg['redis_share']['port']);
		$redis->auth($phpCfg['redis_share']['pwd']);
		$redis->select($iIndex);
		
		return $redis;

	}

	/**
	 * 设置redis的键值
	 * @param string 	$sKey       	键
	 * @param string 	$sValue     	值
	 * @param int 		$iRedisTime 	失效时间
	 */
	static public function setKey ($sKey, $sValue, $iRedisTime = 300) {

		$redis = SureRedis::getInstance();

		return $redis->setex($sKey, $iRedisTime, $sValue);

	}

	/**
	 * 获取redis中的值
	 * @param  string 	$sKey 	键
	 * @return string       	值
	 */
	static public function getKey ($sKey) {

		$redis = SureRedis::getInstance();
		return $redis->get($sKey);

	}

	/**
	 * 从Redis中删除某个key
	 * @param  string 	$sKey 	键
	 * @return boolean  	    是否删除成功
	 */
	static public function removeKey ($sKey) {

		$redis = SureRedis::getInstance();

		$iRet = $redis->del($sKey);

		$bRet = false;

		if ($iRet > 0) $bRet = true;

		return $bRet;
	}

	/**
	 * 发送消息，
	 * @param  string 	$sChannel 频道
	 * @param  string 	$sCardId  卡号
	 * @param  string 	$sMsg     消息
	 * @param  int  	$iTime	  保存时间
	 * @return boolean	是否发送成功
	 */
	static public function sendMsg ($sChannel, $sCardId, $sMsg, $iTime = 7200) {

		$oShareRedis = SureRedis::getInstance();

		// 1 : 表示成功 , 0 ：表示发送失败
		$iRet = $oShareRedis->publish($sChannel.'_'.$sCardId, $sMsg);

		SURE_LOG(__FILE__, __LINE__, LP_INFO, "publish -->".$iRet);

		if ($iRet >= 1) {
			SURE_LOG(__FILE__, __LINE__, LP_INFO, "直接推送成功");
			return true;
		}

		// redis key值
		$sRedisKey = SureRedis::getMessageRedisKey($sChannel, $sCardId);

		return SureRedis::setKey($sRedisKey, $sMsg, $iTime);

	}

	/**
	 * 发送消息，但不缓存
	 * @param  string 	$sChannel 频道
	 * @param  string 	$sCardId  卡号
	 * @param  string 	$sMsg     消息
	 * @return boolean	是否发送成功
	 */
	static public function sendMsgNoCache ($sChannel, $sCardId, $sMsg) {

		$oShareRedis = SureRedis::getShareRedisInstance();

		// 1 : 表示成功 , 0 ：表示发送失败
		$iRet = $oShareRedis->publish($sChannel.'_'.$sCardId, $sMsg);

		SURE_LOG(__FILE__, __LINE__, LP_INFO, "publish -->".$iRet);

		if ($iRet >= 1) {
			SURE_LOG(__FILE__, __LINE__, LP_INFO, "直接推送成功");
			return true;
		}

		return false;
	}

	/**
	 * 获取消息订阅Redis Key
	 * @param  string $sChannel 频道
	 * @param  string $sCardId  卡号
	 * @return string Redis Key   
	 */
	static public function getMessageRedisKey ($sChannel, $sCardId) {
		return MSGACTION.'_'.$sChannel.'_'.$sCardId;
	}

}

