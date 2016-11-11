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

	/**
	 * 上一个执行的sql语句
	 * @var unknown
	 */
	private $_sql;

	/**
	 * 存储了表中对应的字段名
	 * @var unknown
	 */
	private $_fields;
	
	/**
	 * 存储了desc tableName后的结果
	 * @var unknown
	 */
	private $_desc;

	function __construct($table)
	{
		$this->_table = $table;
		$this->__loadDB();
		$this->__loadMemcache();
		$this->__loadRedis();
		
		$this->initlize();
	}

	private function initlize()
	{
		$this->_desc = $this->_db->query('desc `' . trim($this->_table,' `').'`');
		foreach ($this->_desc as $r)
		{
			$this->_fields[$this->_table][] = $r['Field'];
		}
	}

	public function model($table)
	{
		static $array = [];
		if (! isset($array[$table]) || empty($array[$table]))
		{
			$array[$table] = new model($table);
		}
		return $array[$table];
	}

	public function getFields($table = '')
	{
		$table = trim($table, '`');
		return empty($table) ? $this->_fields : $this->_fields[$table];
	}

	public function setFields($fields, $table = '')
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
		if (memcached::ready())
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
		return '`' . $this->_table . '`' . (isset($this->_temp['table']) ? $this->_temp['table'] : '');
	}

	/**
	 * 过滤式搜索
	 * 
	 * @param array $filter        	
	 * @return \system\core\Ambigous
	 */
	function fetch(array $filter = array())
	{
		$parameter = isset($filter['parameter']) ? $filter['parameter'] : '*';
		if (isset($filter['start']) && isset($filter['length']))
		{
			$this->limit($filter['start'], $filter['length']);
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
						$this->orderby($value[0], $value[1]);
					}
				}
				else
				{
					$this->orderby($filter['sort'][0], $filter['sort'][1]);
				}
			}
		}
		$debug = false;
		if (isset($filter['debug']))
			$debug = $filter['debug'];
		return $this->select($parameter, $debug);
	}

	/**
	 * 查询一条数据
	 * 
	 * @param string $parameter        	
	 * @return Ambigous <NULL, \system\core\Ambigous>
	 */
	function find($parameter = '*')
	{
		$result = $this->limit(1)->select($parameter);
		return isset($result[0]) ? $result[0] : NULL;
	}

	/**
	 * 载入redis
	 */
	private function __loadRedis()
	{
	
	}

	/**
	 * 查询多条记录
	 * 
	 * @param string $field        	
	 * @return Ambigous <boolean, multitype:>
	 */
	public function select($field = '*', $returnSql = false)
	{
		if (is_array($field))
			$field = implode(',', $field);
		$sql = 'select ' . $field . ' from ' . $this->getTable() . ' ' . $this->getWhere() . $this->getGroupby() . ' ' . $this->getOrderby() . ' ' . $this->getLimit();
		
		if ($returnSql)
			
			return $sql;
		
		$result = $this->query($sql, empty($this->_temp['where']) ? array() : $this->_temp['array']);
		unset($this->_temp);
		return $result;
	}

	/**
	 * 获取where查询语句
	 * 
	 * @return string
	 */
	private function getWhere()
	{
		return isset($this->_temp['where']) ? $this->_temp['where'] : '';
	}

	/**
	 * 获取group by语句
	 * 
	 * @return string
	 */
	private function getGroupby()
	{
		return isset($this->_temp['groupby']) ? $this->_temp['groupby'] : '';
	}

	/**
	 * 获取排序语句
	 * 
	 * @return string
	 */
	private function getOrderby()
	{
		return isset($this->_temp['orderby']) ? $this->_temp['orderby'] : '';
	}

	/**
	 * 获取sql
	 */
	private function getLimit()
	{
		return isset($this->_temp['limit']) ? $this->_temp['limit'] : '';
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
	public function where($sql, array $array = array(), $combine = 'and')
	{
		// where语句中的in操作符单独使用
		if (substr_count($sql, ' in ') == 1)
		{
			if (empty($array))
			{
				return $this;
			}
			$pattern = '$in\s*\(\s*\?\s*\)$';
			if (preg_match($pattern, $sql, $inSql))
			{
				$inSql = $inSql[0];
				$replace = ' in (' . implode(',', array_fill(0, count($array), '?')) . ')';
				$sql = str_replace($inSql, $replace, $sql);
			}
		}
		if (isset($this->_temp['where']))
		{
			$this->_temp['where'] = $this->_temp['where'] . ' ' . $combine . ' ' . '(' . $sql . ')';
		}
		else
		{
			$this->_temp['where'] = 'where' . ' (' . $sql . ')';
		}
		if (isset($this->_temp['array']))
		{
			$this->_temp['array'] = array_merge($this->_temp['array'], $array);
		}
		else
		{
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
	public function insert(array $array, $defualt = NULL,$debug = false)
	{
		$fields = empty($this->getFields($this->getTable())) ? '' : ' (`' . implode('`,`', $this->getFields($this->getTable())) . '`)';
		
		//是否是数字下标
		$source_keys = array_keys($array);
		$des_keys = range(0, count($array)-1,1);
		$diff = array_diff($source_keys,$des_keys);
		$is_num_index = empty($diff);
		
		//对于非数字下标的一些初始化检查
		if (!$is_num_index)
		{
			//去除多余的字段
			foreach ($array as $index=>$value)
			{
				if (!in_array($index, $this->getFields($this->getTable())))
				{
					unset($array[$index]);
				}
			}
			//填充默认的字段
			foreach ($this->_desc as $index=>$value)
			{
				if (!isset($array[$value['Field']]))
				{
					if ($value['Default'] === NULL)
					{
						if ($value['Null'] == 'YES')
						{
							$array[$value['Field']] = NULL;
						}
						else
						{
							switch ($value['Type'])
							{
								case 'datetime':
									$array[$value['Field']] = date('Y-m-d H:i:s');
									break;
								case 'timestamp':
									$array[$value['Field']] = date('Y-m-d H:i:s');
									break;
								case 'date':
									$array[$value['Field']] = date('Y-m-d');
									break;
								default:
									$zero = '$int\(\d+\)$';
									$empty_string = '$(char)?(text)?$';
									if (preg_match($zero, $value['Type']))
									{
										$array[$value['Field']] = 0;
									}
									else if (preg_match($empty_string, $value['Type']))
									{
										$array[$value['Field']] = '';
									}
							}
						}
					}
					else
					{
						$array[$value['Field']] = $value['Default'];
					}
				}
			}
		}
		
		$parameter = '';
		foreach ($array as $key => $value)
		{
			if (is_int($key))
			{
				$parameter .= '?,';
			}
			else
			{
				$parameter .= ':' . $key . ',';
			}
		}
		$parameter = rtrim($parameter, ',');
		$sql = 'insert into ' . $this->getTable() . $fields . ' values (' . $parameter . ') ' . $this->getDuplicate();
		if ($debug)
			return $sql;
		$result = $this->query($sql, $array);
		unset($this->_temp);
		return $result;
	}

	/**
	 * 相当于insert into里面的on duplicate key update
	 */
	public function duplicate($key, $value = '')
	{
		if (is_array($key))
		{
			$temp = [];
			foreach ($key as $index => $value)
			{
				$temp[] = $index . '=\'' . $value . '\'';
			}
			
			if (isset($this->_temp['duplicate']))
			{
				$this->_temp['duplicate'] .= ',' . implode(',', $temp);
			}
			else
			{
				$this->_temp['duplicate'] = 'on duplicate key update ' . implode(',', $temp);
			}
		}
		else
		{
			if (isset($this->_temp['duplicate']))
			{
				$this->_temp['duplicate'] .= ',' . $key . '=' . $value;
			}
			else
			{
				$this->_temp['duplicate'] = 'on duplicate key update ' . $key . '=' . $value;
			}
		}
		return $this;
	}

	/**
	 * 获取on duplicate key update语句
	 * 
	 * @return string
	 */
	private function getDuplicate()
	{
		return isset($this->_temp['duplicate']) ? $this->_temp['duplicate'] : '';
	}

	/**
	 * 更改
	 *
	 * @param string|array $key        	
	 * @param string|NULL $value        	
	 * @return Ambigous <boolean, multitype:>
	 */
	public function update($key, $value = '', $check = false)
	{
		if (is_array($key))
		{
			$parameter = '';
			$value = array();
			foreach ($key as $a => $b)
			{
				if ($check)
				{
					if (strpos($a, '.') === false)
					{
						
						if (! in_array($a, $this->getFields($this->_table)))
						{
							continue;
						}
					}
					else
					{
						if (! in_array(substr($a, strpos($a, '.') + 1), $this->getFields($this->_table)))
						{
							continue;
						}
					}
				}
				$parameter .= ($a . ' = ?,');
				$value[] = $b;
			}
			$parameter = rtrim($parameter, ',');
			$sql = 'update ' . $this->getTable() . ' set ' . $parameter . ' ' . $this->getWhere() . ' ' . $this->getLimit();
		}
		else
		{
			if ($check)
			{
				if (strpos($key, '.') === false)
				{
					if (! in_array($key, $this->getFields($this->_table)))
					{
						return false;
					}
				}
				else
				{
					if (! in_array(substr($key, strpos($key, '.') + 1), $this->getFields($this->_table)))
					{
						return false;
					}
				}
			}
			$sql = 'update ' . $this->getTable() . ' set ' . $key . ' = ? ' . $this->getWhere() . ' ' . $this->getLimit();
			$value = array(
				$value
			);
		}
		$value = isset($this->_temp['array']) ? array_merge($value, $this->_temp['array']) : $value;
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
		$sql = 'update ' . $this->_table . (isset($this->_temp['table']) ? $this->_temp['table'] : '') . ' set ' . $key . ' = ' . $key . ' + ? ' . $this->_temp['where'];
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
		$sql = 'delete ' . $table . (isset($this->_temp['table']) ? $this->_temp['table'] : '') . ' from ' . $this->_table . ' ' . $this->_temp['where'];
		$result = $this->query($sql, empty($this->_temp['array']) ? array() : $this->_temp['array']);
		unset($this->_temp);
		return $result;
	}

	/**
	 * 增加限制规则
	 * 
	 * @param unknown $start        	
	 * @param number $length        	
	 * @return \system\core\model
	 */
	public function limit($start, $length = 0)
	{
		if (empty($length))
			$this->_temp['limit'] = 'limit ' . $start;
		else
			$this->_temp['limit'] = 'limit ' . $start . ',' . $length;
		return $this;
	}

	/**
	 * 添加排序规则,最先添加的排序规则比重大于后面的排序规则
	 * 
	 * @param string $field
	 *        	排序字段
	 * @param string $asc
	 *        	排序规则 默认为asc从小到大
	 * @return $this
	 */
	public function orderby($field, $asc = 'asc')
	{
		if (isset($this->_temp['orderby']))
		{
			$this->_temp['orderby'] = $this->_temp['orderby'] . ',' . $field . ' ' . $asc;
		}
		else
		{
			$this->_temp['orderby'] = 'order by ' . $field . ' ' . $asc;
		}
		return $this;
	}

	/**
	 * 添加分组查询条件
	 */
	public function groupby($group)
	{
		$this->_temp['groupby'] = ' group by ' . $group;
		return $this;
	}

	/**
	 * 增加搜索表
	 * 
	 * @param unknown $table        	
	 * @param string $mode        	
	 * @param string $on        	
	 */
	public function table($table, $mode = 'join', $on = '')
	{
		if (! isset($this->_temp['table']))
		{
			$this->_temp['table'] = ' ' . $mode . ' ' . $table . ' on ' . $on;
		}
		else
		{
			$this->_temp['table'] .= ' ' . $mode . ' ' . $table . ' on ' . $on;
		}
		return $this;
	}

	public function query($sql, array $array = array())
	{
		return $this->_db->query($sql, $array);
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