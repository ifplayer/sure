<?php

namespace sure\tools;

/**
 * Sure正则表达式验证工具
 */
class SureRegex {

	/**
	 * 是否为空
	 * @param  string &$sKeyword 关键字
	 * @return boolean           是否为空
	 */
	public static function isEmpty(&$sKeyword) {

		$sKeyword = trim($sKeyword);//去除两边的空格，防止empty判断' '之类的'空'字符正常通过

		if (empty($sKeyword))
			return true;

		//判断中间空格，查找空字符串首次出现的位置，没有则返回false
		//ACSII 13代表回车符，32代表space符、&#32(html 空格)
		if (false !==  strpos(chr(32), $sKeyword) && false !== strpos(chr(13), $sKeyword))
			return true;
		else
			return false;

		/*$bEmpty = false;
		for ($i = 0; $i < strlen($sKeyword); $i++) {

			//echo 'ascii:' . ord($sKeyword[$i]);
			if (chr(32) === $sKeyword[$i] || chr(13) === $sKeyword[$i]) {

				$bEmpty = true;
				break;
			}
		}

		return $bEmpty;*/
	}

	/**
	 * 检查用户名
	 * @param  string &$sUserName 用户名
	 * @return boolean            是否为空
	 */
	public static function checkUserName(&$sUserName) {

		if (self::isEmpty($sUserName))
			return false;

		//去除两边的空字符串
        //$sUserName = preg_replace("/\s/", "", $sUserName);
        //$sUserName = time($sUserName);

		//6~20位有效字符(_、字母、数字)，并以字符开头
		if (!preg_match("/^[a-zA-Z]{1}([a-zA-Z0-9]|[_]){5,19}$/", $sUserName))
            return false;

        return true;
	}

	/**
	 * 检测密码
	 * @param  string &$sPassword 密码
	 * @return boolean            是否合法
	 */
	public static function checkPassword(&$sPassword) {

		if (self::isEmpty($sPassword))
			return false;

		//去除两边的空字符串
        //$sPassword = preg_replace("/\s/", "", $sPassword);
        //$sPassword = time($sPassword);
		
        //检测特殊字符，只支持文本或数字，6~20位;不允许出现&#32或&nbsp;，两者都代表html空字符;//!(.*(&#32|&nbsp;).*)
        if (!preg_match("/^([a-zA-Z0-9]|[`~!@#$%^&*()+=|{}':;',\\[\\].<>]){6,20}$/", $sPassword))
            return false;

        if (preg_match("/^.*(?=&nbsp;|&#32).*$/", $sPassword))
        	return false;

        //后期需要把两条正则表达式合并，问题在于&nbsp;与&#32的排除上，使用正向预查正常，负向预查失败

        return true;
	}

	/**
	 * 检测卡号
	 * @param  string &$sCardId 卡号
	 * @return boolean          是否合法
	 */
	public static function checkCardId(&$sCardId) {

		if (self::isEmpty($sCardId))
			return false;

		//去除两边的空字符串
		//$sCardId = preg_replace("/\s/", "", $sCardId);
		//$sCardId = time($sCardId);

		/*$iLen = strlen($sCardId);		
		if (9 !== $iLen && 10 !== $iLen)
			return false;*/

		//9到10位有效数字
        if (!preg_match("/^[0-9]{9,10}$/", $sCardId))
            return false;

        return true;
	}

	/**
	 * 检测用户卡号
	 * @param  string &$sUserCardId 用户卡号id
	 * @return boolean              是否合法
	 */
	public static function checkUserCardId(&$sUserCardId) {

		if (self::isEmpty($sUserCardId))
			return false;
		
		//9到10位有效数字
        if (!preg_match("/^[0-9]{16}$/", $sUserCardId))
            return false;

        return true;
	}

	/**
	 * 检测手机号码
	 * @param  string $sMobilephone 手机号码
	 * @return boolean           	是否合法
	 */
	public static function checkMobilephone(&$sMobilephone) {

		if (self::isEmpty($sMobilephone))
			return false;

		//去除两边的空字符串
        //$sUserName = preg_replace("/\s/", "", $sUserName);
        //$sMobilephone = time($sMobilephone);

        //手机号码正则表达式验证
        if (!preg_match("/^(13|14|15|17|18)[0-9]{9}$/", $sMobilephone))
            return false;

        return true;
	}

	/**
	 * 检测联系电话（可是座机，手机）
	 * @param  string $sTel 联系电话
	 * @return boolean           	是否合法
	 */
	public static function checkTel(&$sTel) {

		if (self::isEmpty($sTel))
			return false;

		// 手机号码
		if (preg_match("/^(13|14|15|17|18)[0-9]{9}$/",$sTel))
			return true;

		// 一种是三位区号，8位本地号(如010-12345678)，一种是4位区号，7位本地号(0376-2233445)
        if (preg_match("/^0\d{2,3}-\d{7,8}$/", $sTel))
        	return true;

        // 7或8位本地号
        if (preg_match("/^\d{7,8}$/", $sTel))
        	return true;

        return false;
	}

	/**
	 * 检测邮箱
	 * @param  string &$sMailbox 邮箱
	 * @return boolean           是否合法
	 */
	public static function checkMailbox(&$sMailbox) {

		if (self::isEmpty($sMailbox))
			return false;

		//去除两边的空字符串
        //$sMailbox = preg_replace("/\s/", "", $sMailbox);
        //$sMailbox = time($sMailbox);

		//邮箱正则表达式验证
        if (!preg_match("/^([\\w-.]+)@(([[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.)|(([\\w-]+.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(]?)$/", $sMailbox))
            return false;
        
    	return true;
	}

	/**
	 * 检测图书条形码
	 * @param  string $sBookCode 图书条形码
	 * @return boolean           是否合法
	 */
	public static function checkBookCode($sBookCode) {

		if (self::isEmpty($sBookCode))
			return false;

		if (!preg_match("/^[0-9]{9}$/", $sBookCode))
			return false;

		return true;
	}

	/**
	 * 检测中文字符
	 * @param  string $sWord     词
	 * @param  array &$arrMatch  匹配项
	 * @return boolean           是否为中文
	 */
	public static function checkChinese($sWord, &$arrMatch) {

		if (self::isEmpty($sWord))
			return false;

		$arrAllMatch = null;
		//preg_match("/^[\x{4e00}-\x{9fa5}]+/$u", $sName, $arrAllMatch);//匹配一次
		$bRet = preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $sWord, $arrAllMatch);//匹配多次

		if (isset($arrAllMatch[0]))
			$arrMatch = $arrAllMatch[0];

		return $bRet;
	}

	/**
	 * 检测上传图片
	 * @param  string $sWord     词
	 * @param  array &$arrMatch  匹配项
	 * @return boolean           是否为中文
	 */
	public static function checkUploadImage(&$sImg) {

		if (self::isEmpty($sImg))
			return false;

		$sImg = str_replace("/_", "/", $sImg);		    

		if (!preg_match_all("/^(\/var\/www\/html\/)(([a-zA-Z0-9\_\-])+(\/))*([a-zA-Z0-9\_\-])+(\.jpg|\.JPG|\.jpeg|\.JPEG|\.bmp|\.BMP|\.png|\.PNG|\.gif|\.GIF)$/", $sImg))
			return false;

		return true;
	}

	/**
	 * 检测真是姓名
	 * @param  string $sWord     词
	 * @param  array &$arrMatch  匹配项
	 * @return boolean           是否为中文
	 */
	public static function checkRealName(&$sWord) {

		if (self::isEmpty($sWord))
			return false;

		if (!preg_match_all("/^[\x{4e00}-\x{9fa5}a-zA-Z\s]+$/u", $sWord))
			return false;


		return true;
	}


	/**
	 * 检测ISBN码
	 * @param  string $sWord     词
	 * @param  array &$arrMatch  匹配项
	 * @return boolean           是否为中文
	 */
	public static function checkISBN(&$sWord) {

		if (self::isEmpty($sWord))
			return false;

		if ((mb_strlen($sWord, 'utf-8')) == 10 && preg_match_all("/^[0-9]{1,9}[0-9xX]$/u", $sWord)) {
			return true;
		}

		if ((mb_strlen($sWord, 'utf-8')) == 13 && preg_match_all("/^[978]{1,3}[0-9]{4,12}[0-9xX]$/u", $sWord)) {
			return true;
		}

		return false;
	}

	/**
	 * 检查身份证
	 * @param  string $sIdentitycard 身份证
	 * @return 
	 */
	public static function checkIdentitycard($sIdentitycard){

		SURE_LOG(__FILE__, __LINE__, LP_INFO, "根据身份证规则判断合法性，证件类型：{$sIdentitycard}");

		// 只能是18位  
		if (strlen($sIdentitycard) != 18) { 
			SURE_LOG(__FILE__, __LINE__, LP_INFO, "传入的身份证长度不为18位");
			return false;  
		}  

		// 取出本体码  
		$sIdCardBase = substr($sIdentitycard, 0, 17);  

		// 取出校验码  
		$sVerifyCode = substr($sIdentitycard, 17, 1);  

		// 加权因子  
		$arrFactor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);  

		// 校验码对应值  
		$arrVerifyCodeList = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');  

		// 根据前17位计算校验码  
		$iTotal = 0;  
		for ($i=0; $i<17; $i++) {  
			$iTotal += substr($sIdCardBase, $i, 1) * $arrFactor[$i];  
		}  

		// 取模  
		$iMod = $iTotal % 11;  

		// 比较校验码  
		if ($sVerifyCode != $arrVerifyCodeList[$iMod]) { 
			
			SURE_LOG(__FILE__, __LINE__, LP_INFO, "检查身份证合法性，失败");
			return false;  
		}

		SURE_LOG(__FILE__, __LINE__, LP_INFO, "检查身份证合法性，成功");
		return true;  

	}

}
