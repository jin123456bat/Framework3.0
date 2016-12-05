<?php
namespace application\algorithm;

use framework\core\model;
use application\extend\cache;
use application\extend\BaseComponent;
use framework\core\database\sql;

class algorithm extends BaseComponent
{
	private $_duration = 0;
	
	private $_starttime = '';
	
	private $_endtime = '';
	
	/**
	 * constructor
	 * @param unknown $starttime 开始时间点
	 * @param unknown $endtime 结束时间点
	 * @param unknown $duration 时间间隔，默认5分钟
	 */
	function __construct($starttime = '',$endtime = '',$duration = 300)
	{
		$this->_starttime = $starttime;
		$this->_endtime = $endtime;
		$this->_duration = $duration;
	}
	
	/**
	 * 设置时间间隔
	 * @param unknown $duration
	 */
	public function setDuration($duration)
	{
		$this->_duration = $duration;
	}
	
	/**
	 * 设置开始时间和结束时间
	 * @param unknown $starttime
	 * @param unknown $endtime
	 */
	public function setTime($starttime,$endtime)
	{
		$this->_starttime = $starttime;
		$this->_endtime = $endtime;
	}
	
	/**
	 * 计算CDS分时段的在线数量
	 */
	public function CDSOnlineNum($sn = array())
	{
		$sn = $this->combineSns($sn);
		
		$cds_max = 0;
		$cds_detail = array();
		
		switch ($this->_duration)
		{
			case 30*60:
				$time = 'if( date_format(ctime,"%i")<30,date_format(ctime,"%Y-%m-%d %H:00:00"),date_format(ctime,"%Y-%m-%d %H:30:00") )';
			break;
			case 60*60:
				$time = 'date_format(ctime,"%Y-%m-%d %H:00:00")';
			break;
			case 2*60*60:
				$time = 'concat(date_format(ctime,"%Y-%m-%d")," ",floor(date_format(ctime,"%H")/2)*2,":00:00")';
			break;
			case 24*60*60:
				$time = 'date_format(ctime,"%Y-%m-%d 00:00:00")';
			break;
			default:
				$time = '';
		}
		
		
		if (!empty($time))
		{
			$result = $this->model('feedbackHistory')
			->where('ctime >= ? and ctime < ?',array(
				$this->_starttime,$this->_endtime
			))
			->group('time')
			->order('time','asc')
			->in('sn',$sn)
			->select(array(
				'time' => $time,
				'count' => 'count(distinct(sn))'
			));
			foreach ($result as $r)
			{
				$cds_detail[$r['time']] = $r['count'];
			}
			$cds_max = empty($cds_detail)?0:max($cds_detail);
		}
		else
		{
			for($t_time = $this->_starttime;strtotime($t_time)<strtotime($this->_endtime);$t_time = date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration))
			{
				if (!empty($sn))
				{
					if (is_array($sn))
					{
						$this->model('feedbackHistory')->In('sn',$sn);
					}
					else if (is_scalar($sn))
					{
						$this->model('feedbackHistory')->where('sn=?',array($sn));
					}
				}
				$cds_detail[$t_time] = 1 * $this->model('feedbackHistory')
				->where('ctime >= ? and ctime < ?',array(
					$t_time,
					date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration)
				))
				->scalar('count(distinct(sn))');
				if ($cds_detail[$t_time] > $cds_max)
				{
					$cds_max = $cds_detail[$t_time];
				}
			}
		}
		
		return array(
			'max' => $cds_max,
			'detail' => $cds_detail,
		);
	}
	
	/**
	 * 在线用户数量
	 */
	public function USEROnlineNum($sn = array())
	{
		$sn = $this->combineSns($sn);
		
		$user_detail = array();
		
		switch ($this->_duration)
		{
			case 30*60:
				$time = 'if( date_format(update_time,"%i")<30,date_format(update_time,"%Y-%m-%d %H:00:00"),date_format(update_time,"%Y-%m-%d %H:30:00") )';
			break;
			case 60*60:
				$time = 'date_format(update_time,"%Y-%m-%d %H:00:00")';
			break;
			case 2*60*60:
				$time = 'concat(date_format(update_time,"%Y-%m-%d")," ",floor(date_format(update_time,"%H")/2)*2,":00:00")';
			break;
			case 24*60*60:
				$time = 'date_format(update_time,"%Y-%m-%d 00:00:00")';
			break;
			default:
				$time = '';
		}
		
		if (!empty($time))
		{
			$sql = new sql();
			$sql->from('_feedback_history');
			$sql->in('sn',$sn);
			$sql = $sql->where('update_time >= ? and update_time < ?',array(
				$this->_starttime,
				$this->_endtime
			))
			->group('time,sn')
			->select(array(
				'time' => $time,
				'online'=>'max(online)'
			));
			
			$result = $this->model('feedbackHistory')->setFrom($sql,'a')->group('time')->select('time,sum(online) as online');
			
			//重置
			$this->model('feedbackHistory')->setFrom('_feedback_history');
			
			foreach ($result as $r)
			{
				$user_detail[$r['time']] = $r['online']*1;
			}
		}
		else
		{
			for($t_time = $this->_starttime;strtotime($t_time)<strtotime($this->_endtime);$t_time = date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration))
			{
				if (!empty($sn))
				{
					if (is_array($sn))
					{
						$this->model('feedbackHistory')->In('sn',$sn);
					}
					else if (is_scalar($sn))
					{
						$this->model('feedbackHistory')->where('sn=?',array($sn));
					}
				}
				$max_online_gourp_sn = $this->model('feedbackHistory')
				->group('sn')
				->where('update_time >= ? and update_time<?',array(
					$t_time,
					date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration),
				))
				->select('max(online) as online,sn,ctime');
			
				$user_detail[$t_time] = 0;
				foreach ($max_online_gourp_sn as $online)
				{
					$user_detail[$t_time] += $online['online'];
				}
			}
		}
		
		return array(
			'max' => empty($user_detail)?0:max($user_detail),
			'detail' => $user_detail,
		);
	}
	
	/**
	 * 服务流速
	 * @return number[]|number[][]
	 */
	public function ServiceMax($sn = array())
	{
		$traffic_stat = $this->traffic_stat($sn);
		$service_max_detail = $traffic_stat['service'];
		$service_max_max = empty($service_max_detail)?0:max($service_max_detail);
		
		return array(
			'max' => $service_max_max,
			'detail' => $service_max_detail,
		);
	}
	
	/**
	 * 计算累计流量，不分时间段
	 * @param array $sn
	 * @return number[]
	 */
	public function ServiceSum($sn = array())
	{
		$sn = $this->combineSns($sn);
		
		if (!empty($sn))
		{
			if (is_array($sn))
			{
				$sql = '';
				$param = array();
				reset($sn);
				$s = current($sn);
				while ($s)
				{
					$sql .= 'sn like ? or ';
					$param[] = '%'.substr($s,3);
					$s = next($sn);
				}
				$sql = substr($sql, 0,-4);
				$this->model('operation_stat')->where($sql,$param);
			}
			else if(is_scalar($sn))
			{
				$this->model('operation_stat')->where('sn like ?',array('%'.substr($sn, 3)));
			}
		}
		$service_sum_sum = 1*$this->model('operation_stat')
		->where('make_time >= ? and make_time < ?',array(
			$this->_starttime,
			$this->_endtime
		))
		->sum('service_size');
		return array(
			'max' => $service_sum_sum,
			'detail' => array(),
		);
	}
	
	/**
	 * 获取分类名称
	 * @param array $r 一个包含class和category的数组
	 * @return string
	 */
	private function getCategoryName($r)
	{
		$category = $this->getConfig('category');
		
		switch ($r['class'])
		{
			case 0:$classname = isset($category['http'][$r['category']])?$category['http'][$r['category']]:'其他';break;
			case 1:$classname = isset($category['mobile'][$r['category']])?$category['mobile'][$r['category']]:'其他';break;
			case 2:
				if ($r['category']>=128)
				{
					$classname = isset($category['videoLive'][$r['category']-128])?$category['videoLive'][$r['category']-128]:'其他';break;
				}
				else
				{
					$classname = isset($category['videoDemand'][$r['category']])?$category['videoDemand'][$r['category']]:'其他';break;
				}
		}
		return $classname;
	}
	
	/**
	 * 分CP服务流速
	 * @param number $top
	 * @return NULL[]|unknown[]
	 */
	public function CPService($sn = array(),$top = 9)
	{
		$sn = $this->combineSns($sn);
		
		$cp_service = array();
		if (!empty($sn))
		{
			if (is_array($sn))
			{
				$sql = '';
				$param = array();
				reset($sn);
				$s = current($sn);
				while ($s)
				{
					$sql .= 'sn like ? or ';
					$param[] = '%'.substr($s,3);
					$s = next($sn);
				}
				$sql = substr($sql, 0,-4);
				$this->model('operation_stat')->where($sql,$param);
			}
			else if(is_scalar($sn))
			{
				$this->model('operation_stat')->where('sn like ?',array('%'.substr($sn, 3)));
			}
		}
		//取出service累计最大的前n个分类
		$categoryTop = $this->model('operation_stat')->where('make_time>=? and make_time<?',array(
			$this->_starttime,
			$this->_endtime
		))
		->group(array('class','category'))
		->order('service_sum','desc')
		->limit($top)
		->forceIndex('primary')//强制索引
		->select(array(
			'category',
			'class',
			'sum(service_size) as service_sum',
		));
		
		
		$top = array();
		foreach ($categoryTop as $r)
		{
			$top[] = array(
				'category' => $r['category'],
				'class' => $r['class'],
			);
		}
	
		$total_operation_stat = array();
		for($t_time = $this->_starttime;strtotime($t_time)<strtotime($this->_endtime);$t_time = date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration))
		{
			if (!empty($sn))
			{
				if (is_array($sn))
				{
					$sql = '';
					$param = array();
					reset($sn);
					$s = current($sn);
					while ($s)
					{
						$sql .= 'sn like ? or ';
						$param[] = '%'.substr($s,3);
						$s = next($sn);
					}
					$sql = substr($sql, 0,-4);
					$this->model('operation_stat')->where($sql,$param);
				}
				else if(is_scalar($sn))
				{
					$this->model('operation_stat')->where('sn like ?',array('%'.substr($sn, 3)));
				}
			}
			$result = $this->model('operation_stat')
			->where('make_time>=? and make_time<?',array(
				$t_time,
				date('Y-m-d H:i:s',strtotime($t_time) + $this->_duration)
			))
			->group('class,category')
			->select(array(
				'class',
				'category',
				'service_size' => 'sum(service_size)',
			));
			
			foreach ($top as $r)
			{
				$classname = $this->getCategoryName($r);
				$cp_service[$classname][$t_time] = 0;
			}
			$cp_service['其他'][$t_time] = 0;
			
			$total_operation_stat[$t_time] = 0;
			foreach ($result as $r)
			{
				if (in_array(array(
					'category'=>$r['category'],
					'class' => $r['class']
				), $top,true))
				{
					$classname = $this->getCategoryName($r);
				}
				else
				{
					$classname = '其他';
				}
				
				$total_operation_stat[$t_time] += $r['service_size'];
				$cp_service[$classname][$t_time] += $r['service_size'];
			}
		}
		
		reset($sn);
		$service = $this->ServiceMax($sn);
		$service = $service['detail'];
		
		
		foreach ($cp_service as $classname => &$v)
		{
			
			foreach ($v as $time => &$value)
			{
				$value = (isset($service[$time])?$service[$time]:0) * division($value,$total_operation_stat[$time]);
			}
		}
		
		$max = array();
		foreach ($cp_service as $classname => $v_t)
		{
			$max[$classname] = max($v_t);
		}
		
		return array(
			'max' => $max,
			'detail' => $cp_service
		);
	}
	
	
	
	/**
	 * 网卡流速
	 * 计算最大Service和对应的cache
	 * @return number[][]|number[]|boolean[]
	 */
	public function traffic_stat($sn = array())
	{
		$key = md5($this->_starttime.$this->_endtime.$this->_duration.(is_array($sn)?implode(',', $sn):$sn));
		
		static $cacheContainer = array();
		if (isset($cacheContainer[$key]) && !empty($cacheContainer[$key]))
		{
			return $cacheContainer[$key];
		}
		
		$sn = $this->combineSns($sn);
		
		switch ($this->_duration)
		{
			case 5*60:
				$time = 'concat(date_format(time,"%Y-%m-%d %H"),":",LPAD(floor(date_format(time,"%i")/5)*5,2,0),":00")';
				break;
			case 30*60:
				$time = 'if( date_format(time,"%i")<30,date_format(time,"%Y-%m-%d %H:00:00"),date_format(time,"%Y-%m-%d %H:30:00") )';
				break;
			case 60*60:
				$time = 'date_format(time,"%Y-%m-%d %H:00:00")';
				break;
			case 24*60*60:
				$time = 'date_format(time,"%Y-%m-%d 00:00:00")';
				break;
			default:
				$time = '';
		}
		
		$cache_max_detail = array();
		$service_max_detail = array();
		$monitor_max_detail = array();
		
		if (!empty($time))
		{
			$traffic_stat = new sql();
			$cdn_traffic_stat = new sql();
			$xvirt_traffic_stat = new sql();
				
			$xvirt_traffic_stat->from('cds_v2.xvirt_traffic_stat')
			->in('sn',$sn)
			->where('make_time>=? and make_time<?',array(
				$this->_starttime,$this->_endtime
			))
			->select(array(
				'time' => 'date_format(make_time,"%Y-%m-%d %H:%i")',
				'service' => '-1*service',
				'cache' => 0,
				'monitor' => 0,
			));
				
			$traffic_stat->from('ordoac.traffic_stat')
			->in('sn',$sn)
			->where('create_time>=? and create_time<?',array(
				$this->_starttime,$this->_endtime
			))
			->select(array(
				'time'=>'date_format(create_time,"%Y-%m-%d %H:%i")',
				'service'=>'1024*service',
				'cache' => '1024*cache',
				'monitor'=>'1024*monitor',
			));
				
			$sn = array_map(function($s){
				return '%'.substr($s, 3);
			}, $sn);
			$cdn_traffic_stat->from('cds_v2.cdn_traffic_stat')
			->likein('sn',$sn)
			->where('make_time>=? and make_time<?',array(
				$this->_starttime,$this->_endtime
			))
			->select(array(
				'time' => 'date_format(make_time,"%Y-%m-%d %H:%i")',
				'service',
				'cache',
				'monitor',
			));
		
			$xvirt_traffic_stat->union(true, $cdn_traffic_stat, $traffic_stat);
		
			$t = new sql();
			$t->setFrom($xvirt_traffic_stat,'t');
			$t->group('time');
			$t->select(array(
				'time',
				'service' => 'sum(service)',
				'cache' => 'sum(cache)',
				'monitor' => 'sum(monitor)'
			));
		
			$result = $this->model('traffic_stat')
			->setFrom($t,'a')
			->group('timenode')
			->select(array(
				'timenode'=>$time,
				'line' => 'max(concat(lpad(service,20,0),"-",lpad(cache,20,0),"-",lpad(monitor,20,0)))',
			));
			
			//重置from
			$this->model('traffic_stat')->setFrom('traffic_stat');
			
			foreach ($result as $r)
			{
				list($service,$cache,$monitor) = explode('-', $r['line']);
				$service_max_detail[$r['timenode']] = $service*1;
				$cache_max_detail[$r['timenode']] = $cache*1;
				$monitor_max_detail[$r['timenode']] = $monitor*1;
			}
		}
		else
		{
			for($t_time = $this->_starttime;strtotime($t_time)<strtotime($this->_endtime);$t_time = date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration))
			{
				$temp_service = array();
				$temp_cache = array();
				$temp_monitor = array();
				
				$traffic_stat_model = $this->model('traffic_stat');
				if (!empty($sn))
				{
					if (is_array($sn))
					{
						$traffic_stat_model->In('sn',$sn);
					}
					else if (is_scalar($sn))
					{
						$traffic_stat_model->where('sn=?',array($sn));
					}
				}
				$traffic_stat = $traffic_stat_model->where('create_time>=? and create_time<?',array(
					$t_time,
					date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration)
				))
				->order('time','asc')
				->group('time')
				->select(array(
					'time'=>'DATE_FORMAT(create_time,"%Y-%m-%d %H:%i:00")',
					'sum_service'=>'sum(service) * 1024',
					'sum_cache' => 'sum(cache) * 1024',
					'sum_monitor' => 'sum(monitor) * 1024',
				));
				
				foreach ($traffic_stat as $r)
				{
					$temp_service[$r['time']] = $r['sum_service'];
					$temp_cache[$r['time']] = $r['sum_cache'];
					$temp_monitor[$r['time']] = $r['sum_monitor'];
				}
				
				
				$cdn_traffic_stat_model = $this->model('cdn_traffic_stat');
				if (!empty($sn))
				{
					if (is_scalar($sn))
					{
						$cdn_traffic_stat_model->where('sn like ?',array('%'.substr($sn, 3)));
					}
					else if (is_array($sn))
					{
						$where = '';
						$param = array();
						foreach ($sn as $s)
						{
							$where .= 'sn like ? or ';
							$param[] = '%'.substr($s, 3);
						}
						$where = substr($where,0, -4);
						$cdn_traffic_stat_model->where($where,$param);
					}
				}
				$cdn_traffic_stat = $cdn_traffic_stat_model
				->where('make_time>=? and make_time<?',array(
					$t_time,
					date('Y-m-d H:i:s',strtotime($t_time) + $this->_duration)
				))
				->order('time','asc')
				->group('time')
				->select(array(
					'time'=>'DATE_FORMAT(make_time,"%Y-%m-%d %H:%i:00")',
					'sum_service' => 'sum(service)',
					'sum_cache' => 'sum(cache)',
					'sum_monitor' => 'sum(monitor)',
				));
				
				foreach ($cdn_traffic_stat as $r)
				{
					if (isset($temp_service[$r['time']]))
					{
						$temp_service[$r['time']] += $r['sum_service'];
					}
					else
					{
						$temp_service[$r['time']] = $r['sum_service']*1;
					}
					
					if (isset($temp_cache[$r['time']]))
					{
						$temp_cache[$r['time']] += $r['sum_cache'];
					}
					else
					{
						$temp_cache[$r['time']] = $r['sum_cache']*1;
					}
					
					if (isset($temp_monitor[$r['time']]))
					{
						$temp_monitor[$r['time']] += $r['sum_monitor'];
					}
					else
					{
						$temp_monitor[$r['time']] = $r['sum_monitor']*1;
					}
				}
				
				$xvirt_traffic_stat_model = $this->model('xvirt_traffic_stat');
				if (!empty($sn))
				{
					if(is_scalar($sn))
					{
						$xvirt_traffic_stat_model->where('sn like ?',array('%'.substr($sn, 3)));
					}
					else
					{
						$where = '';
						$param = array();
						foreach ($sn as $s)
						{
							$where .= 'sn like ? or ';
							$param[] = '%'.substr($s, 3);
						}
						$where = substr($where,0, -4);
						$xvirt_traffic_stat_model->where($where,$param);
					}
				}
				//traffic_stat + cdn_traffic_stat - xvirt_traffic_stat
				$xvirt = $xvirt_traffic_stat_model->where('make_time>=? and make_time<?',array(
					$t_time,
					date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration)
				))
				->order('time','asc')
				->group('time')
				->select(array(
					'time'=>'DATE_FORMAT(make_time,"%Y-%m-%d %H:%i:00")',
					'sum_service'=>'sum(service)',
					'sum_cache'=>'sum(cache)',
				));
				foreach ($xvirt as $r)
				{
					if (isset($temp_service[$r['time']]))
					{
						$temp_service[$r['time']] -= $r['sum_service'];
					}
				}
				
				
				
				$max = 0;
				$max_time = '';
				foreach ($temp_service as $time=>$service)
				{
					if ($service>=$max)
					{
						$max = $service * 1;
						$max_time = $time;
					}
				}
				
				$service_max_detail[$t_time] = $max;
				if (!empty($max_time))
				{
					$cache_max_detail[$t_time] = isset($temp_cache[$max_time])?$temp_cache[$max_time]:0;
					$monitor_max_detail[$t_time] = isset($temp_monitor[$max_time])?$temp_monitor[$max_time]:0;
				}
				else
				{
					$cache_max_detail[$t_time] = 0;
					$monitor_max_detail[$t_time] = 0;
				}
			}
		}
		$cacheContainer[$key] = array(
			'service' => $service_max_detail,
			'cache' => $cache_max_detail,
			'monitor' => $monitor_max_detail
		);
		return $cacheContainer[$key];
	}
	
	/**
	 * 计算独立的服务流速和缓存流速
	 * service和cache互不依赖
	 */
	function traffic_stat_alone($sn = NULL)
	{
		$sn = $this->combineSns($sn);
		
		$service = array();
		$cache = array();
		
		switch ($this->_duration)
		{
			case 5*60:
				$time = 'concat(date_format(time,"%Y-%m-%d %H"),":",LPAD(floor(date_format(time,"%i")/5)*5,2,0),":00")';
				break;
			case 30*60:
				$time = 'if( date_format(time,"%i")<30,date_format(time,"%Y-%m-%d %H:00:00"),date_format(time,"%Y-%m-%d %H:30:00") )';
				break;
			case 60*60:
				$time = 'date_format(time,"%Y-%m-%d %H:00:00")';
				break;
			case 24*60*60:
				$time = 'date_format(time,"%Y-%m-%d 00:00:00")';
				break;
			default:
				$time = '';
		}
		
		if (!empty($time))
		{
			$traffic_stat = new sql();
			$cdn_traffic_stat = new sql();
			$xvirt_traffic_stat = new sql();
			
			$xvirt_traffic_stat->from('cds_v2.xvirt_traffic_stat')
			->in('sn',$sn)
			->where('make_time>=? and make_time<?',array(
				$this->_starttime,$this->_endtime
			))
			->select(array(
				'time' => 'date_format(make_time,"%Y-%m-%d %H:%i")',
				'service' => '-1*service',
				'cache' => 0,
			));
			
			$traffic_stat->from('ordoac.traffic_stat')
			->in('sn',$sn)
			->where('create_time>=? and create_time<?',array(
				$this->_starttime,$this->_endtime
			))
			->select(array(
				'time'=>'date_format(create_time,"%Y-%m-%d %H:%i")',
				'service'=>'1024*service',
				'cache' => '1024*cache',
			));
			
			$sn = array_map(function($s){
				return '%'.substr($s, 3);
			}, $sn);
			$cdn_traffic_stat->from('cds_v2.cdn_traffic_stat')
			->likein('sn',$sn)
			->where('make_time>=? and make_time<?',array(
				$this->_starttime,$this->_endtime
			))
			->select(array(
				'time' => 'date_format(make_time,"%Y-%m-%d %H:%i")',
				'service',
				'cache',
			));
		
			$xvirt_traffic_stat->union(true, $cdn_traffic_stat, $traffic_stat);
		
			$t = new sql();
			$t->setFrom($xvirt_traffic_stat,'t');
			$t->group('time');
			$t->select(array(
				'time',
				'service' => 'sum(service)',
				'cache' => 'sum(cache)',
			));
		
			$result = $this->model('traffic_stat')
			->setFrom($t,'a')
			->group('timenode')
			->select(array(
				'timenode'=>$time,
				'service'=>'max(service)',
				'cache'=>'max(cache)',
			));
			
			//重置from
			$this->model('traffic_stat')->setFrom('traffic_stat');
			
			foreach ($result as $r)
			{
				$service[$r['timenode']] = $r['service']*1;
				$cache[$r['timenode']] = $r['cache']*1;
			}
		}
		else
		{
			for($t_time = $this->_starttime;strtotime($t_time)<strtotime($this->_endtime);$t_time = date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration))
			{
				$temp_service = array();
				$temp_cache = array();
				
				if (!empty($sn))
				{
					if (is_array($sn))
					{
						$this->model('traffic_stat')->In('sn',$sn);
					}
					else if (is_scalar($sn))
					{
						$this->model('traffic_stat')->where('sn=?',array($sn));
					}
				}
				$traffic_stat = $this->model('traffic_stat')
				->where('create_time>=? and create_time<?',array(
					$t_time,
					date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration)
				))
				->order('time','asc')
				->group('time')
				->select(array(
					'time'=>'DATE_FORMAT(create_time,"%Y-%m-%d %H:%i:00")',
					'sum_service'=>'sum(service) * 1024',
					'sum_cache' => 'sum(cache) * 1024',
				));
				
				foreach ($traffic_stat as $stat)
				{
					if (isset($temp_service[$stat['time']]))
					{
						$temp_service[$stat['time']] += $stat['sum_service'];
					}
					else
					{
						$temp_service[$stat['time']] = $stat['sum_service'];
					}
					
					if (isset($temp_cache[$stat['time']]))
					{
						$temp_cache[$stat['time']] += $stat['sum_cache'];
					}
					else
					{
						$temp_cache[$stat['time']] = $stat['sum_cache']*1;
					}
				}
					
				if (!empty($sn))
				{
					if (is_scalar($sn))
					{
						$this->model('cdn_traffic_stat')->where('sn like ?',array('%'.substr($sn, 3)));
					}
					else if (is_array($sn))
					{
						$where = '';
						$param = array();
						foreach ($sn as $s)
						{
							$where .= 'sn like ? or ';
							$param[] = '%'.substr($s, 3);
						}
						$where = substr($where,0, -4);
						$this->model('cdn_traffic_stat')->where($where,$param);
					}
				}
				$cdn_traffic_stat = $this->model('cdn_traffic_stat')
				->where('make_time>=? and make_time<?',array(
					$t_time,
					date('Y-m-d H:i:s',strtotime($t_time) + $this->_duration)
				))
				->order('time','asc')
				->group('time')
				->select(array(
					'time'=>'DATE_FORMAT(make_time,"%Y-%m-%d %H:%i:00")',
					'sum_service' => 'sum(service)',
					'sum_cache' => 'sum(cache)',
				));
				foreach ($cdn_traffic_stat as $stat)
				{
					if (isset($temp_service[$stat['time']]))
					{
						$temp_service[$stat['time']] += $stat['sum_service'];
					}
					else
					{
						$temp_service[$stat['time']] = $stat['sum_service'];
					}
					if (isset($temp_cache[$stat['time']]))
					{
						$temp_cache[$stat['time']] += $stat['sum_cache'];
					}
					else
					{
						$temp_cache[$stat['time']] = $stat['sum_cache'];
					}
				}
				
				if (!empty($sn))
				{
					if(is_scalar($sn))
					{
						$this->model('xvirt_traffic_stat')->where('sn like ?',array('%'.substr($sn, 3)));
					}
					else
					{
						$where = '';
						$param = array();
						foreach ($sn as $s)
						{
							$where .= 'sn like ? or ';
							$param[] = '%'.substr($s, 3);
						}
						$where = substr($where,0, -4);
						$this->model('xvirt_traffic_stat')->where($where,$param);
					}
				}
				$xvirt = $this->model('xvirt_traffic_stat')
				->where('make_time>=? and make_time<?',array(
					$t_time,
					date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration)
				))
				->order('time','asc')
				->group('time')
				->select(array(
					'time'=>'DATE_FORMAT(make_time,"%Y-%m-%d %H:%i:00")',
					'sum_service'=>'sum(service)',
					'sum_cache'=>'sum(cache)',
				));
				foreach ($xvirt as $stat)
				{
					if (isset($temp_service[$stat['time']]))
					{
						$temp_service[$stat['time']] -= $stat['sum_service'];
					}
					/* if (isset($temp_cache[$stat['time']]))
					{
						$temp_cache[$stat['time']] -= $stat['sum_cache'];
					} */
				}
	
				$service[$t_time] = empty($temp_service)?0:max($temp_service);
				$cache[$t_time] = empty($temp_cache)?0:max($temp_cache);
			}
		}
		return array('service' => $service,'cache' => $cache);
	}
	
	/**
	 * 计算服务，缓存回源，代理缓存回源流速
	 */
	function traffic_stat_service_cache_proxy($sn = array())
	{
		$key = md5($this->_starttime.$this->_endtime.$this->_duration.(is_array($sn)?implode(',', $sn):$sn));
		
		static $cacheContainer = array();
		if (isset($cacheContainer[$key]) && !empty($cacheContainer[$key]))
		{
			return $cacheContainer[$key];
		}
		
		$sn = $this->combineSns($sn);
		
		$cache_max_detail = array();
		$service_max_detail = array();
		$proxy_max_detail = array();
		for($t_time = $this->_starttime;strtotime($t_time)<strtotime($this->_endtime);$t_time = date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration))
		{
			$temp_service = array();
			$temp_cache = array();
			$temp_proxy = array();
			
			$traffic_stat_model = $this->model('traffic_stat');
			if (!empty($sn))
			{
				if (is_array($sn))
				{
					$traffic_stat_model->In('sn',$sn);
				}
				else if (is_scalar($sn))
				{
					$traffic_stat_model->where('sn=?',array($sn));
				}
			}
			$traffic_stat = $traffic_stat_model->where('create_time>=? and create_time<?',array(
				$t_time,
				date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration)
			))
			->order('time','asc')
			->group('time')
			->select(array(
				'time'=>'DATE_FORMAT(create_time,"%Y-%m-%d %H:%i:00")',
				'sum_service'=>'sum(service) * 1024',
				'sum_cache' => 'sum(cache) * 1024',
			));
			
			foreach ($traffic_stat as $r)
			{
				$temp_service[$r['time']] = $r['sum_service'];
				$temp_cache[$r['time']] = $r['sum_cache'];
			}
			
			$cdn_traffic_stat_model = $this->model('cdn_traffic_stat');
			if (!empty($sn))
			{
				if (is_scalar($sn))
				{
					$cdn_traffic_stat_model->where('sn like ?',array('%'.substr($sn, 3)));
				}
				else if (is_array($sn))
				{
					$where = '';
					$param = array();
					foreach ($sn as $s)
					{
						$where .= 'sn like ? or ';
						$param[] = '%'.substr($s, 3);
					}
					$where = substr($where,0, -4);
					$cdn_traffic_stat_model->where($where,$param);
				}
			}
			$cdn_traffic_stat = $cdn_traffic_stat_model
			->where('make_time>=? and make_time<?',array(
				$t_time,
				date('Y-m-d H:i:s',strtotime($t_time) + $this->_duration)
			))
			->order('time','asc')
			->group('time')
			->select(array(
				'time'=>'DATE_FORMAT(make_time,"%Y-%m-%d %H:%i:00")',
				'sum_service' => 'sum(service)',
				'sum_cache' => 'sum(cache)',
			));
			
			foreach ($cdn_traffic_stat as $r)
			{
				if (isset($temp_service[$r['time']]))
				{
					$temp_service[$r['time']] += $r['sum_service'];
				}
				else
				{
					$temp_service[$r['time']] = $r['sum_service']*1;
				}
				
				$temp_proxy[$r['time']] = $r['sum_cache']*1;
			}
			
			$xvirt_traffic_stat_model = $this->model('xvirt_traffic_stat');
			if (!empty($sn))
			{
				if(is_scalar($sn))
				{
					$xvirt_traffic_stat_model->where('sn like ?',array('%'.substr($sn, 3)));
				}
				else
				{
					$where = '';
					$param = array();
					foreach ($sn as $s)
					{
						$where .= 'sn like ? or ';
						$param[] = '%'.substr($s, 3);
					}
					$where = substr($where,0, -4);
					$xvirt_traffic_stat_model->where($where,$param);
				}
			}
			//traffic_stat + cdn_traffic_stat - xvirt_traffic_stat
			$xvirt = $xvirt_traffic_stat_model->where('make_time>=? and make_time<?',array(
				$t_time,
				date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration)
			))
			->order('time','asc')
			->group('time')
			->select(array(
				'time'=>'DATE_FORMAT(make_time,"%Y-%m-%d %H:%i:00")',
				'sum_service'=>'sum(service)',
			));
			foreach ($xvirt as $r)
			{
				if (isset($temp_service[$r['time']]))
				{
					$temp_service[$r['time']] -= $r['sum_service'];
				}
			}
			
			$max = 0;
			$max_time = '';
			foreach ($temp_service as $time=>$service)
			{
				if ($service>=$max)
				{
					$max = $service * 1;
					$max_time = $time;
				}
			}
			$service_max_detail[$t_time] = $max;
			if (!empty($max_time))
			{
				$cache_max_detail[$t_time] = isset($temp_cache[$max_time])?$temp_cache[$max_time]:0;
				$proxy_max_detail[$t_time] = isset($temp_proxy[$max_time])?$temp_proxy[$max_time]:0;
			}
			else
			{
				$cache_max_detail[$t_time] = 0;
				$proxy_max_detail[$t_time] = 0;
			}
		}
		$cacheContainer[$key] = array(
			'service' => $service_max_detail,
			'cache' => $cache_max_detail,
			'proxy' => $proxy_max_detail,
		);
		return $cacheContainer[$key];
	}
	
	/**
	 * 分时间段，累计流量
	 * @return number
	 */
	function operation_stat($sn = array())
	{
		$key = md5($this->_starttime.$this->_endtime.$this->_duration.(is_array($sn)?implode(',', $sn):$sn));
		
		static $cacheContainer = array();
		if (isset($cacheContainer[$key]) && !empty($cacheContainer[$key]))
		{
			return $cacheContainer[$key];
		}
		
		$sn = $this->combineSns($sn);
		
		
		switch ($this->_duration)
		{
			case 5*60:
				$time = 'concat(date_format(make_time,"%Y-%m-%d %H:"),lpad(floor(date_format(make_time,"%i")/5)*5,2,0),":00")';
				break;
			case 30*60:
				$time = 'if( date_format(make_time,"%i")<30,date_format(make_time,"%Y-%m-%d %H:00:00"),date_format(make_time,"%Y-%m-%d %H:30:00") )';
				break;
			case 60*60:
				$time = 'date_format(make_time,"%Y-%m-%d %H:00:00")';
				break;
			case 2*60*60:
				$time = 'concat(date_format(make_time,"%Y-%m-%d")," ",floor(date_format(make_time,"%H")/2)*2,":00:00")';
				break;
			case 24*60*60:
				$time = 'date_format(make_time,"%Y-%m-%d 00:00:00")';
				break;
			default:
				$time = '';
		}
		$operation_stat = array();
		if (!empty($time))
		{
			$sn = array_map(function($s){
				return '%'.substr($s, 3);
			}, $sn);
			$result = $this->model('operation_stat')
			->where('make_time>=? and make_time<?',array(
				$this->_starttime,$this->_endtime
			))
			->group('time')
			->order('time','asc')
			->likein('sn',$sn)
			->select(array(
				'time' => $time,
				'service' => 'sum(service_size)',
				'cache' => 'sum(cache_size+proxy_cache_size)'
			));
			foreach ($result as $r)
			{
				$operation_stat['service'][$r['time']] = $r['service']*1;
				$operation_stat['cache'][$r['time']] = $r['cache']*1;
			}
		}
		else
		{
			
			for($t_time = $this->_starttime;strtotime($t_time)<strtotime($this->_endtime);$t_time = date('Y-m-d H:i:s',strtotime($t_time)+$this->_duration))
			{
				if (!empty($sn))
				{
					if (is_array($sn))
					{
						$sql = '';
						$param = array();
						reset($sn);
						$s = current($sn);
						while ($s)
						{
							$sql .= 'sn like ? or ';
							$param[] = '%'.substr($s,3);
							$s = next($sn);
						}
						$sql = substr($sql, 0,-4);
						$this->model('operation_stat')->where($sql,$param);
					}
					else if(is_scalar($sn))
					{
						$this->model('operation_stat')->where('sn like ?',array('%'.substr($sn, 3)));
					}
				}
				$result = $this->model('operation_stat')
				->where('make_time>=? and make_time<?',array(
					$t_time,
					date('Y-m-d H:i:s',strtotime($t_time) + $this->_duration),
				))
				->find(array(
					'sum_service'=>'sum(service_size)',
					'sum_cache'=>'sum(cache_size+proxy_cache_size)'
				));
				$operation_stat['service'][$t_time] = $result['sum_service']*1;
				$operation_stat['cache'][$t_time] = $result['sum_cache']*1;
			}
		}
		$cacheContainer[$key] = $operation_stat;
		return $cacheContainer[$key];
	}
}