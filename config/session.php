<?php
return array(
	'save_handler' => 'db',//session的存储方式  默认的为files 也可以为db  redis memcache
	'block'=>true,//session读取的时候是否是阻塞方式
	
	//当save_handler=db中的时候有效
	'db' => array(
		'model' => 'session',//model的名字  至于连接方式等可以在model中配置
	),
	
	//当save_handler=files的时候有效  这里的配置实际上是使用php.ini中的配置 好处是这里可以使用变量以及函数
	'files' => array(
		'save_path' => "d:/wamp64/tmp"
	),
	
	
	//当save_handler=redis的时候有效
	'redis' => array(
	),
	
	//当save_handler = memcache的时候有效
	'memcache' => array(
		
	),
);