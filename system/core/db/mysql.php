<?php
namespace system\core\db;

use \PDO;
/**
 * mysql类
 *
 * @author jcc
 *        
 */
class mysql
{

	private $config;

	private static $mysql;

	private $pdo;

	private function __construct($config = NULL)
	{
		$this->config = $config;
		$this->connect();
	}
	
	public function getDBName()
	{
		return $this->config['db_dbname'];
	}

	/**
	 * 获取mysql进程
	 */
	public static function getInstance($config = NULL)
	{
		if (empty(self::$mysql))
			self::$mysql = new mysql($config);
		return self::$mysql;
	}

	/**
	 * 数据库链接
	 */
	private function connect()
	{
		$this->pdo = new PDO($this->config['db_type'] . ':host=' . $this->config['db_server'] . ';dbname=' . $this->config['db_dbname'], $this->config['db_user'], $this->config['db_password'], array(
			PDO::ATTR_PERSISTENT => $this->config['db_forever']/*持久化连接*/));
		// 设置异常模式为抛出异常
		//$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->setCharset($this->config['db_charset']);
	}

	/**
	 * 设置连接字符集
	 *
	 * @param unknown_type $charset        	
	 */
	public function setCharset($charset)
	{
		$this->exec('set names `' . $charset . '`');
	}

	/**
	 * 执行sql语句
	 */
	public function query($sql, array $array = array())
	{
		$statement = $this->pdo->prepare($sql);
		if ($statement) {
			$result = $statement->execute($array);
			if ($result) {
				$sql_type = strtolower(substr(trim($statement->queryString), 0, 6));
				if (in_array($sql_type, ['select'],true)) {
					return $statement->fetchAll(PDO::FETCH_ASSOC);
				}
				else if (in_array($sql_type, ['insert','delete','update'],true))
				{
					return $statement->rowCount();
				}
				return $statement->fetchAll(PDO::FETCH_ASSOC);
			}
		}
		return false;
	}

	/**
	 * 执行某一些查询语句使用 例如 show tables
	 *
	 * @param unknown_type $sql        	
	 * @return multitype:
	 */
	public function exec($sql)
	{
		return $this->pdo->exec($sql);
	}

	/**
	 * 开始事物
	 */
	public function transaction()
	{
		return $this->pdo->beginTransaction();
	}

	/**
	 * 执行
	 */
	public function commit()
	{
		return $this->pdo->commit();
	}

	/**
	 * 事物回滚
	 */
	public function rollback()
	{
		return $this->pdo->rollBack();
	}

	/**
	 * 上一次插入的id
	 */
	public function lastInsert($name = NULL)
	{
		return $this->pdo->lastInsertId($name);
	}
	
	public function error()
	{
		return $this->pdo->errorInfo();
	}
	
	public function errno()
	{
		return $this->pdo->errorCode();
	}
}