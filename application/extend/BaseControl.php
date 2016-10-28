<?php
namespace application\extend;

use framework\core\request;
use framework\core\control;
use framework\core\response\json;

abstract class BaseControl extends control
{
	protected $_timemode;
	
	protected $_startTime;
	
	protected $_endTime;
	
	protected $_duration;
	
	protected $_duration_second;
	
	protected function setTime()
	{
		$this->_timemode = request::param('timemode');
		
		switch ($this->_timemode)
		{
			case '1':
				//最近24小时
				$timestamp = (floor(time() / (5*60)) - 1) * 5*60;
				$this->_endTime = date('Y-m-d H:i:s',$timestamp);
				$this->_startTime = date('Y-m-d H:i:s',strtotime('-24 hour',strtotime($this->_endTime)));
				break;
			case '2':
				//昨天
				$this->_startTime = date('Y-m-d 00:00:00',strtotime('-1 day'));
				$this->_endTime = date('Y-m-d 00:00:00');
				break;
			case '3':
				//上周
				$this->_startTime = date('Y-m-d 00:00:00', strtotime('last week'));
				$this->_endTime = date('Y-m-d 00:00:00',strtotime('this week'));
				break;
				//近7天
			case '4':
				$this->_startTime = date('Y-m-d 00:00:00',strtotime('-7 day'));
				$this->_endTime = date('Y-m-d 00:00:00');
				break;
			case '5':
				//最近30天
				$this->_endTime = date('Y-m-d 00:00:00');
				$this->_startTime = date('Y-m-d 00:00:00',strtotime('-30 day'));
				break;
			case '6':
				//上月
				$this->_endTime = date('Y-m-1 00:00:00');
				$this->_startTime = date('Y-m-1 00:00:00',strtotime('last month'));
				break;
			default:
				//自定义时间
				$this->_timemode = NULL;
				$this->_startTime = request::param('starttime');
				$this->_endTime = request::param('endtime');
				break;
		}
		
		if (strtotime($this->_startTime) === false)
		{
			return new json(json::FAILED,'开始时间错误');
		}
		if (strtotime($this->_endTime) ===  false)
		{
			return new json(json::FAILED,'结束时间错误');
		}
		if (strtotime($this->_startTime) >= strtotime($this->_endTime))
		{
			return new json(json::FAILED,'开始时间不能大于等于结束时间');
		}
		$this->_duration = request::param('duration');
		switch ($this->_duration)
		{
			case 'minutely':$this->_duration_second = 60*5;break;
			case 'hourly':$this->_duration_second = 60*60;break;
			case 'daily':$this->_duration_second = 60*60*24;break;
			default:
				return new json(json::FAILED,'duration参数错误');
		}
	}
}