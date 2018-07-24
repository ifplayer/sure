<?php

namespace sure\tools;

/**
* 作者：owen
* 非法词汇过滤
* 2016-4-6
*/

class SureString {

	// 脏词内容
	static $sFileContent = "";
		
	// 脏词替换数组
	static $arrReplace = null;

	/**
	 * 过滤非法字符/脏词
	 * @param  string $sText    内容
	 * @param  string $sReplace 替换的字符，默认为 *
	 * @return string           返回结果
	 */
	static public function filter ($sText, $sReplace = '***') {
		
		if (empty(self::$sFileContent)) {
			$sTxtPath = "/data/lib/res/words.dat";
			self::$sFileContent = @file_get_contents($sTxtPath);
		}

		if (empty(self::$sFileContent)) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "sFileContent为空");
			return $sText;
		}

		$arrText = explode("\n", self::$sFileContent);

		if (count($arrText) < 1) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "arrText为空");
			return $sText;
		}

		if (is_null(self::$arrReplace)) {
			self::$arrReplace = array_fill(0, count($arrText), $sReplace);
		}

		$arrBadWords = array_combine($arrText, self::$arrReplace);
		
		$sRet = strtr($sText, $arrBadWords);

		SURE_LOG(__FILE__, __LINE__, LP_INFO, $sText." ---> ".$sRet);

		return $sRet;

	}

}

