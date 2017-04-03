<?php
namespace system\core;
class SessionHandler implements \SessionHandlerInterface
{
	/**
	 * {@inheritDoc}
	 * @see SessionHandlerInterface::open()
	 */
	public function open($save_path, $name)
	{
		//这里是session_start的时候调用的函数，
		return 0;//成功返回0
		
		return 1;//失败返回1
	}

	/**
	 * {@inheritDoc}
	 * @see SessionHandlerInterface::close()
	 */
	public function close()
	{
		//当调用session_write_close的时候调用
		
		return 0;//成功返回0
		
		return 1;//失败返回1
	}

	/**
	 * {@inheritDoc}
	 * @see SessionHandlerInterface::read()
	 */
	public function read($session_id)
	{
		// TODO Auto-generated method stub
		//通过session_id如何获取到session中的数据，这里写从memcached中读取数据，记住，返回一个字符串，加入获取失败也返回一个空字符串
		return '';
	}

	/**
	 * {@inheritDoc}
	 * @see SessionHandlerInterface::write()
	 */
	public function write($session_id, $session_data)
	{
		// TODO Auto-generated method stub
		//写入session数据,$session_data是一个字符串
		
		return 0;//成功返回0
		
		return 1;//失败返回1
	}

	/**
	 * {@inheritDoc}
	 * @see SessionHandlerInterface::destroy()
	 */
	public function destroy($session_id)
	{
		// TODO Auto-generated method stub
		return 0;//成功返回0
		
		return 1;//失败返回1
	}

	/**
	 * {@inheritDoc}
	 * @see SessionHandlerInterface::gc()
	 */
	public function gc($maxlifetime)
	{
		// TODO Auto-generated method stub
		return 0;//成功返回0
		
		return 1;//失败返回1
	}

	
}