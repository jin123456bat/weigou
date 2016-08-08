<?php
namespace system\core;

use system\core\db\mysql;
/**
 * 数据模型
 *
 * @author 程晨
 *        
 */
class model
{
	private $_db;

	private $_memcache;

	private $_redis;

	private $_table;

	public $_temp;
	
	private $_sql;
	
	private $_fields;

	function __construct($table)
	{
		$this->_table = $table;
		$this->__loadDB();
		$this->__loadMemcache();
		$this->__loadRedis();
		
		$this->getTableName($table);
	}
	
	public function model($table)
	{
		static $array = [];
		if (!isset($array[$table]) || empty($array[$table]))
		{
			$array[$table] = new model($table);
		}
		return $array[$table];
	}
	
	public function getFields($table = '')
	{
		$table = trim($table,'`');
		return empty($table)?$this->_fields:$this->_fields[$table];
	}
	
	public function setFields($fields,$table = '')
	{
		if (empty($table))
		{
			$this->_fields = $fields;
		}
		else
		{
			$this->_fields[$table] = $fields;
		}
	}
	
	/**
	 * 获取表的字段名
	 */
	private function getTableName($table)
	{
		if (!isset($this->_fields) || empty($this->_fields[$table]))
		{
			$result = $this->query('select COLUMN_NAME from information_schema.COLUMNS where table_name = ? and table_schema = ?',[$table,$this->_db->getDBName()]);
			$array = [];
			foreach ($result as $field)
			{
				$array[] = $field['COLUMN_NAME'];
			}
			$this->_fields[$table] = $array;
		}
	}

	/**
	 * 载入数据库
	 */
	private function __loadDB()
	{
		$_dbConfig = config('db');
		$this->_db = mysql::getInstance($_dbConfig);
	}

	/**
	 * 载入memcache
	 */
	private function __loadMemcache()
	{
		if(memcached::ready())
		{
			$this->_memcache = new memcached(config('memcached'));
		}
	}
	
	function setTable($table)
	{
		$this->_table = $table;
	}
	
	/**
	 * 获得表名
	 */
	private function getTable()
	{
		return '`'.$this->_table.'`'.(isset($this->_temp['table'])?$this->_temp['table']:'');
	}
	
	/**
	 * 过滤式搜索
	 * @param array $filter
	 * @return \system\core\Ambigous
	 */
	function fetch(array $filter = array())
	{
		$parameter = isset($filter['parameter'])?$filter['parameter']:'*';
		if (isset($filter['start']) && isset($filter['length']))
		{
			$this->limit($filter['start'],$filter['length']);
		}
		elseif (isset($filter['length']))
		{
			$this->limit($filter['length']);
		}
		if (isset($filter['sort']))
		{
			if (is_string($filter['sort']))
			{
				$this->orderby($filter['sort']);
			}
			else if (is_array($filter['sort']))
			{
				if (is_array($filter['sort'][0]))
				{
					foreach ($filter['sort'] as $value)
					{
						$this->orderby($value[0],$value[1]);
					}
				}
				else
				{
					$this->orderby($filter['sort'][0],$filter['sort'][1]);
				}
			}
		}
		$debug = false;
		if (isset($filter['debug']))
			$debug = $filter['debug'];
		return $this->select($parameter,$debug);
	}
	
	/**
	 * 查询一条数据
	 * @param string $parameter
	 * @return Ambigous <NULL, \system\core\Ambigous>
	 */
	function find($parameter = '*')
	{
		$result = $this->limit(1)->select($parameter);
		return isset($result[0])?$result[0]:NULL;
	}

	/**
	 * 载入redis
	 */
	private function __loadRedis()
	{
		
	}

	/**
	 * 查询多条记录
	 * @param string $field
	 * @return Ambigous <boolean, multitype:>
	 */
	public function select($field = '*',$returnSql = false)
	{
		if (is_array($field))
			$field = implode(',', $field);
		$sql = 'select ' . $field . ' from ' . $this->getTable() . ' ' . $this->getWhere() .$this->getGroupby().' '.$this->getOrderby().' '.$this->getLimit();
		if ($returnSql)
			return $sql;
		$result = $this->query($sql, empty($this->_temp['where']) ? array() : $this->_temp['array']);
		unset($this->_temp);
		return $result;
	}
	
	/**
	 * 获取where查询语句
	 * @return string
	 */
	private function getWhere()
	{
		return isset($this->_temp['where'])?$this->_temp['where']:'';
	}
	
	/**
	 * 获取group by语句
	 * @return string
	 */
	private function getGroupby()
	{
		return isset($this->_temp['groupby'])?$this->_temp['groupby']:'';
	}
	
	/**
	 * 获取排序语句
	 * @return string
	 */
	private function getOrderby()
	{
		return isset($this->_temp['orderby'])?$this->_temp['orderby']:'';
	}
	
	/**
	 * 获取sql
	 */
	private function getLimit()
	{
		return isset($this->_temp['limit'])?$this->_temp['limit']:'';
	}
	
	function getSql()
	{
		return $this->_sql;
	}

	/**
	 * 增加条件
	 * 
	 * @param string $sql        	
	 * @param array $array        	
	 * @return \system\core\model
	 */
	public function where($sql, array $array = array(),$combine = 'and')
	{
		//where语句中的in操作符单独使用
		if (substr_count($sql, ' in ')==1)
		{
			if(empty($array))
			{
				return $this;
			}
			$replace = implode(',', array_fill(0, count($array), '?'));
			$sql = str_replace('?', $replace, $sql);
		}
		if (isset($this->_temp['where'])) {
			$this->_temp['where'] = $this->_temp['where'] .' '. $combine.' ' .'('. $sql.')';
		} else {
			$this->_temp['where'] = 'where' . ' (' . $sql.')';
		}
		if (isset($this->_temp['array'])) {
			$this->_temp['array'] = array_merge($this->_temp['array'], $array);
		} else {
			$this->_temp['array'] = $array;
		}
		return $this;
	}
	
	

	/**
	 * 插入
	 * 
	 * @param array $array        	
	 * @return Ambigous <boolean, multitype:>
	 */
	public function insert(array $array,$defualt = NULL,$debug = false)
	{
		$fields = empty($this->getFields($this->_table))?'':'(`'.implode('`,`', $this->getFields($this->_table)).'`)';
		if (!array_key_exists(0, $array))
		{
			//对于非数字下标的数组，重新组合数组，以满足表中的字段顺序
			$temp = [];
			foreach ($this->getFields($this->_table) as $field)
			{
				if (isset($array[$field]))
				{
					$temp[$field] = $array[$field];
				}
				else
				{
					//默认用null填充
					$temp[$field] = $defualt;
				}
			}
			$array = $temp;
		}
		$parameter = '';
		foreach ($array as $key => $value) {
			if (is_int($key)) {
				$parameter .= '?,';
			} else {
				$parameter .= ':' . $key . ',';
			}
		}
		$parameter = rtrim($parameter, ',');
		$sql = 'insert into ' . $this->getTable() .$fields.' values (' . $parameter . ') '.$this->getDuplicate();
		if ($debug)
			return $sql;
		$result = $this->query($sql, $array);
		unset($this->_temp);
		return $result;
	}
	
	/**
	 * 相当于insert into里面的on duplicate key update
	 */
	public function duplicate($key,$value = '')
	{
		if (is_array($key))
		{
			$temp = [];
			foreach ($key as $index => $value)
			{
				$temp[] =  $index.'=\''.$value.'\'';
			}
			
			if (isset($this->_temp['duplicate']))
			{
				$this->_temp['duplicate'] .= ','.implode(',', $temp);
			}
			else
			{
				$this->_temp['duplicate'] = 'on duplicate key update '.implode(',', $temp);
			}
		}
		else
		{
			if (isset($this->_temp['duplicate']))
			{
				$this->_temp['duplicate'] .= ','.$key.'='.$value;
			}
			else
			{
				$this->_temp['duplicate'] = 'on duplicate key update '.$key .'='. $value;
			}
		}
		return $this;
	}
	
	/**
	 * 获取on duplicate key update语句
	 * @return string
	 */
	private function getDuplicate()
	{
		return isset($this->_temp['duplicate'])?$this->_temp['duplicate']:'';
	}

	/**
	 * 更改
	 * 
	 * @param string|array $key
	 * @param string|NULL $value        	
	 * @return Ambigous <boolean, multitype:>
	 */
	public function update($key, $value = '',$check = false)
	{
		if(is_array($key))
		{
			$parameter = '';
			$value = array();
			foreach ($key as $a => $b)
			{
				if ($check)
				{
					if(strpos($a, '.') === false)
					{
						
						if(!in_array($a, $this->getFields($this->_table)))
						{
							continue;
						}
					}
					else
					{
						if(!in_array(substr($a, strpos($a, '.')+1), $this->getFields($this->_table)))
						{
							continue;
						}
					}
				}
				$parameter .= ($a.' = ?,');
				$value[] = $b;
			}
			$parameter = rtrim($parameter,',');
			$sql = 'update '.$this->getTable().' set '.$parameter.' '.$this->getWhere().' '.$this->getLimit();
		}
		else
		{
			if ($check)
			{
				if(strpos($key, '.') === false)
				{
					if(!in_array($key, $this->getFields($this->_table)))
					{
						return false;
					}
				}
				else
				{
					if(!in_array(substr($key, strpos($key, '.')+1), $this->getFields($this->_table)))
					{
						return false;
					}
				}
			}
			$sql = 'update '. $this->getTable(). ' set ' . $key . ' = ? ' . $this->getWhere().' '.$this->getLimit();
			$value = array($value);
		}
		$value = isset($this->_temp['array'])?array_merge($value,$this->_temp['array']):$value;
		$result = $this->query($sql, $value);
		unset($this->_temp);
		return $result;
	}

	/**
	 * 自增
	 * 
	 * @param unknown $key        	
	 * @param number $num        	
	 * @return Ambigous <boolean, multitype:>
	 */
	public function increase($key, $num = 1)
	{
		$sql = 'update ' . $this->_table.(isset($this->_temp['table'])?$this->_temp['table']:'') . ' set ' . $key . ' = ' . $key . ' + ? ' . $this->_temp['where'];
		$result = $this->_db->query($sql, array_merge(array(
			$num
		), $this->_temp['array']));
		unset($this->_temp);
		return $result;
	}

	/**
	 * 删除
	 * 
	 * @return Ambigous <boolean, multitype:>
	 */
	public function delete($table = '')
	{
		$sql = 'delete '.$table.(isset($this->_temp['table'])?$this->_temp['table']:'').' from ' . $this->_table . ' ' . $this->_temp['where'];
		$result = $this->query($sql, $this->_temp['array']);
		unset($this->_temp);
		return $result;
	}
	
	/**
	 * 增加限制规则
	 * @param unknown $start
	 * @param number $length
	 * @return \system\core\model
	 */
	public function limit($start,$length = 0)
	{
		if(empty($length))
			$this->_temp['limit'] = 'limit '.$start;
		else
			$this->_temp['limit'] = 'limit '.$start.','.$length;
		return $this;
	}
	
	/**
	 * 添加排序规则,最先添加的排序规则比重大于后面的排序规则
	 * @param string $field 排序字段
	 * @param string $asc 排序规则 默认为asc从小到大
	 * @return $this
	 */
	public function orderby($field,$asc = 'asc')
	{
		if(isset($this->_temp['orderby']))
		{
			$this->_temp['orderby'] = $this->_temp['orderby'].','.$field.' '.$asc;
		}
		else
		{
			$this->_temp['orderby'] = 'order by '.$field.' '.$asc;
		}
		return $this;
	}
	
	/**
	 * 添加分组查询条件
	 */
	public function groupby($group)
	{
		$this->_temp['groupby'] = ' group by '.$group;
		return $this;
	}
	
	
	/**
	 * 增加搜索表
	 * @param unknown $table
	 * @param string $mode
	 * @param string $on
	 */
	public function table($table,$mode = 'join',$on = '')
	{
		if(!isset($this->_temp['table']))
		{
			$this->_temp['table'] = ' '.$mode.' '.$table.' on '.$on;
		}
		else
		{
			$this->_temp['table'] .= ' '.$mode.' '.$table.' on '.$on;
		}
		return $this;
	}
	
	public function query($sql,array $array = array())
	{
		return $this->_db->query($sql,$array);
	}
	
	public function transaction()
	{
		return $this->_db->transaction();
	}
	
	public function commit()
	{
		return $this->_db->commit();
	}
	
	public function rollback()
	{
		return $this->_db->rollback();
	}
	
	public function lastInsertId($name = NULL)
	{
		return $this->_db->lastInsert($name);
	}
}