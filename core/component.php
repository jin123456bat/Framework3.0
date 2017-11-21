<?php
namespace framework\core;

/**
 * 组件基类
 * @author fx
 */
class component extends base
{

	private static $_config = array();

	function __construct()
	{
		parent::__construct();
	}

	function initlize()
	{
		return parent::initlize();
	}

	/**
	 * 载入组件配置
	 * @param boolean $is_framework        
	 */
	function setConfig($is_framework = true)
	{
		$root = $is_framework ? SYSTEM_ROOT : APP_ROOT;
		// 用户配置
		$config_path = $root . '/config/';
		foreach (scandir($config_path) as $config_file)
		{
			if ($config_file != '.' && $config_file != '..')
			{
				$config = include $config_path . $config_file;
				if (is_array($config) && ! empty($config))
				{
					$name = pathinfo($config_file, PATHINFO_FILENAME);
					if (isset(self::$_config[$name]) && is_array(self::$_config[$name]))
					{
						self::$_config[$name] = array_merge(self::$_config[$name], $config);
					}
					else
					{
						self::$_config[$name] = $config;
					}
				}
			}
		}
	}

	/**
	 * 替换配置
	 * @param unknown $key        
	 * @param unknown $value        
	 * @param string $file        
	 * @return boolean
	 */
	final public function replaceConfig($key, $value, $file = '')
	{
		if (empty($file))
		{
			$file = base::$APP_CONF;
		}
		self::$_config[$file][$key] = $value;
		return true;
	}

	/**
	 * 获取组件配置
	 */
	public static function getConfig($name = null)
	{
		if ($name !== null)
		{
			if (isset(self::$_config[$name]))
			{
				return self::$_config[$name];
			}
			return NULL;
		}
		else
		{
			return self::$_config;
		}
	}
}
