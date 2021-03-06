<?php
namespace framework\core\response;

use framework\core\response;
use framework\core\http;
use framework\core\validator;

/**
 * 302跳转
 * @author fx
 */
class url extends response
{

	private $_control;

	private $_action;

	/**
	 * url中的其他参数
	 * 
	 * @var array
	 */
	private $_parameter = array();

	function __construct($control = '', $action = '', $parameter = array())
	{
		$this->_control = $control;
		$this->_action = $action;
		$this->_parameter = $parameter;
		parent::__construct();
	}

	function initlize()
	{
		$this->setHttpStatus(302);
		$this->setHeader('Location', $this->__toString());
		$this->setBody('');
	}

	function __toString()
	{
		if (validator::url($this->_control))
		{
			return $this->_control;
		}
		else
		{
			return http::url($this->_control,$this->_action,$this->_parameter);
		}
	}
}