<?php

/**
* 用于限频（原子性）
*/

namespace sure\tools;

use sure\tools\SureRedis;

class SureLimit {

	/**
	 * 限制频率 (不允许在定时任务中使用)
	 * @param  string  	$sKey      key值
	 * @param  int   	$iSec      秒
	 * @param  int  	$iMaxCount 最多访问次数
	 * @return boolean             是否限频成功 返回:true可以正常访问，返回false则不行
	 */
	static public function isLimit ($sKey, $iSec, $iMaxCount) {

		// redis 对象
		$redis = SureRedis::getInstance();
		$sKey = "LIMIT_".$sKey;

		if ($iMaxCount < 1) {
			return false;
		}

		// 是否设置成功
		$bRet = $redis->setnx($sKey, $iMaxCount - 1);

		if ($bRet === true) {
			// 设置成功，设置失效时间
			$redis->setTimeout($sKey, $iSec);
			return true;
		}

		// 设置失败
		$iCount = $redis->decr($sKey);
		$bRet = $iCount >= 0 ? true : false;
		return $bRet;

	}

}
