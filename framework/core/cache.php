<?php
namespace framework\core;

use framework;

class cache extends component
{

	private static $_instance;

	private static $_expires;

	protected static function init()
	{
		$config = self::getConfig('cache');
		self::$_expires = isset($config['expires']) ? $config['expires'] : 0;
		$type = isset($config['type']) ? $config['type'] : 'mysql';
		if (! (isset(self::$_instance[$type]) && ! empty(self::$_instance[$type])))
		{
			$cache = 'framework\\core\\cache\\driver\\' . $type;
			self::$_instance[$type] = new $cache($config);
		}
		return self::$_instance[$type];
	}

	public static function __callstatic($name, $args)
	{
		return self::$name($args);
	}

	/**
	 * 设置默认的数据有效期
	 * @param unknown $expires        	
	 */
	static function setExpires($expires)
	{
		self::$_expires = $expires;
	}

	/**
	 * 设置或者更新数据
	 *
	 * @param unknown $name 数据名称
	 * @param unknown $value 数据值	
	 * @param number $cache 数据有效期 当为0的时候使用默认的数据有效期
	 */
	static function set($name, $value, $expires = 0)
	{
		$app = self::getConfig('app');
		if (isset($app['cache']) && $app['cache'])
		{
			$cacheInstance = self::init();
			$config = self::getConfig('cache');
			$expires = empty($expires) ? self::$_expires : $expires;
			return $cacheInstance->set($name, $value, $expires);
		}
		return false;
	}
	
	/**
	 * 自增
	 * @param unknown $name
	 * @param number $amount
	 * @return bool true on success or false on failure
	 */
	static function increase($name,$amount = 1)
	{
		$app = self::getConfig('app');
		if (isset($app['cache']) && $app['cache'])
		{
			$cacheInstance = self::init();
			return $cacheInstance->increase($name,$amount);
		}
		return null;
	}
	
	/**
	 * 自减
	 * @param unknown $name
	 * @param number $amount
	 * @return bool true on success or false on failure
	 */
	static function decrease($name,$amount = 1)
	{
		$app = self::getConfig('app');
		if (isset($app['cache']) && $app['cache'])
		{
			$cacheInstance = self::init();
			return $cacheInstance->decrease($name,$amount);
		}
		return null;
	}

	/**
	 * 获取数据
	 *
	 * @param unknown $name
	 * @param $default NULL 当数据不存在的时候的默认值
	 * @return mixed|unknown
	 */
	static function get($name,$default = NULL)
	{
		$app = self::getConfig('app');
		if (isset($app['cache']) && $app['cache'])
		{
			$cacheInstance = self::init();
			$value = $cacheInstance->get($name);
			if ($value === NULL)
			{
				return $default;
			}
			return $value;
		}
		return null;
	}
	
	/**
	 * 删除缓存
	 * @param string $name
	 */
	static function remove($name)
	{
		$app = self::getConfig('app');
		if (isset($app['cache']) && $app['cache'])
		{
			$cacheInstance = self::init();
			return $cacheInstance->remove($name);
		}
		return false;
	}
	
	/**
	 * 清空所有缓存
	 */
	static function flush()
	{
		$app = self::getConfig('app');
		if (isset($app['cache']) && $app['cache'])
		{
			$cacheInstance = self::init();
			return $cacheInstance->flush();
		}
		return false;
	}
	
	/**
	 * 判断缓存是否存在
	 * @param string $name
	 * @return bool
	 */
	static function has($name)
	{
		$app = self::getConfig('app');
		if (isset($app['cache']) && $app['cache'])
		{
			$cacheInstance = self::init();
			return $cacheInstance->has($name);
		}
		return false;
	}
	
	/**
	 * 和set相同，不同的是假如原来的name存在了，会失败，并且返回false
	 * @param unknown $name
	 * @param unknown $value
	 * @return boolean
	 */
	static function add($name,$value)
	{
		$app = self::getConfig('app');
		if (isset($app['cache']) && $app['cache'])
		{
			$cacheInstance = self::init();
			$config = self::getConfig('cache');
			$expires = empty($expires) ? self::$_expires : $expires;
			return $cacheInstance->add($name, $value, $expires);
		}
		return false;
	}
	
	/**
	 * 尚未实现
	 * 强制使用某一个服务器
	 */
	static function server()
	{
		
	}
}
