<?php
namespace system\core;
class websocket
{
	private $_config;
	
	private $_connection;
	
	private $_error = [];
	
	private $_socket = [];
	
	function __construct($config = NULL)
	{
		$this->_config = $config;
	}
	
	function connection($address = '',$port = 80)
	{
		if (!isset($this->_socket[md5($address.$port)]) || empty($this->_socket[md5($address.$port)]))
		{
			$result = true;
			if (empty($address))
				$address = $_SERVER['HTTP_HOST'];
			$this->_connection = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if (!$this->_connection)
			{
				$result = false;
			}
			if(!socket_set_option($this->_connection, SOL_SOCKET, SO_REUSEADDR, 1))
			{
				$result = false;
			}
			if(!socket_bind($this->_connection, $address, $port))
			{
				$result = false;
			}
			if(socket_listen($this->_connection, 2))
			{
				$result = false;
			}
			if (!$result)
			{
				$this->_error['socket_last_error'] = socket_last_error($this->_connection);
				$this->_error['socket_strerror'] = socket_strerror($this->_error['socket_last_error']);
			}
			
			$this->_socket[md5($address.$port)] = $this->_connection;
		}
		return $this->_socket[md5($address.$port)];
	}
	
	function read($socket,$length = 2048)
	{
		$bytes = socket_recv($socket,$buffer,$length);
		if(!empty($bytes))
		{
			return $this->decode($buffer);
		}
		return false;
	}
	
	/**
	 * 向socket中写入数据
	 * @param unknown $socket
	 * @param unknown $data
	 * @return number
	 */
	function write($socket,$data)
	{
		return socket_write(socket,$data, strlen($data));
	}
	
	/**
	 * 关闭socket连接
	 * @param unknown $socket
	 */
	function close($socket)
	{
		foreach ($this->_socket as $index => $sock)
		{
			if ($socket === $sock)
			{
				unset($this->_socket[$index]);
				return socket_close($socket);
			}
		}
		return false;
	}
	
	/**
	 * 获取websocket版本
	 * @return Ambigous <string, unknown>
	 */
	function version()
	{
		return isset($_SERVER['HTTP_SEC_WEBSOCKET_VERSION'])?$_SERVER['HTTP_SEC_WEBSOCKET_VERSION']:'';
	}
	
	/**
	 * 获取websocket连接key
	 * @return Ambigous <string, unknown>
	 */
	function getRequestKey()
	{
		return isset($_SERVER['HTTP_SEC_WEBSOCKET_KEY'])?$_SERVER['HTTP_SEC_WEBSOCKET_KEY']:'';
	}
	
	/**
	 * 获取要响应response中的key
	 * @param string $guid
	 */
	function getResponseKey($guid = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
	{
		base64_encode(sha1($this->getRequestKey().$guid,true));
	}
	
	/**
	 * 获取要添加到header中的数据
	 */
	function header()
	{
		return [
			'Upgrade'=> 'websocket',
			'Connection' => 'Upgrade',
			'Sec-WebSocket-Accept' => $this->getResponseKey(),
		];
	}
	
	private function decode($buffer)
	{
		return $buffer;
	}
	
	/**
	 * 假如连接函数返回了false 可以通过这个函数获取失败信息
	 * @return Ambigous <multitype:, number>
	 */
	function get_error()
	{
		return $this->_error;
	}
}