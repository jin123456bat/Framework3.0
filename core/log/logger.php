<?php
namespace framework\core\log;

/**
 * 日志写入器的接口
 */
interface logger
{

	/**
	 * 系统不可用
	 * 
	 * @param string $message        
	 * @param array $context        
	 * @return null
	 */
	public function emergency($message, array $context = array());

	/**
	 * **必须**立刻采取行动
	 * 例如：在整个网站都垮掉了、数据库不可用了或者其他的情况下，**应该**发送一条警报短信把你叫醒。
	 * 
	 * @param string $message        
	 * @param array $context        
	 * @return null
	 */
	public function alert($message, array $context = array());

	/**
	 * 紧急情况
	 * 例如：程序组件不可用或者出现非预期的异常。
	 * 
	 * @param string $message        
	 * @param array $context        
	 * @return null
	 */
	public function critical($message, array $context = array());

	/**
	 * 运行时出现的错误，不需要立刻采取行动，但必须记录下来以备检测。
	 * 
	 * @param string $message        
	 * @param array $context        
	 * @return null
	 */
	public function error($message, array $context = array());

	/**
	 * 出现非错误性的异常。
	 * 例如：使用了被弃用的API、错误地使用了API或者非预想的不必要错误。
	 * 
	 * @param string $message        
	 * @param array $context        
	 * @return null
	 */
	public function warning($message, array $context = array());

	/**
	 * 一般性重要的事件。
	 * 
	 * @param string $message        
	 * @param array $context        
	 * @return null
	 */
	public function notice($message, array $context = array());

	/**
	 * 重要事件
	 * 例如：用户登录和SQL记录。
	 * 
	 * @param string $message        
	 * @param array $context        
	 * @return null
	 */
	public function info($message, array $context = array());

	/**
	 * debug 详情
	 * 
	 * @param string $message        
	 * @param array $context        
	 * @return null
	 */
	public function debug($message, array $context = array());

	/**
	 * 任意等级的日志记录
	 * 
	 * @param mixed $level        
	 * @param string $message        
	 * @param array $context        
	 * @return null
	 */
	public function log($level, $message, array $context = array());
}
