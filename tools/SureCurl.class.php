<?php

namespace sure\tools;

/**
* Curl工具类
*/
class SureCurl
{
	
	/**
	 * post 获取数据
	 * @param  string 	$sHttp http地址
	 * @param  array 	$sData post数据
	 * @return string            
	 */
	static public function post($sHttp, $arrData)
	{

		$sPostData = "";

		foreach ($arrData as $k => $v) {
		    $sPostData.= "$k=".urlencode($v)."&";
		}

		$sData = substr($sPostData, 0, -1);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL, $sHttp);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $sData);

		$sRet = curl_exec($ch);

		curl_close($ch);

		return $sRet;
	}

	/**
	 * post 获取数据
	 * @param  string 	$sHttp http地址
	 * @param  string 	$sData post数据
	 * @return string            
	 */
	static public function postData($sHttp, $sData)
	{

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL, $sHttp);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $sData);

		$sRet = curl_exec($ch);

		curl_close($ch);

		return $sRet;
	}

	/**
	 * 上传文件
	 * @param  string $sHttp     请求地址
	 * @param  string $sFilePath 文件路径
	 * @param  string $sType     类型
	 * @return string            服务器返回内容
	 */
	static public function postFile($sHttp, $sFilePath, $sType)
	{

		$sFileName = basename($sFilePath);

        $data = array(
            'field'=>'@'.$sFilePath.";type=".$sType.";filename=".$sFileName
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $sHttp);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $sRet = curl_exec($ch);

        curl_close($ch);

		return $sRet;
	}

	/**
	 * curl网络请求
	 * @param  string $sHttp   http请求地址
	 * @param  string $sData   请求内容
	 * @param  string $sMethod 方法，默认：post
	 * @return string          服务返回的结果
	 */
	static function request ($sHttp, $sData, $sMethod = 'post')
	{

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $sHttp);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $sMethod);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: $sMethod"));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $sData);
		
		$sRet = curl_exec($ch);

		if (curl_errno($ch)) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "{sHttp} 请求失败 : ".curl_error($ch));
		}
		
		curl_close($ch);
		 
		return $sRet;
	}

}
