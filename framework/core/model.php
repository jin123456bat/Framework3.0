<?php
namespace framework\core;

use framework\core\database\sql;
use framework\core\database\driver\mysql;

class model extends component
{
	private $_table;
	
	private $_sql;
	
	private $_db;
	
	private static $_history = array();
	
	private $_desc;
	
	private $_compress = false;
	
	private $_compress_sql = array();
	
	function __construct($table = NULL)
	{
		$this->_table = $table;
	}
	
	public static function debug_trace_sql()
	{
		return self::$_history;
	}
	
	/**
	 * when this class is initlized,this function will be execute
	 * {@inheritDoc}
	 * @see \core\component::initlize()
	 */
	function initlize()
	{
		$this->_sql = new sql();
		
		if (method_exists($this, '__config'))
		{
			$db = $this->__config();
		}
		else
		{
			$db = $this->getConfig('db');
			
			if (!isset($db['db_type']))
			{
				foreach ($db as $d)
				{
					if (isset($d['default']) && $d['default'])
					{
						$db = $d;
						break;
					}
				}
			}
		}
		
		$this->_db = mysql::getInstance($db);
		
		if (method_exists($this, '__tableName'))
		{
			$this->_table = $this->__tableName();
		}
		
		$this->setTable($this->_table);
		parent::initlize();
	}
	
	/**
	 * only for sql
	 * @param unknown $name
	 * @param unknown $args
	 * @return \framework\core\model
	 */
	function __call($name,$args)
	{
		call_user_func_array(array($this->_sql,$name),$args);
		return $this;
	}
	
	/**
	 * set database table's name
	 * @param unknown $table
	 */
	function setTable($table)
	{
		$this->_table = $table;
		$this->parse();
	}
	
	/**
	 * get this database table's name
	 * @return unknown|string
	 */
	function getTable()
	{
		return $this->_table;
	}
	
	/**
	 * process something about this tables;
	 */
	private function parse()
	{
		$this->_desc = $this->query('DESC `'.$this->_table.'`');
	}
	
	/**
	 * find all rows from result
	 */
	function select($fields = '*')
	{
		$this->_sql->from($this->_table);
		$sql = $this->_sql->select($fields);
		$result = $this->query($sql);
		return $result;
	}
	
	/**
	 * find a row from result
	 */
	function find($fields = '*')
	{
		$result = $this->limit(1)->select($fields);
		return isset($result[0])?$result[0]:NULL;
	}
	
	/**
	 * find the first field's value from frist row
	 */
	function scalar($field = '*')
	{
		$result = $this->find($field);
		if (is_array($result))
		{
			return array_shift($result);
		}
		return NULL;
	}
	
	/**
	 * 获取数量
	 * @param unknown $field
	 * @return NULL|mixed
	 */
	function count($field)
	{
		return $this->scalar('count('.$field.')');
	}
	
	function max($field)
	{
		return $this->scalar('max('.$field.')');
	}
	
	function sum($field)
	{
		return $this->scalar('sum('.$field.')');
	}
	
	function avg($field)
	{
		return $this->scalar('avg('.$field.')');
	}
	
	/**
	 * 更新数据表
	 * @param unknown $name
	 * @param string $value
	 * @return boolean
	 */
	function update($name,$value = '')
	{
		$this->_sql->from($this->_table);
		$sql = $this->_sql->update($name,$value);
		return $this->query($sql);
	}
	
	function insert($data = array())
	{
		//字段名称检查
		$fields = array();
		foreach ($this->_desc as $index=>$value)
		{
			$fields[] = $value['Field'];
		}
		
		//是否是数字下标
		$source_keys = array_keys($data);
		$des_keys = range(0, count($data)-1,1);
		$diff = array_diff($source_keys,$des_keys);
		$is_num_index = empty($diff);
		
		//补充默认值
		if (!$is_num_index)
		{
			//去除多余的数据
			foreach ($data as $index => $value)
			{
				if (!in_array($index, $fields))
				{
					unset($data[$index]);
				}
			}
			
			//填充默认数据
			foreach ($this->_desc as $index=>$value)
			{
				if (!in_array($value['Field'], array_keys($data)))
				{
					if ($value['Default'] === NULL)
					{
						if ($value['Null'] == 'YES')
						{
							$data[$value['Field']] = NULL;
						}
						else
						{
							if ($value['Key'] == 'PRI' && $value['Extra'] == trim('AUTO_INCREMENT'))
							{
								$data[$value['Field']] = NULL;
							}
							else
							{
								switch ($value['Type'])
								{
									case 'datetime':
										$data[$value['Field']] = date('Y-m-d H:i:s');
										break;
									case 'timestamp':
										$data[$value['Field']] = date('Y-m-d H:i:s');
										break;
									case 'date':
										$data[$value['Field']] = date('Y-m-d');
										break;
									default:
										$zero = '$int\(\d+\)$';
										$empty_string = '$(char)?(text)?$';
										if (preg_match($zero, $value['Type']))
										{
											$data[$value['Field']] = 0;
										}
										else if (preg_match($empty_string, $value['Type']))
										{
											$data[$value['Field']] = '';
										}
								}
							}
						}
					}
					else
					{
						$data[$value['Field']] = $value['Default'];
					}
				}
			}
			
			//调整字段顺序
			$temp = array();
			foreach ($this->_desc as $value)
			{
				$temp[$value['Field']] = $data[$value['Field']];
			}
			$data = $temp;
		}
		
		$this->_sql->from($this->_table);
		if ($this->_compress)
		{
			if (!isset($this->_compress_sql['insert']))
			{
				$keys = array_keys($data);
				$this->_compress_sql['insert'] = 'INSERT INTO '.$this->_table.' (`'.implode('`,`', $keys).'`) values (\''.implode('\',\'', $data).'\')';
			}
			else
			{
				$this->_compress_sql['insert'] .= ',(\''.implode('\',\'', $data).'\')';
			}
			return true;
		}
		$sql = $this->_sql->insert($data);
		return $this->query($sql);
	}
	
	/**
	 * 删除
	 * @return boolean
	 */
	function delete()
	{
		$this->_sql->from($this->_table);
		$sql = $this->_sql->delete();
		return $this->query($sql);
	}
	
	/**
	 * 执行自定义sql
	 * @param unknown $sql
	 * @param array $array
	 * @return boolean
	 */
	function query($sql,$array = array())
	{
		if ($sql instanceof sql)
		{
			$complete_sql = $sql->getSql();
			self::$_history[] = $complete_sql;
			$array = $sql->getParams();
			$sql_string = $sql->__toString();
			$sql->clear();
			$sql = $sql_string;
		}
		else
		{
			$complete_sql = $this->_sql->getSql($sql,$array);
			self::$_history[] = $complete_sql;
		}
		if ($this->_compress)
		{
			$this->_compress_sql[] = $complete_sql;
			return true;
		}
		return $this->_db->query($sql,$array);
	}
	
	/**
	 * 事务开始
	 */
	function transaction()
	{
		return $this->_db->transaction();
	}
	
	/**
	 * 事务提交
	 */
	function commit()
	{
		return $this->_db->commit();
	}
	
	/**
	 * 事务回滚
	 */
	function rollback()
	{
		return $this->_db->rollback();
	}
	
	/**
	 * 上一个插入的ID
	 * @param unknown $name
	 */
	function lastInsertId($name = NULL)
	{
		return $this->_db->lastInsert($name);
	}
	
	/**
	 * 清空表
	 * @return boolean
	 */
	function truncate()
	{
		return $this->_db->exec('TRUNCATE `'.$this->_table.'`');
	}
	
	/**
	 * 开启sql压缩
	 * 所谓的sql压缩是指当需要一次性执行非常多的sql的时候，自动把所有的sql语句都拼接起来，当作一条sql执行
	 * 当开启sql压缩后query函数始终返回true
	 */
	function startCompress()
	{
		$this->_compress = true;
	}
	
	/**
	 * 提交压缩后的sql
	 */
	function commitCompress()
	{
		if ($this->_compress && !empty($this->_compress_sql))
		{
			$sql = implode(';', $this->_compress_sql).';';
			$result = $this->_db->exec($sql);
			$this->_compress = false;
			return $result;
		}
		return false;
	}
}