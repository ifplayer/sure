<?php

namespace sure\tools;

use \sure\type\SureFastdfsRetCodeType;

/**
 * 作者：owen
 * 功能：用于fastdfs上传数据
 */

class SureFastdfs 
{

	/**
	 * 组的名称
	 * @var string
	 */
	private $m_sGroupName;

	/**
	 * 上传file的名
	 * @var string
	 */
	private $m_sUploadFileName = "img";

	/**
	 * 文件最大
	 * @var integer
	 */
	private $m_iMaxSize = 500;

	/**
	 * 获取错误码
	 * @var integer
	 */
	private $m_iErrorno = 0;

	/**
	 * 是否图片
	 * @var boolean
	 */
	private $m_bImage;

	/**
	 * 文件类型
	 * @var array
	 */
	private $m_arrType = array();

	/**
	 * 文件的绝对路径
	 * @var string
	 */
	private $m_sFilePath;

	/**
	 * 构造函数
	 * @param string  $sGroupName 组名
	 * @param boolean $bImage     是否图片
	 */
	function __construct ($bImage = true, $sGroupName = "qfq")
	{
		$this->m_sGroupName = $sGroupName;
		$this->m_bImage     = $bImage;
	}

	/**
	 * 获取域名
	 * @return string 获取域名
	 */
	static public function getHost ()
	{

		global $phpCfg;
		
		//配置文件地址
		if (!isset($phpCfg)) {
			$phpCfg = parse_ini_file('/data/cfg/php.cfg', true);
		}

		return $phpCfg['fastdfs']['http'];

	}
	
	/**
	 * 上传图片
	 * @param  string $sImagePath 文件绝对路径
	 * @param  string $sImageName 上传成功之后文件名
	 * @param  string $sExt       扩展名
	 * @param  string $sGroupName 文件组，默认是qfq
	 * @return boolean            是否成功
	 */
	static public function upload ($sImagePath, & $sImageName, $sExt, $sGroupName = "qfq")
	{

		$result = fastdfs_storage_upload_by_filename($sImagePath, null, array(), $sGroupName);

		if (!file_exists($sImagePath)) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "$sImagePath --> 不存在");
			return false;
		}
		
		if ($result === false) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "$sImagePath --> 上传失败");
			return false;
		}

		$sImageName = sprintf("%s/%s", $result['group_name'], $result['filename']);

		return true;
	}

	/**
	 * 上传图片
	 * @param  string $sImageName 	文件名
	 * @param  string $sFileName 	图片名
	 * @param  string $sGroupName 	文件组，默认是qfq
	 * @param  int    $iCode        返回错误值
	 * @return boolean           	是否成功
	 */
	static public function uploadImage (& $sImageName, $sFileName = "tmp_name", $sGroupName = "qfq", & $iCode = 0)
	{

		if (!isset($_FILES['img'])) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, 'img 找不到');
			$iCode = SureFastdfsRetCodeType::UNKNOWN_ERROR;
			return false;
		}

		$iFileSize = $_FILES['img']['size'] / 1024;

		//图片太大
		if ($iFileSize > 100) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, '文件太大 --->'.$iFileSize);
			$iCode = SureFastdfsRetCodeType::FILE_SIZE_LARGE;
			return false;
		}

		$arrImgType = array('gif', 'jpg', 'jpeg', 'png');

		$sTmp = strrchr($_FILES['img']['name'], ".");

		if ($sTmp === false) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, '出现非法文件 --->'.$sTmp);
			$iCode = SureFastdfsRetCodeType::FILE_TYPE_ILLEGAL;
			return false;
		}

		$sImgType =  strtolower(substr($sTmp, 1));

		if (!in_array($sImgType, $arrImgType)) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, '出现非法文件 --->'.$sImgType);
			$iCode = SureFastdfsRetCodeType::FILE_TYPE_ILLEGAL;
			return false;
		}

		$sFilePath = $_FILES["img"][$sFileName].'.'.$sImgType;

		$bRet = move_uploaded_file($_FILES["img"][$sFileName], $sFilePath);

		if (!$bRet) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, '移动文件失败 --->'.$sFilePath);
			$iCode = SureFastdfsRetCodeType::UNKNOWN_ERROR;
			return false;
		}

		$bRet = self::upload($sFilePath, $sImageName, $sImgType, $sGroupName);

		if ($bRet == false) {
			$iCode = SureFastdfsRetCodeType::UNKNOWN_ERROR;
		}

		$iCode = SureFastdfsRetCodeType::SUCCESS;

		return true;
	}


	/**
	 * 删除图片
	 * @param  string $sFileName   文件图片路径
	 * @return boolean             是否成功
	 */
	static public function remove ($sFileName)
	{

		$bRet = fastdfs_storage_delete_file1($sFileName);

		if ($bRet === false) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, "$sFileName --> 删除失败");
			return false;
		}

		return $bRet;

	}

	/**
	 * 设置upload file的name
	 * @param string $sUploadFileName upload file的name
	 */
	public function setUploadFileName ($sUploadFileName)
	{
		$this->m_sUploadFileName = $sUploadFileName;
	}

	/**
	 * 设置上传文件最大的字节数
	 * @param int $iMaxSize 字节数
	 */
	public function setFileMaxSize ($iMaxSize)
	{
		$this->m_iMaxSize = $iMaxSize;
	}

	/**
	 * 错误返回码
	 * @return int 错误返回码
	 */
	public function getErrorno ()
	{
		return $this->m_iErrorno;
	}

	/**
	 * 设置文件上传的类型
	 * @param array $arrFileType 文件类型
	 */
	public function setFileType ($arrFileType) 
	{
		$this->m_arrType = $arrFileType;
	}

	/**
	 * 设置是否为图片
	 * @param boolean $bImage 是否图片
	 */
	public function setImage ($bImage)
	{
		$this->m_bImage = $bImage;
	}

	/**
	 * 获取文件的绝对路径
	 * @return string 文件绝对路径
	 */
	public function getFilePath ()
	{
		return $this->m_sFilePath;
	}

	/**
	 * 上传文件
	 * @return string 返回文件名（假如失败，返回false）
	 */
	public function uploadFile ()
	{
		if (!isset($_FILES[$this->m_sUploadFileName])) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, $this->m_sUploadFileName.' 找不到');
			$this->m_iErrorno = SureFastdfsRetCodeType::UNKNOWN_ERROR;
			return false;
		}

		$iFileSize = $_FILES[$this->m_sUploadFileName]['size'] / 1024;

		//图片太大
		if ($iFileSize > $this->m_iMaxSize) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, '文件太大 --->'.$iFileSize);
			$this->m_iErrorno = SureFastdfsRetCodeType::FILE_SIZE_LARGE;
			return false;
		}

		$sTmp = strrchr($_FILES[$this->m_sUploadFileName]['name'], ".");

		if ($sTmp === false) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, '出现非法文件 --->'.$sTmp);
			$this->m_iErrorno = SureFastdfsRetCodeType::FILE_TYPE_ILLEGAL;
			return false;
		}

		$sImgType =  strtolower(substr($sTmp, 1));

		if ($this->m_bImage) {
			// 此时是图片
			$arrImgType = array('gif', 'jpg', 'jpeg', 'png');

			SURE_LOG(__FILE__, __LINE__, LP_INFO, $_FILES[$this->m_sUploadFileName]['name']);

			if (!in_array($sImgType, $arrImgType)) {
				SURE_LOG(__FILE__, __LINE__, LP_ERROR, '出现非法文件 --->'.$sImgType);
				$this->m_iErrorno = SureFastdfsRetCodeType::FILE_TYPE_ILLEGAL;
				return false;
			}
		}

		if (is_array($this->m_arrType) && $this->m_bImage === false && !in_array($sImgType, $this->m_arrType)) {

			if (count($this->m_arrType) == 0) {
				SURE_LOG(__FILE__, __LINE__, LP_ERROR, '未设置文件类型');
				$this->m_iErrorno = SureFastdfsRetCodeType::FILE_TYPE_ILLEGAL;
				return false;
			}

			SURE_LOG(__FILE__, __LINE__, LP_ERROR, '出现非法文件 --->'.$sImgType);
			$this->m_iErrorno = SureFastdfsRetCodeType::FILE_TYPE_ILLEGAL;
			return false;
		}

		$this->m_sFilePath = $_FILES[$this->m_sUploadFileName]['tmp_name'].'.'.$sImgType;
		$bRet      = move_uploaded_file($_FILES[$this->m_sUploadFileName]['tmp_name'], $this->m_sFilePath);

		if (!$bRet) {
			SURE_LOG(__FILE__, __LINE__, LP_ERROR, '移动文件失败 --->'.$this->m_sFilePath);
			$this->m_iErrorno = SureFastdfsRetCodeType::UNKNOWN_ERROR;
			return false;
		}

		$bRet = self::upload($this->m_sFilePath, $sImageName, $sImgType, $this->m_sGroupName);

		if ($bRet === false) {
			$this->m_iErrorno = SureFastdfsRetCodeType::UNKNOWN_ERROR;
			return false;
		}

		$this->m_iErrorno = SureFastdfsRetCodeType::SUCCESS;

		return $sImageName;
	}

}
