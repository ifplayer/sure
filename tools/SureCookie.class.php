<?php

/**
 * 作者：owen
 * 日期：2016-5-26
 * 功能：操作cookie
 */

namespace sure\tools;

class SureCookie
{

	/**
	 * 设置cookie
	 * @param string  	$sKey  key
	 * @param string  	$sVal  内容
	 * @param int 		$iTime 有效时间
	 */
	static public function setCookie ($sKey, $sVal, $iTime = 3600000, $sDomain = "qingfanqie.com")
	{
		setcookie($sKey, $sVal, time() + $iTime, "/", $sDomain);
	}

	/**
	 * 获取cookie值
	 * @param  string $sKey cookie key
	 * @return string       cookie value
	 */
	static public function getCookie ($sKey)
	{
		if (isset($_COOKIE[$sKey])) {
			return $_COOKIE[$sKey];
		}

		return "";
	}

	/**
	 * 删除cookie
	 * @param  string $sKey cookie key
	 */
	static public function remCookie ($sKey)
	{
		setcookie($sKey, '', time() - 3600);
	}

}
