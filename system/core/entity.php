<?php
namespace system\core;

class entity extends base
{
	private $_pk;
	
	private $_data;
	
	private $_has_error = false;
	
	private $_errors;
	
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
	
	function save()
	{
		if (empty($this->getPrimaryKey()))
		{
			if($this->model($this->getClassName())->insert($this->getData()))
			{
				$this->_data[$this->_pk] = $this->model($this->getClassName())->lastInsertId();
				return true;
			}
			return false;
		}
		else
		{
			$pk = $this->getPrimaryKeyName();
			return $this->model($this->getClassName())->where($pk.'=?',[$this->getPrimaryKey()])->limit(1)->update($this->getData());
		}
	}
	
	function delete()
	{
		$pk = $this->getPrimaryKeyName();
		return $this->model($this->getClassName())->where($pk.'=?',[$this->getPrimaryKey()])->limit(1)->delete();
	}
		
	function getPrimaryKeyName()
	{
		return $this->_pk;
	}
		
	function getPrimaryKey()
	{
		return $this->_data[$this->_pk];
	}
	
	function getData()
	{
		return $this->_data;
	}
	
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
	
	function validate()
	{
		$validate_return = true;
		$rules = $this->__rule();
		$entityFilter = new entityFilter();
		foreach ($rules as $rule)
		{
			$key = key($rule);
			$current = current($rule);
			$data = $this->getData();
			$filters = explode(',', $current);
			foreach ($filters as $filter)
			{
				$filterName = $filter.'Filter';
				if (method_exists($entityFilter, $filterName))
				{
					if (isset($data[$key]))
					{
						$result = call_user_func([$entityFilter,$filterName],$data[$key],$rule);
						if (!$result)
						{
							$this->_has_error = true;
							if (isset($rule['message']))
							{
								$this->_errors[$key] = $rule['message'];
								$validate_return = false;
							}
						}
					}
				}
			}
		}
		return $validate_return;
	}
}