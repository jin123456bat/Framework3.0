<?php
namespace framework\core\database;

use framework\core\base;

class sql extends base
{
	private $_temp = array();
	
	private $_do = '';
	
	function __construct()
	{

	}
	
	/**
	 * select for update
	 */
	function forUpdate()
	{
		$this->_temp['forUpdate'] = true;
	}
	
	/**
	 * do select
	 * @param unknown $field
	 */
	function select($fields = '*')
	{
		$this->_do = 'select';
		
		if (is_array($fields))
		{
			foreach ($fields as $as => $field)
			{
				if ($field instanceof sql)
				{
					$field = $field->__toString();
				}
				if (is_string($as))
				{
					$this->_temp['fields'][$as] = $field;
				}
				else if (is_int($as))
				{
					$this->_temp['fields'][] = $field;
				}
			}
		}
		else if ($fields instanceof sql)
		{
			$this->_temp['fields'][] = $fields->__toString();
		}
		else if (is_string($fields))
		{
			$this->_temp['fields'][] = $fields;
		}
		return $this;
	}
	
	function update($key,$value = NULL)
	{
		$this->_do = 'update';
		
		if (is_array($key))
		{
			foreach ($key as $index=>$value)
			{
				if (is_int($index))
				{
					$this->_temp['update'][] = $value;
				}
				else
				{
					$this->_temp['update'][$index] = $value;
				}
			}
		}
		else if (is_string($key))
		{
			$this->_temp['update'][$key] = $value;
		}
		return $this;
	}
	
	/**
	 * replace into
	 */
	function replace($key,$value = NULL)
	{
		if (empty($this->_do))
		{
			$this->_do = 'REPLACE';
			
			if ($value instanceof sql)
			{
				$value = $value->__toString();
			}
			
			if (is_string($key))
			{
				$this->_fields[] = $key;
				$this->_params[] = $value;
			}
			else if (is_array($key))
			{
				foreach ($key as $index => $val)
				{
					if ($val instanceof sql)
					{
						$val = $val->__toString();
					}
					$this->_fields[] = $index;
					$this->_params[] = $val;
				}
			}
		}
		return $this;
	}
	
	/**
	 * $this->insert('a',1)->insert('b',2);
	 * $this->insert(['a'=>1,'b'=>2]);
	 * insert into
	 * @param unknown $name
	 * @param unknown $value
	 * @return \framework\core\database\sql
	 */
	function insert($name,$value = NULL)
	{
		$this->_do = 'INSERT';
		
		if (is_array($name))
		{
			foreach ($name as $index => $val)
			{
				$this->insert($index, $val);
			}
		}
		else if (is_string($name))
		{
			$this->_temp['insert'][$name] = $value;
		}
		else if (is_int($name))
		{
			$this->_temp['insert'][] = $value;
		}
		else if ($name instanceof sql && $name->getType()=='select')
		{
			//insert select
			$this->_temp['insert'] = $name;
		}
		return $this;
	}
	
	/**
	 * on duplicate key update
	 * @param unknown $name
	 * @param unknown $value
	 */
	function duplicate($name,$value = NULL)
	{
		$this->_temp['duplicate'] = ' ON DUPLICATE KEY UPDATE';
		if (is_array($name))
		{
			foreach ($name as $index => $val)
			{
				$this->_duplicate_name[] = $index;
				$this->_duplicate_value[] = $val;
			}
		}
		else if (is_string($name))
		{
			$this->_temp['duplicate'] .= $name = $value;
		}
		return $this;
	}
	
	/**
	 * INSERT IGNORE INTO
	 */
	function ignore()
	{
		$this->_temp['ignore'] = true;
		return $this;
	}
	
	function delete()
	{
		$this->_do = 'DELETE ';
		return $this;
	}
	
	function from($table)
	{
		$this->_temp['from'][] = $table;
		return $this;
	}
	
	function where($sql,$array = array(),$combine = 'and')
	{
		if (is_array($sql))
		{
			$sql = '('.implode(') '.$combine.' (', $sql).')';
		}
		if (empty($this->_temp['where']))
		{
			$this->_temp['where'] = ' WHERE ('.$sql.')';
		}
		else
		{
			$this->_temp['where'] = $this->_temp['where'].' '.$combine.' ('.$sql.')';
		}
		
		if (empty($this->_temp['params']))
		{
			$this->_temp['params'] = $array;
		}
		else
		{
			$this->_temp['params'] = array_merge($this->_temp['params'],$array);
		}
		return $this;
	}
	
	function join($table,$on,$combine = 'AND')
	{
		$table = '`'.trim($table,'`').'`';
		if (is_array($on))
		{
			$on = implode(' '.$combine.' ', $on);
		}
		if (!empty($this->_temp['join']))
		{
			$this->_temp['join'] .= ' JOIN '.$table.' ON '.$on;
		}
		else
		{
			$this->_temp['join'] = ' JOIN '.$table.' ON '.$on;
		}
		return $this;
	}
	
	function leftJoin($table,$on,$combine = 'AND')
	{
		$table = '`'.trim($table,'`').'`';
		if (is_array($on))
		{
			$on = implode(' '.$combine.' ', $on);
		}
		if (!empty($this->_temp['join']))
		{
			$this->_temp['join'] .= ' LEFT JOIN '.$table.' ON '.$on;
		}
		else
		{
			$this->_temp['join'] = ' LEFT JOIN '.$table.' ON '.$on;
		}
		return $this;
	}
	
	function rightJoin($table,$on,$combine = 'AND')
	{
		$table = '`'.trim($table,'`').'`';
		if (is_array($on))
		{
			$on = implode(' '.$combine.' ', $on);
		}
		if (!empty($this->_temp['join']))
		{
			$this->_temp['join'] .= ' RIGHT JOIN '.$table.' ON '.$on;
		}
		else
		{
			$this->_temp['join'] = ' RIGHT JOIN '.$table.' ON '.$on;
		}
		return $this;
	}
	
	function innerJoin($table,$on,$combine = 'AND')
	{
		$table = '`'.trim($table,'`').'`';
		if (is_array($on))
		{
			$on = implode(' '.$combine.' ', $on);
		}
		if (!empty($this->_temp['join']))
		{
			$this->_temp['join'] .= ' INNER JOIN '.$table.' ON '.$on;
		}
		else
		{
			$this->_temp['join'] = ' INNER JOIN '.$table.' ON '.$on;
		}
		return $this;
	}
	
	function fullJoin($table,$on,$combine = 'AND')
	{
		$table = '`'.trim($table,'`').'`';
		if (is_array($on))
		{
			$on = implode(' '.$combine.' ', $on);
		}
		if (!empty($this->_temp['join']))
		{
			$this->_temp['join'] .= ' FULL JOIN '.$table.' ON '.$on;
		}
		else
		{
			$this->_temp['join'] = ' FULL JOIN '.$table.' ON '.$on;
		}
		return $this;
	}
	
	function union($all = false,$sql1,$sql2,$sql_)
	{
		$all = false;
		$sqls = func_get_args();
		$sql_string = array();
		foreach ($sqls as $index => $sql)
		{
			if ($sql === true)
			{
				$all = true;
			}
			else if ($sql instanceof sql)
			{
				$sql_string[] = $sql->__toString();
			}
			else if (is_string($sql))
			{
				$sql_string[] = $sql;
			}
		}
		return implode(' UNION '.($all?'ALL ':''), $sql_string);
	}
	
	function order($field,$order = 'ASC')
	{
		if (is_array($field))
		{
			foreach ($field as $asc => $field_temp)
			{
				if (in_array(strtolower(trim($asc)), array('asc','desc')))
				{
					$this->order($field_temp,$asc);
				}
				else if (is_int($asc))
				{
					$this->order($field_temp,$order);
				}
				else
				{
					$this->order($asc,$field_temp);
				}
			}
		}
		else if (is_string($field))
		{
			if (empty($this->_temp['order']))
			{
				$this->_temp['order'] = ' ORDER BY '.$field.' '.$order;
			}
			else
			{
				$this->_temp['order'] .= ','.$field.' '.$order;
			}
		}
		return $this;
	}
	
	function group($fields)
	{
		if (is_array($fields))
		{
			$fields = implode(',', $fields);
		}
		$this->_temp['group'] = ' GROUP BY '.$fields;
		return $this;
	}
	
	function limit($start,$length = NULL)
	{
		if ($length === NULL)
		{
			$this->_temp['limit'] = ' LIMIT '.$start;
		}
		else
		{
			$this->_temp['limit'] = ' LIMIT '.$start.','.$length;
		}
		return $this;
	}
	
	/**
	 * between(a,1,10,'and')
	 * @param unknown $field
	 * @param unknown $a
	 * @param unknown $b
	 * @param string $combine
	 * @return \framework\core\database\sql
	 */
	function between($field,$a,$b,$combine = 'and')
	{
		$this->where($field.' BETWEEN ? and ?',array($a,$b),$combine);
		return $this;
	}
	
	function notbetween($field,$a,$b,$combine = 'and')
	{
		$this->where($field.' NOT BETWEEN ? and ?',array($a,$b),$combine);
		return $this;
	}
	
	function in($field,array $data = array(),$combine = 'and')
	{
		$sql = self::fieldFormat($field).' IN ('.implode(',', array_fill(0, count($data), '?')).')';
	    $this->where($sql,$data,$combine);
		return $this;
	}
	
	function isNULL($fields,$combine = 'and')
	{
		if (is_array($fields))
		{
			foreach ($fields as $field)
			{
				$this->where($field.' is NULL',array(),$combine);
			}
		}
		else if (is_string($fields))
		{
			$this->where($fields.' is NULL',array(),$combine);
		}
		return $this;
	}
	
	function having($sql,array $data = array(),$combine = 'and')
	{
		if (is_array($sql))
		{
			$sql = '('.implode(') '.$combine.' (', $sql).')';
		}
		
		if (empty($this->_temp['having']))
		{
			$this->_temp['having'] = ' HAVING ('.$sql.')';
		}
		else
		{
			$this->_temp['having'] .= ' '.$combine.' ('.$sql.')';
		}
		
		if (empty($this->_temp['_having_params']))
		{
			$this->_temp['_having_params'] = $data;
		}
		else
		{
			$this->_temp['_having_params'] = array_merge($this->_temp['_having_params'],$data);
		}
		return $this;
	}
	
	function distinct()
	{
		$this->_temp['distinct'] = true;
		return $this;
	}
	
	static public function fieldFormat($field)
	{
		$field = trim($field,'` ');
		$fields = explode('.', $field);
		$string = array();
		foreach ($fields as $value)
		{
			$string[] = '`'.trim($value,'` ').'`';
		}
		return implode(',', $string);
	}
	
	function __toString()
	{
		switch (strtolower(trim($this->_do)))
		{
			case 'insert':
				$table = '';
				if (isset($this->_temp['from']) && !empty($this->_temp['from']))
				{
					$table = '`'.implode('`,`',$this->_temp['from']).'`';
				}
				
				$this->_temp['ignore'] = isset($this->_temp['ignore']) && $this->_temp['ignore']?' IGNORE':'';
				
				if (isset($this->_temp['insert']))
				{
					if (is_array($this->_temp['insert']))
					{
						//数字下标
						if (array_keys($this->_temp['insert']) == range(0, count($this->_temp['insert'])-1,1))
						{
							$fields = '';
							$this->_temp['params'] = array_values($this->_temp['insert']);
							$values = array_fill(0, count($this->_temp['params']), '?');
						}
						else
						{
							//字符串下标
							$fields = '(`'.implode('`,`', array_keys($this->_temp['insert'])).'`)';
							$this->_temp['params'] = $this->_temp['insert'];
							$values = array_map(function($value){return ':'.$value;}, array_keys($this->_temp['insert']));
						}
						$sql = 'INSERT'.$this->_temp['ignore'].' INTO '.$table.' '.$fields.' VALUES ('.implode(',', $values).')';
					}
					else if ($this->_temp['insert'] instanceof sql)
					{
						$sql = 'INSERT'.$this->_temp['ignore'].' INTO '.$table.' '.$this->_temp['insert']->__toString();
						$this->_temp['params'] = $this->_temp['insert']->getParams();
					}
				}
				
				return $sql;
			case 'select':
				$distinct = isset($this->_temp['distinct']) && $this->_temp['distinct']===true?'DISTINCT ':'';
				
				$fields = '*';
				if (isset($this->_temp['fields']) && !empty($this->_temp['fields']))
				{
					$fields = '';
					foreach ($this->_temp['fields'] as $as => $field)
					{
						if(!is_int($as))
						{
							$fields .= $field.' as '.$as.' ';
						}
						else
						{
							$fields .= $field;
						}
					}
					$fields = implode(',', $this->_temp['fields']);
				}
				
				$table = '';
				if (isset($this->_temp['from']) && !empty($this->_temp['from']))
				{
					$table = '`'.implode('`,`',$this->_temp['from']).'`';
				}
				
				$this->_temp['where'] = isset($this->_temp['where'])?$this->_temp['where']:'';
				
				$this->_temp['join'] = isset($this->_temp['join'])?$this->_temp['join']:'';
				
				$this->_temp['group'] = isset($this->_temp['group'])?$this->_temp['group']:'';
				
				$this->_temp['having'] = isset($this->_temp['having'])?$this->_temp['having']:'';
				
				$this->_temp['order'] = isset($this->_temp['order'])?$this->_temp['order']:'';
				
				$this->_temp['limit'] = isset($this->_temp['limit'])?$this->_temp['limit']:'';
				
				//for update
				$forUpdate = (isset($this->_temp['forUpdate']) && $this->_temp['forUpdate']===true)?' FOR UPDATE':'';
				
				$sql = 'SELECT '.$distinct.$fields.' FROM '.$table.$this->_temp['join'].$this->_temp['where'].$this->_temp['group'].$this->_temp['having'].$this->_temp['order'].$this->_temp['limit'].$forUpdate;
				return $sql;
			case 'update':
				$table = '';
				if (isset($this->_temp['from']) && !empty($this->_temp['from']))
				{
					$table = '`'.implode('`,`',$this->_temp['from']).'`';
				}
				
				$set = ' SET ';
				if (isset($this->_temp['update']) && is_array($this->_temp['update']))
				{
					foreach ($this->_temp['update'] as $index=>$value)
					{
						if (substr($index, -2) == '+=')
						{
							$index = substr($index, 0,-2);
							$set .= self::fieldFormat($index).'='.self::fieldFormat($index).'+'.$value.',';
						}
						else if (substr($index, -2) == '-=')
						{
							$index = substr($index, 0,-2);
							$set .= self::fieldFormat($index).'='.self::fieldFormat($index).'-'.$value.',';
						}
						else
						{
							$set .= self::fieldFormat($index).'='.$value.',';
						}
					}
					$set = rtrim($set,',');
				}
				else
				{
					$set = '';
				}
				
				$this->_temp['where'] = isset($this->_temp['where'])?$this->_temp['where']:'';
				
				$this->_temp['limit'] = isset($this->_temp['limit'])?$this->_temp['limit']:'';
				
				$sql = 'UPDATE '.$table.$set.$this->_temp['where'].$this->_temp['limit'];
				
				return $sql;
			case 'delete':
				$sql = 'DELETE ';
				return $sql;
		}
		return '';
	}
	
	function getParams()
	{
		$this->_temp['params'] = isset($this->_temp['params'])?$this->_temp['params']:array();
		$this->_temp['_having_params'] = isset($this->_temp['_having_params'])?$this->_temp['_having_params']:array();
		
		$this->_temp['params'] = array_merge($this->_temp['params'],$this->_temp['_having_params']);
		return $this->_temp['params'];
	}
	
	/**
	 * 关联params和sql后的sql
	 */
	function getSql($sql = NULL,$params = array())
	{
		if (empty($sql))
		{
			$sql = $this->__toString();
		}
		$sql_s = str_replace('?', '%s', $sql);
		if (empty($params))
		{
			$params = $this->getParams();
		}
		$num_params = array();
		$word_params = array();
		foreach ($params as $index=>$value)
		{
			if (is_int($index))
			{
				$num_params[] = $value;
			}
			else
			{
				$word_params[$index] = $value;
			}
		}
		$sql_w = vsprintf($sql_s,$num_params);
		
		foreach ($word_params as $index => $value)
		{
			$sql_w = str_replace(':'.$index, $value, $sql_w);
		}
		return $sql_w;
	}
	
	function clear()
	{
		$this->_temp = array();
		$this->_do = '';
	}
	
	/**
	 * sql查询的类型
	 * @return string
	 */
	function getType()
	{
		return strtolower(trim($this->_do));
	}
}