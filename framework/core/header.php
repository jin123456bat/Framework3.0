<?php
namespace framework\core;

class header extends base
{
	private static $_header = array();
	
	function __construct()
	{
		$headers = headers_list();
		foreach ($headers as $header)
		{
			$header = explode(":", $header);
			self::$_header[trim(array_shift($header))] = trim(implode(":", $header));
		}
		
	}
	
	/**
	 * 添加一个header
	 * @param unknown $key        	
	 * @param string $value
	 * @example add("Location: http://www.baidu.com");
	 *          add("Location","http://www.baidu.com");
	 */
	function add($key, $value = NULL)
	{
		if (empty($value)) {
			$header = explode(":", $key);
			
			$name = trim(array_shift($header));
			$value = trim(implode(":", $header));
			
			if (isset(self::$_header[$name]))
			{
				if (is_array(self::$_header[$name]))
				{
					self::$_header[$name][] = $value;
				}
				else
				{
					self::$_header[$name] = array(self::$_header[$name],$value);
				}
			}
			else
			{
				self::$_header[$name] = $value;
			}
		} else {
			self::$_header[$key] = $value;
		}
	}

	/**
	 * 检查header是否存在
	 *
	 * @param unknown $string        	
	 * @return boolean
	 */
	function check($string)
	{
		if (isset(self::$_header[$string]))
			return true;
		return in_array($string, self::$_header);
	}

	/**
	 * 删除一个header，假如有同名的 则只会删除一个
	 *
	 * @param unknown $string
	 */
	function delete($string)
	{
		foreach (self::$_header as $key => $value) {
			if ($key === $string) {
				unset(self::$_header[$key]);
				return true;
			}
			
			if ($key . ': ' . $value === $string) {
				unset(self::$_header[$key]);
				return true;
			}
			header_remove(substr($string, 0, strpos($string, ':')));
		}
	}
	
	function set($name,$value)
	{
		self::$_header[$name] = $value;
	}
	
	function get($name)
	{
		return self::$_header[$name];
	}

	/**
	 * 发送一个header
	 *
	 * @param unknown $key        	
	 * @param string $value        	
	 */
	static function send($key, $value = NULL)
	{
		if (empty($value)) {
			header($key, true);
		} else {
			header($key . ': ' . $value, true);
		}
	}

	/**
	 * 发送所有hander
	 */
	function sendAll()
	{
		foreach (self::$_header as $key => $value) {
			header($key . ': ' . $value, true);
		}
	}
}