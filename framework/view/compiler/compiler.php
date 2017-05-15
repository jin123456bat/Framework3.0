<?php
namespace framework\view\compiler;

/**
 * @author fx
 *
 */
class compiler extends \framework\view\compiler
{
	/**
	 * 模板原文
	 * @var unknown
	 */
	private $_template;
	
	/**
	 * 变量
	 * @var array
	 */
	private $_variable = array();
	
	/**
	 * 模板中声明的字符串
	 * @var array
	 */
	private $_string = array();
	
	/**
	 * 模板钟声明的数组
	 * @var array
	 */
	private $_array = array();
	
	/**
	 * 函数
	 * @var array
	 */
	private $_functions = array();
	
	/**
	 * 左分隔符
	 * @var string
	 */
	private $_leftDelimiter = '{%';
	
	/**
	 * 右分隔符
	 * @var string
	 */
	private $_rightDelimiter = '%}';
	
	/**
	 * 模板在编译过程中的临时变量
	 * @var array
	 */
	private $_temp_variable = array();
	
	/**
	 * 模板文件所在路径
	 * 当使用include标签的时候在这些目录中按照顺序搜索
	 * @var unknown
	 */
	private $_template_path = array();
	
	function __construct($template = '')
	{
		$this->_template = $template;
	}
	
	/**
	 * 获取左开始符号
	 * @return string
	 */
	function getLeftDelimiter()
	{
		return $this->_leftDelimiter;
	}
	
	/**
	 * 设置左开始符号  默认为{%
	 * @param unknown $leftDelimiter
	 */
	function setLeftDelimiter($leftDelimiter)
	{
		$this->_leftDelimiter = $leftDelimiter;
	}
	
	/**
	 * 获取右结束符
	 * @return string
	 */
	function getRightDelimiter()
	{
		return $this->_rightDelimiter;
	}
	
	/**
	 * 设置右结束符
	 * @param unknown $rightDelimiter
	 */
	function setRightDelimiter($rightDelimiter)
	{
		$this->_rightDelimiter = $rightDelimiter;
	}
	
	/**
	 * 设置模板内容
	 */
	function setTempalte($tempalte)
	{
		$this->_template = $tempalte;
	}
	
	/**
	 * 在模板中添加变量
	 * @param unknown $var
	 * @param unknown $val
	 */
	function assign($var,$val)
	{
		if (is_callable($val))
		{
			$this->_functions[$var] = $val;
		}
		else
		{
			$this->_variable['$'.$var] = $val;
		}
	}
	
	/**
	 * 设置模板文件夹路径
	 */
	function setTemplatePath($path)
	{
		$this->_template_path[] = $path;
	}
	
	/**
	 * 获取模板文件夹路径
	 * @return \framework\view\compiler\unknown
	 */
	function getTemplatePath()
	{
		return $this->_template_path;
	}
	
	/**
	 * 已经计算好的表达式或者语句的值
	 * @example
	 * 	$result = NULL
	 * 	if($this->calculation('1+2',$result))
	 * 	{
	 * 		return $result;
	 * 	}
	 * 
	 * @param unknown $string 原始表达式
	 * @param &$result 原始表达式的值将会填充到这个变量中
	 * @return mixed|boolean 这个表达式是否已经计算过，并且成功拿到了值
	 */
	function calculation($string,&$result = NULL)
	{
		if ($string[0] == '$')
		{
			if (isset($this->_variable[$string]))
			{
				$result = $this->_variable[$string];
				return true;
			}
			else if (isset($this->_string[$string]))
			{
				$result = $this->_string[$string];
				return true;
			}
			else if (isset($this->_array[$string]))
			{
				$result = $this->_array[$string];
				return true;
			}
			else if (isset($this->_temp_variable[$string]))
			{
				$result = $this->_temp_variable[$string];
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 获取所有已经计算好了的值
	 * @return array()
	 */
	function getCalculation()
	{
		return $this->_temp_variable;
	}
	
	private function guid(){
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);
		$uuid = chr(123)
		.substr($charid, 0, 8).$hyphen
		.substr($charid, 8, 4).$hyphen
		.substr($charid,12, 4).$hyphen
		.substr($charid,16, 4).$hyphen
		.substr($charid,20,12)
		.chr(125);
		return $uuid;
	}
	
	/**
	 * 记录一个字符串变量
	 * @param unknown $val
	 * @return string 
	 */
	private function remeberString($val)
	{
		do{
			$key = '$_string_'.uniqid();
		}
		while (isset($this->_variable[$key]) || isset($this->_string[$key]));
		$this->_string[$key] = $val;
		return $key;
	}
	
	private function remeberArray($val)
	{
		do{
			$key = '$_array_'.uniqid();
		}
		while (isset($this->_variable[$key]) || isset($this->_array[$key]));
		$this->_array[$key] = $val;
		return $key;
	}
	
	/**
	 * 获取编译后的内容
	 */
	function fetch()
	{
		$pattern = '!'.$this->_leftDelimiter.'.*'.$this->_rightDelimiter.'!i';
		$this->_template = preg_replace_callback($pattern, function($matches){
			//提出声明的字符串
			$after_string = preg_replace_callback('!\'[^\']*\'!i', function($match){
				$key = $this->remeberString(trim($match[0],'\''));
				return $key;
			}, $matches[0]);
			
			//声明的数组
			$after_array = preg_replace_callback('!\[[\s\S]*\]!Ui', function($match){
				$array = explode(',', trim($match[0],'[]'));
				foreach ($array as &$value)
				{
					$value = $this->variable($value);
				}
				$key = $this->remeberArray($array);
				return $key;
			}, $after_string);
			
			return $after_array;
		},$this->_template);
		
		//处理所有标签
		$dirs = scandir(ROOT.'/framework/view/tag');
		if ($dirs)
		{
			array_map(function($file){
				if ($file != '.' && $file != '..')
				{
					$this->tag(pathinfo($file,PATHINFO_FILENAME));
				}
			}, $dirs);
		}
		
		
		//处理 所有的block
		$dirs = scandir(ROOT.'/framework/view/block');
		if ($dirs)
		{
			array_map(function($file){
				if ($file != '.' && $file != '..')
				{
					$this->block(pathinfo($file,PATHINFO_FILENAME));
				}
			}, $dirs);
		}
		
		//剩下的全都是变量了
		$pattern = '!'.$this->_leftDelimiter.'[\s\S]*'.$this->_rightDelimiter.'!U';
		$this->_template = preg_replace_callback($pattern, function($match){
			//获取到所有模板标签，然后判断，是标签还是变量
			//对标签和表达式做一个处理
			$string = trim(ltrim(rtrim($match[0],$this->_rightDelimiter),$this->_leftDelimiter));
			//其它的都按照普通变量来处理
			return $this->variable($string);
		}, $this->_template);
		
		return $this->_template;
	}
	
	/**
	 * 获得标签中的参数
	 * @param unknown $label
	 */
	private function getTagParameter($label)
	{
		$parameter = array();
		$string = ltrim(rtrim($label,$this->_rightDelimiter),$this->_leftDelimiter);
		$first = true;
		foreach(explode(' ', $string) as $str)
		{
			if (empty($str))
			{
				continue;
			}
			if ($first)
			{
				$first = false;
				continue;
			}

			list($key,$value) = explode('=', $str);
			$value = trim($value,'\'" ');
			
			$parameter[$key] = $this->variable(trim($value));
		}
		return $parameter;
	}
	
	/**
	 * 处理标签
	 * @param unknown $block
	 */
	private function tag($tag)
	{
		$pattern = '!'.$this->_leftDelimiter.$tag.' [\s\S]*'.$this->_rightDelimiter.'!Ui';
		$this->_template = preg_replace_callback($pattern, function($match) use ($tag){
			
			$parameter = $this->getTagParameter($match[0]);
			$class = 'framework\\view\\tag\\'.$tag;
			if (class_exists($class,true))
			{
				$class = new $class();
				return $class->compile($parameter,$this);
			}
			return $match[0];
		},$this->_template);
	}
	
	/**
	 * 计算函数的值
	 * @param $string  $string中已经去掉了Delimiter
	 * @return mixed 返回函数计算的结果
	 */
	private function func($string)
	{
		if(preg_match('!([a-zA-Z_]\w*)\(.*\)!', $string,$func_name))
		{
			preg_match('!\((.*)\)!', $string,$args);
			$param_arr = explode(',', $args[1]);
			$param_arr = array_map(function($v){
				$value = $this->expression($v);
				return $value;
			} ,$param_arr);
			$func_name = $func_name[1];
			
			//优先自定义函数  自定义函数会覆盖系统函数
			if (isset($this->_functions[$func_name]))
			{
				$value = call_user_func_array($this->_functions[$func_name], $param_arr);
				return $value;
			}
			else if (function_exists($func_name))
			{
				//检查系统函数
				$value = call_user_func_array($func_name,$param_arr);
				return $value;
			}
		}
		//假如没有函数存在则直接自动返回
		return $string;
	}
	
	private function strrpos($string,$word,$offset)
	{
		for($i = 0;$i<strlen($string);$i++)
		{
			if ($i == $offset)
			{
				return false;
			}
			if($string[$i] == $word)
			{
				return $i;
			}
		}
		return false;
	}
	
	/**
	 * 判断是否存在算术表达式
	 * @param unknown $string
	 */
	private function getBracketsExpression($string,$offset = 0,&$left_brackets_pos = 0,&$right_brackets_pos = 0)
	{
		$left_brackets_pos = $this->strrpos($string,'(',$offset);
		$right_brackets_pos = strpos($string,')',$left_brackets_pos);
		$offset = -$left_brackets_pos;
		if ($left_brackets_pos!==false && $right_brackets_pos!==false)
		{
			if (!isset($string[$left_brackets_pos-1]) || in_array($string[$left_brackets_pos-1], array('(','+','-','*','/','.')))
			{
				$express =  substr($string, $left_brackets_pos+1,$right_brackets_pos-$left_brackets_pos-1);
				return $express;
			}
			else
			{
				
				return $this->getBracketsExpression($string,$offset,$left_brackets_pos,$right_brackets_pos);
			}
		}
		return false;
	}
	
	/**
	 * 计算表达式
	 * @param unknown $string 表达式
	 * @return string 表达式计算结果
	 */
	public function variable($string)
	{
		$calString = $string;
		//优先计算表达式中的算术括号
		$left_brackets_pos = 0;
		$right_brackets_pos = 0;
		$expression = $this->getBracketsExpression($calString,0,$left_brackets_pos,$right_brackets_pos);
		while ($expression)
		{
			$this->_temp_variable['$'.$expression] = $this->expression($expression);
			$calString = substr($calString,0, $left_brackets_pos) . $this->_temp_variable['$'.$expression] . substr($calString, $right_brackets_pos+1);
			$expression = $this->getBracketsExpression($calString,0,$left_brackets_pos,$right_brackets_pos);
		}
		//计算表达式中的函数
		while (preg_match('![a-zA-Z_]\w*\([^\(\)]+\)!', $calString))
		{
			$calString = preg_replace_callback('![a-zA-Z_]\w*\([^\(\)]+\)!', function($match){
				$value = $this->func($match[0]);
				$this->_temp_variable['$'.$match[0]] = $value;
				
				return $this->_temp_variable['$'.$match[0]];
			}, $calString);
		}
		//计算表达式
		$calString = $this->expression($calString);
		return $calString;
	}
	
	/**
	 * 计算表达式的值
	 * 表达式中不能有括号
	 * @param string $string 表达式字符串  去掉Delimiter
	 * @return 表达式子计算的结果
	 */
	private function expression($string)
	{
		if (isset($this->_temp_variable['$'.$string]))
		{
			return $this->_temp_variable['$'.$string];
		}
		
		$calString = $string;
		//变量替换  数组 将在模板中定义的数组替换回来
		$calString = preg_replace_callback('!\$([a-zA-Z_]\w*\.)*[a-zA-Z_]\w*!', function($match){
			$result = NULL;
			if (strpos($match[0],'.'))
			{
				$array = explode('.',$match[0]);
				if (isset($this->_variable[current($array)]))
				{
					$data = $this->_variable[current($array)];
				}
				next($array);
				while (current($array))
				{
					$data = isset($data[current($array)])?$data[current($array)]:'';
					next($array);
				}
				return $data;
			}
			else
			{
				if (isset($this->_array[$match[0]]))
				{
					return 'array('.implode(',', $this->_array[$match[0]]).')';
				}
				return $match[0];
			}
		}, $calString);
		
		
		//变量替换字符串
		foreach ($this->_string as $key => $value)
		{
			eval($key.' = \''.$value.'\';');
		}
		foreach ($this->_variable as $key => $value)
		{
			if (is_string($value))
			{
				eval($key.' = \''.$value.'\';');
			}
		}
		
		$result = @eval('return '.$calString.';');
		$this->_temp_variable['$'.$string] = $result;
		
		return $result;
	}
	
	/**
	 * 处理block
	 */
	private function block($block)
	{
		//$pattern = '!'.$this->_leftDelimiter.$block.'[\s\w\(\).\+\-\*/=,\'$[\]]+'.$this->_rightDelimiter.'([\s\S]*)'.$this->_leftDelimiter.'/'.$block.$this->_rightDelimiter.'!i';
		$pattern = '!{%section[\s\w\(\).\+\-\*/=,\'$[\]]+%}(((?<block>{%section)|(?<-block>{%/section%})|.)*(?(block)(?!))){%/section%}!';
		var_dump($pattern);
		var_dump($this->_template);
		$this->_template = preg_replace_callback($pattern, function($match) use($block){
			$content = $match[1];
			if(preg_match('!'.$this->_leftDelimiter.$block.'([^'.$this->_rightDelimiter.']*)'.$this->_rightDelimiter.'!i', $match[0],$left))
			{
				$parameter = array();
				$parameters = explode(' ',trim($left[1]));
				foreach ($parameters as $p)
				{
					list($key,$value) = explode('=', $p);
					$parameter[trim($key)] = $this->variable(trim($value));//将表达式+函数拿去计算
				}
			}
			else
			{
				$parameter = array();
			}
			$class = 'framework\\view\\block\\'.$block;
			if (class_exists($class,true))
			{
				$class = new $class();
				return $class->compile($content,$parameter,$this);
			}
			return $match[0];
		}, $this->_template);
	}
}