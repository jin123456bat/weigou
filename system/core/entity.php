<?php
namespace system\core;

class entity extends base
{
	private $_pk;
	
	private $_data;
	
	private $_has_error = false;
	
	private $_errors;
	
	private $_rule_type = '';
	
	/**
	 * @param string $pk 主键名称
	 */
	function __construct($pk = 'id')
	{
		$this->_pk = $pk;
	}
	
	private function getClassName()
	{
		return end(explode('\\', get_class($this)));
	}
	
	function setRuleType($type)
	{
		$this->_rule_type = $type;
	}
	
	/**
	 * 保存
	 * @return boolean|\system\core\Ambigous
	 */
	function save()
	{
		if (empty($this->getPrimaryKey()))
		{
			$data = $this->getData();
			$on_insert_struct_value = array();
			$on_insert_struct = array();
			foreach ($data as $key => $value)
			{
				if (method_exists($this, '__'.$key))
				{
					$on_insert_struct[$key] = '__'.$key;
					$on_insert_struct_value[$key] = $value;
					unset($data[$key]);
				}
			}
			$this->model($this->getClassName())->transaction();
			if($this->model($this->getClassName())->insert($data))
			{
				$this->_data[$this->_pk] = $this->model($this->getClassName())->lastInsertId();
				foreach ($on_insert_struct as $key => $struct)
				{
					$struct = call_user_func(array($this,$struct),$this->getPrimaryKey(),$on_insert_struct_value[$key]);
					foreach ($struct as $tableName => $d_data)
					{
						if (isset($d_data['insert']) && is_array($d_data['insert']))
						{
							foreach ($d_data['insert'] as $insert)
							{
							 	if(!$this->model($tableName)->insert($insert))
							 	{
							 		$this->model($this->getClassName())->rollback();
							 		return false;
							 	}
							}
						}
					}
					
				}
				$this->model($this->getClassName())->commit();
				return true;
			}
			$this->model($this->getClassName())->rollback();
			return false;
		}
		else
		{
			$pk = $this->getPrimaryKeyName();
			$data = $this->getData();
			$on_delete_struct = array();
			$on_delete_struct_value = array();
			foreach ($data as $key => $value)
			{
				if (method_exists($this, '__'.$key))
				{
					$on_delete_struct[$key] = '__'.$key;
					$on_delete_struct_value[$key] = $value;
					unset($data[$key]);
				}
			}
			
			$this->model($this->getClassName())->transaction();
			//先更新
			$this->model($this->getClassName())->where($pk.'=?',[$this->getPrimaryKey()])->limit(1)->update($data);
			foreach ($on_delete_struct as $key => $struct)
			{
				$struct = call_user_func(array($this,$struct),$this->getPrimaryKey(),$on_delete_struct_value[$key]);
				foreach ($struct as $tableName => $d_data)
				{
					//先删除
					if (isset($d_data['delete']) && is_array($d_data['delete']))
					{
						foreach ($d_data['delete'] as $field => $field_value)
						{
							$this->model($tableName)->where($field.'=?',array($field_value));
						}
						$this->model($tableName)->delete();
					}
					//在添加
					if (isset($d_data['insert']) && is_array($d_data['insert']))
					{
						foreach ($d_data['insert'] as $insert)
						{
							if(!$this->model($tableName)->insert($insert))
							{
								$this->model($this->getClassName())->rollback();
								return false;
							}
						}
					}
				}
			}
			$this->model($this->getClassName())->commit();
			return true;
		}
	}
	
	/**
	 * 删除
	 * @return \system\core\Ambigous
	 */
	function delete()
	{
		$pk = $this->getPrimaryKeyName();
		return $this->model($this->getClassName())->where($pk.'=?',[$this->getPrimaryKey()])->limit(1)->delete();
	}
		
	/**
	 * 获取主键名
	 * @return string
	 */
	function getPrimaryKeyName()
	{
		return $this->_pk;
	}
		
	/**
	 * 获取主键值
	 * @return unknown
	 */
	function getPrimaryKey()
	{
		return $this->_data[$this->_pk];
	}
	
	/**
	 * 获取数据源
	 * @return unknown
	 */
	function getData()
	{
		return $this->_data;
	}
	
	/**
	 * 设置数据源
	 * @param unknown $data
	 */
	function setData($data)
	{
		$this->_data = $data;
	}
	
	/**
	 * 按照key/value的格式添加一个数据，已经存在的key会被覆盖
	 * @param unknown $key
	 * @param unknown $value
	 */
	function addData($key,$value)
	{
		$this->_data[$key] = $value;
	}
	
	/**
	 * 删除一个数据
	 * @param unknown $key
	 */
	function removeData($key)
	{
		unset($this->_data[$key]);
	}
	
	function __rule()
	{
		return array();
	}
	
	/**
	 * 是否有错误
	 * @return boolean
	 */
	function hasError()
	{
		return $this->_has_error;
	}
	
	/**
	 * 获取错误信息
	 */
	function getErrors()
	{
		return $this->_errors;
	}
	
	function addError($key,$message)
	{
		$this->_has_error = true;
		if (is_array($this->_errors[$key]))
		{
			$this->_errors[$key][] = $message;
		}
		else
		{
			$this->_errors[$key] = [$message];
		}
	}
	
	/**
	 * 数据验证
	 * @return boolean
	 */
	function validate()
	{
		$validate_return = true;
		$rules = $this->__rule();
		$entityFilter = new entityFilter();
		foreach ($rules as $rule)
		{
			if (isset($rule['type']) && !empty($this->_rule_type))
			{
				if ($rule['type'] != $this->_rule_type)
				{
					continue;
				}
			}
			$key = key($rule);
			$current = current($rule);
			$data = $this->getData();
			$filters = explode(',', $current);
			foreach ($filters as $filter)
			{
				switch (strtolower($filter))
				{
					case 'unique':
						if(!empty($this->model($this->getClassName())->where($key.'=?',[$data[$key]])->find()))
						{
							$this->addError($key, $rule['message']);
							$validate_return = false;
						}
						break;
					default:
						$filterName = $filter.'Filter';
						if (method_exists($entityFilter, $filterName))
						{
							$result = call_user_func([$entityFilter,$filterName],$data[$key],$rule);
							if (!$result)
							{
								$this->addError($key, $rule['message']);
								$validate_return = false;
							}
						}
				}
				
			}
		}
		return $validate_return;
	}
}