<?php
namespace application\helper;

class oms
{
	private $_des_key = '4QEzW4RWiwbb150901092132';
	
	private $_md5_key = '$oC@Mj%gts#0Ufr7J$J$J2kkI!WxF3*XKvAx7i9T';

	function getDesKey()
	{
		return $this->_des_key;
	}
	
	function getMd5Key()
	{
		return $this->_md5_key;
	}
	
	/**
	 * 数据加密 返回加密后的内容
	 * @param unknown $result
	 * @param string $content
	 * @return string
	 */
	function encrypt($result = false,$content = '')
	{
		$xml_data_xml = '<?xml version="1.0" encoding="utf-8"?>' .
			'<Response>' .
			'<success>' . ($result ? 'true' : 'false') . '</success>' .
			'<reason>' . (empty($content) ? '失败' : $content) . '</reason>' .
			'</Response>';
		return (base64_encode($this->_encryption($xml_data_xml, $this->getDesKey())) . "|" . base64_encode(md5($xml_data_xml . '&' . $this->getMd5Key())));
	}
	
	/**
	 * 解密数据 解密成功返回数据原始内容  否则返回false
	 * @param unknown $uncode
	 * @param unknown $appid
	 * @param unknown $xml
	 * @param unknown $md5
	 * @return mixed
	 */
	function desrypt($uncode,$appid,$xml,$md5)
	{
		$xml = $this->_decrypt(base64_decode($xml), $this->getDesKey());
		
		if ($this->getVerification($xml, base64_decode($md5), $this->getMd5Key()))
		{
			return xmlToArray($xml);
		}
		return false;
	}
	
	/**
	 * 加密
	 * @copyright (c) 2015-10-15, coolzbw
	 * @param string $data 内容
	 * @param string $des_key DES秘钥
	 * @return string
	 */
	private function _encryption($data, $des_key) {
		return mcrypt_encrypt(MCRYPT_3DES, $des_key, $data, MCRYPT_MODE_ECB);
	}
	
	/**
	 * 解密
	 * @copyright (c) 2015-10-15, coolzbw
	 * @param string $data 内容
	 * @param string $des_key DES秘钥
	 * @return string
	 */
	private function _decrypt($data, $des_key) {
		$ret = mcrypt_decrypt(MCRYPT_3DES, $des_key, $data, MCRYPT_MODE_ECB);
		return rtrim($ret, "\0");
	}
	
	/**
	 * @version 验证数据合法性
	 * @copyright (c) 2015-10-15, coolzbw
	 * @param $original_text 报文原文
	 * @param $server_sign 加密报文
	 * @param string $md5_key 应用秘钥
	 * @return bool
	 */
	private function getVerification($original_text, $server_sign, $md5_key) {
		$local_sign = md5($original_text . "&" . $md5_key);
		return ($local_sign == $server_sign) ? TRUE : FALSE;
	}
}