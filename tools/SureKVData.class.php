<?php

/**
* 作者：owen
* 功能：KV存储
*/

namespace sure\tools;

use \sure\base\SureException;

class SureKVData
{	
	private $m_instance;

	/**
	 * 构造函数
	 */
	function __construct () 
	{

		//配置文件地址
		$phpCfg = parse_ini_file('/data/cfg/php.cfg', true);

		$this->m_instance = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		if ($this->m_instance === false) {
			throw new SureException("SureKVData socket 创建失败", 0);
		}

		$result = socket_connect($this->m_instance, $phpCfg['kv']['ip'], $phpCfg['kv']['port']);

		socket_set_option($this->m_instance, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 10, "usec" => 0));
		socket_set_option($this->m_instance, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 10, "usec" => 0));

		if ($result === false) {
			throw new SureException("SureKVData socket 连接失败", 0);
		}
	}

	/**
	 * 判断key是否合法
	 * @param  string $sKey key
	 * @return boolean      是否合法
	 */
	public function checkKey ($sKey)
	{
		if (substr_count($sKey, "_") != 2) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "key不合法 -- ".$sKey);
			return false;
		}
		return true;
	}

	/**
	 * 获取值
	 * @param  string $sKey key
	 * @return string       value（异常情况下返回false）
	 */
	public function get ($sKey) {

		if (!$this->checkKey($sKey)) {
			return false;
		}

		$arrData = $this->getArrData(array($sKey));

		if ($arrData === false) {
			return false;
		}

		if (isset($arrData[$sKey])) {
			return $arrData[$sKey];
		}

		return false;

	}

	/**
	 * 批量获取多个value
	 * @param  array $arrKeys key数组
	 * @return arrary         值数组（异常情况下返回false）
	 */
	public function getArrData ($arrKeys)
	{

		foreach ($arrKeys as $sKey) {
			if (!$this->checkKey($sKey)) {
				return false;
			}
		}

		$arrData = array(
			'sMethod' => 'get', 
			'arrKeys' => $arrKeys
		);

		$sData = json_encode($arrData);
		$sData = pack("N", strlen($sData)).$sData;
		$iRet  = socket_write($this->m_instance, $sData, strlen($sData));

		if ($iRet < 1) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "发送数据失败");
			return false;
		}

		$sRet = "";

		while (true) {

			$sData = socket_read($this->m_instance, 1024);
			$sRet .= $sData;

			if (mb_substr($sData, -1) == "\n" || strlen($sData) == 0) {
				break;
			}
		}

		if (empty($sRet)) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "获取不到数据 -- ".json_encode($arrKeys));
			return false;
		}

		$arrData = json_decode($sRet, true);

		return $arrData;
	}

	/**
	 * 保存数据
	 * @param  string $sData 需要保存的数据
	 * @return string        key值（假如失败返回false）
	 */
	public function save ($sData)
	{

		$iLength     = mb_strlen($sData, 'utf-8');
		
		// 每次只传1000
		$iPerLength  = 1000;
		$iStartIndex = 0;
		$iIndex      = 0;
		$sId         = sprintf("%d%d", time(), rand(1, 99999999));

		// 一共需要发送次数
		$iMaxCount   = intval($iLength / $iPerLength);

		if ($iLength % $iPerLength != 0) {
			$iMaxCount++;
		}

		while ($iStartIndex < $iLength) {
			$sPerData    = mb_substr($sData, $iStartIndex, $iPerLength, 'utf-8');
			$iStartIndex += $iPerLength;

			if (!$this->perSend($sPerData, $iIndex, $sId, $iMaxCount)) {
				SURE_LOG(__FILE__, __LINE__, LP_ERROR, "出现数据发送失败");
				return false;
			}

			$iIndex++;
			// usleep(50000);
		}

		$sRet = socket_read($this->m_instance, 1024);
		SURE_LOG(__FILE__, __LINE__, LP_INFO, "$sRet");

		$arrData = json_decode($sRet, true);

		if ($arrData['bRet'] == false) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, $arrData['sMsg']);
			return false;
		}

		return $arrData['sKey'];
	}

	/**
	 * 根据key进行数据保存
	 * @param  string $sData 数据
	 * @param  string $sKey  key
	 * @return boolean       是否成功
	 */
	public function saveWithKey ($sData, $sKey)
	{

		if (!$this->checkKey($sKey)) {
			return false;
		}

		$iLength     = mb_strlen($sData, 'utf-8');
		
		// 每次只传1000
		$iPerLength  = 1000;
		$iStartIndex = 0;
		$iIndex      = 0;
		$sId         = sprintf("%d%d", time(), rand(1, 99999999));

		// 一共需要发送次数
		$iMaxCount   = intval($iLength / $iPerLength);

		if ($iLength % $iPerLength != 0) {
			$iMaxCount++;
		}

		while ($iStartIndex < $iLength) {
			$sPerData    = mb_substr($sData, $iStartIndex, $iPerLength, 'utf-8');
			$iStartIndex += $iPerLength;

			if (!$this->perSend($sPerData, $iIndex, $sId, $iMaxCount, $sKey)) {
				SURE_LOG(__FILE__, __LINE__, LP_ERROR, "出现数据发送失败");
				return false;
			}

			$iIndex++;
			// usleep(50000);
		}

		$sRet = socket_read($this->m_instance, 1024);
		SURE_LOG(__FILE__, __LINE__, LP_INFO, "$sRet");

		$arrData = json_decode($sRet, true);

		if ($arrData['bRet'] == false) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, $arrData['sMsg']);
			return false;
		}

		return true;
	}

	/**
	 * 插入更多数据
	 * @param  string $sData 内容
	 * @param  string $key   key
	 * @return boolean       是否保存情况
	 */
	public function append ($sData, $sKey) 
	{
		if (!$this->checkKey($sKey)) {
			return false;
		}

		$iLength     = mb_strlen($sData, 'utf-8');
		
		// 每次只传1000
		$iPerLength  = 1000;
		$iStartIndex = 0;
		$iIndex      = 0;
		$sId         = sprintf("%d%d", time(), rand(1, 99999999));

		// 一共需要发送次数
		$iMaxCount   = intval($iLength / $iPerLength);

		if ($iLength % $iPerLength != 0) {
			$iMaxCount++;
		}

		while ($iStartIndex < $iLength) {
			$sPerData    = mb_substr($sData, $iStartIndex, $iPerLength, 'utf-8');
			$iStartIndex += $iPerLength;

			if (!$this->perSend($sPerData, $iIndex, $sId, $iMaxCount, $sKey, true)) {
				SURE_LOG(__FILE__, __LINE__, LP_ERROR, "出现数据发送失败");
				return false;
			}

			$iIndex++;
			usleep(50000);
		}

		$sRet = socket_read($this->m_instance, 1024);
		SURE_LOG(__FILE__, __LINE__, LP_INFO, "$sRet");

		$arrData = json_decode($sRet, true);

		if ($arrData['bRet'] == false) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, $arrData['sMsg']);
			return false;
		}

		return true;
	}

	/**
	 * 更新
	 * @param  string $sData 内容
	 * @param  string $sKey  key
	 * @return boolean       是否成功
	 */
	public function update ($sData, $sKey)
	{
		if (!$this->checkKey($sKey)) {
			return false;
		}

		return $this->saveWithKey($sData, $sKey);
	}

	/**
	 * 删除某个key
	 * @param  string $sKey 键
	 * @return boolean      是否成功
	 */
	public function remove ($sKey) 
	{
		if (!$this->checkKey($sKey)) {
			return false;
		}

		$arrData = array(
			'sMethod' => 'remove', 
			'sKey'    => $sKey
		);

		$sData = json_encode($arrData);
		$sData = pack("N", strlen($sData)).$sData;
		$iRet  = socket_write($this->m_instance, $sData, strlen($sData));

		if ($iRet < 1) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "发送数据失败");
			return false;
		}

		$sRet = socket_read($this->m_instance, 1024);

		SURE_LOG(__FILE__, __LINE__, LP_INFO, "$sRet");

		if (empty($sRet)) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "获取不到数据 -- ".json_encode($arrKeys));
			return false;
		}

		$arrData = json_decode($sRet, true);

		return $arrData['bRet'];
	}

	/**
	 * 目录孩子数量
	 * @param  string $sKey 二级key
	 * @return array        
	 */
	public function childCount ($sKey) {
		
		$arrKey = explode("_", $sKey);

		if (count($arrKey) != 2) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "key只能是2级");
			return false;
		}

		$arrData = array(
			'sMethod' => 'childCount', 
			'sKey'    => $sKey
		);

		$sData = json_encode($arrData);
		$sData = pack("N", strlen($sData)).$sData;
		$iRet  = socket_write($this->m_instance, $sData, strlen($sData));

		if ($iRet < 1) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "发送数据失败");
			return false;
		}

		$sRet = "";

		while (true) {

			$sData = socket_read($this->m_instance, 1024);
			$sRet .= $sData;

			if (mb_substr($sData, -1) == "\n" || strlen($sData) == 0) {
				break;
			}
		}

		if (empty($sRet)) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "获取不到数据 -- ".$sKey);
			return false;
		}

		$arrData = json_decode($sRet, true);

		return $arrData;

	}

	/**
	 * 将buf分批上传
	 * @param  string 	$sData     内容
	 * @param  int 		$iIndex    发送索引
	 * @param  int 		$sId       表示本次数据传输的id号
	 * @param  int 		$iMaxCount 发送的最多次数
	 * @param  string   $sKey      是否已这个key进行保存
	 * @param  boolean  $bAppend   是否在末端插入
	 * @return boolean             本次是否发送成功
	 */
	private function perSend ($sData, $iIndex, $sId, $iMaxCount, $sKey = '', $bAppend = false) {

		$arrData = array(
			'sMethod'   => 'append',
			'sVal'      => $sData,
			'iIndex'    => $iIndex,
			'sId'       => $sId,
			'iMaxCount' => $iMaxCount,
			'sKey'      => $sKey,
			'bAppend'   => $bAppend
		);

		$sData = json_encode($arrData);
		$sData = pack("N", strlen($sData)).$sData;
		$iRet  = socket_write($this->m_instance, $sData, strlen($sData));

		if ($iRet < 1) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "发送数据失败");
			return false;
		}

		return true;

	}

	/**
	 * 析构函数
	 */
	function __destruct() 
	{
		if ($this->m_instance) {
            socket_close($this->m_instance);
            unset($this->m_instance);
            $this->m_instance = null;
	    }
	}
	
}
