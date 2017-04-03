<?php
abstract class Data implements IData
{
	public abstract function init();
	
	private $_data;
	
	public function getData()
	{
		return $this->_data;
	}
	
	protected function setData($data)
	{
		$this->_data = $data;
	}
}