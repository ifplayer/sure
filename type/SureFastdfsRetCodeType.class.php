<?php

namespace sure\type;

/**
 * 设备访问类型
 */
class SureFastdfsRetCodeType {
	
	// 正常
	const SUCCESS           = 0;
	// 文件太大
	const FILE_SIZE_LARGE   = 1;
	// 文件类型非法
	const FILE_TYPE_ILLEGAL = 2;
	// 未知错误
	const UNKNOWN_ERROR     = 3;

}
