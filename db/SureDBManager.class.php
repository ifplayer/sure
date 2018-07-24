<?php

namespace sure\db;

use \sure\db\SureDBMysql;
use \sure\db\SureDBMysqli;
use \sure\base\SureException;

/**
 * Sure框架中管理db的类
 */
class SureDBManager {

	// dbQfq
	static $dbQfqCon = null;

	/**
	 * 获取db对象
	 * @param  字符串 	$sType 	db节点
	 * @return db对象        	操作数据库对象
	 */
	static public function getDBManager ($sType) {
		
		
		//配置文件地址
		$phpCfg = parse_ini_file('/data/cfg/php.cfg', true);

		// 获取php版本号
		$iPhpVersion = intval(substr(PHP_VERSION, 0, 1));

		if ($sType == DB_QFQ) {

			if (isset(self::$dbQfqCon)) {
				return self::$dbQfqCon;
			}

			if ($iPhpVersion >= 7) {
				self::$dbQfqCon = new SureDBMysqli($phpCfg['qfq_db']['ip'], $phpCfg['qfq_db']['username'], 
					$phpCfg['qfq_db']['pwd'], $phpCfg['qfq_db']['database']);
			} else {
				self::$dbQfqCon = new SureDBMysql($phpCfg['qfq_db']['ip'], $phpCfg['qfq_db']['username'], 
					$phpCfg['qfq_db']['pwd'], $phpCfg['qfq_db']['database']);
			}

			return self::$dbQfqCon;

		}

		throw new SureException("查找不到db节点");
	}

	/**
	 * 释放连接资源
	 * @param  类型 $sType db类型
	 */
	static public function freeConn ($sType) {

		if ($sType == DB_QFQ && isset(self::$dbQfqCon)) {
			self::$dbQfqCon = null;
		}

	}

}

