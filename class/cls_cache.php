<?php
if(!defined("ROOT_PATH")){
	define("ROOT_PATH",dirname(str_replace("\\", "/", dirname(__FILE__)))."/");
}
/*
缓存类文件
*/
if(!defined("ROOT_PATH")){
	define("ROOT_PATH","");
}
class cache
{
	public $expire=3600;
	public $cache_dir; 
	public $mem=null;
	public $cache_type="file";
	public $mysql;
	public $cacheconfig=array();	
	function __construct($cacheconfig=array())
	{
		$this->cacheconfig=$cacheconfig;
		$this->init();
		$this->defaultType();
	}
	
	public function init(){
		$this->cache_dir=ROOT_PATH."temp/filecache";
	}
 
	public function keydir($key){
		$d=md5($key);
		return "/".$d{0}."/".$d[1]."/".$d[2]."/";
	}
	public function setType($cache_type){
	 
		if(!isset($this->cacheconfig[$cache_type])){
			return $this->defaultType();
		}
		$this->cache_type=$cache_type;
		return $this;
	}
	
	 
	
	public function defaultType(){
		 
		if($this->cacheconfig['redis']){
			$this->cache_type="redis";
		}elseif($this->cacheconfig['memcache']){
			$this->cache_type="memcache";
		}elseif($this->cacheconfig['mysql']){
			$this->cache_type='mysql';
		}else{
			$this->cache_type="file";
		}
		 
		
	}
	
	public function set($key,$val,$expire=3600)
	{
		 
		 switch($this->cache_type){
			case "memcache":
					$this->mem_set($key,$val,$expire);
				break;
			case "redis":
					$this->redis_set($key,$val,$expire);
				break; 
			case "file":
			
					$this->file_set($key,$val,$expire);
					
				break;
			case "php":
					$this->php_set($key,$val,$expire);
				break;
			case "mysql":
					$this->mysql_set($key,$val,$expire);
				break;
		 }
	}
	
	public function get($key){
		 
		 switch($this->cache_type){
			 case "memcache":
			 			return $this->mem_get($key);
					break;
			case "redis":
						return $this->redis_get($key);
					break;
			 case "file":
						return $this->file_get($key);
					break;
			 case "php":
			 			return $this->php_get($key);
			 		break;
			case "mysql":
					
						return $this->mysql_get($key);	
					break;
		 }
	}
	

	
	/*
	@获取缓存内容
	*@file 文件名
	*/
	public function file_get($key)
	{
		$key=preg_replace("/[^\w]/","",$key);
		$dir=$this->cache_dir.$this->keydir($key);
		
		$file=$dir.$key.".txt";
		if(file_exists($file)){
			$data=json_decode(file_get_contents($file),true);
			if($data['expire']<time()){
				return false;
			}
			return $data['data'];
			
		}else{
			return false;
		}
		 
	}
	
	/**
	*设置缓存
	*/
	public function file_set($key,$val,$expire=3600){
		$key=preg_replace("/[^\w]/","",$key);
		$dir=$this->cache_dir.$this->keydir($key);
		$file=$dir.$key.".txt";
		$this->umkdir($dir);
		$data=array("expire"=>time()+$expire,"data"=>$val);
		file_put_contents($file,json_encode($data)); 
	}
	
	/**
	*@获取php缓存 可以直接include文件
	*/
	public function php_get($key)
	{
		$key=preg_replace("/[^\w]/","",$key);
		$dir=$this->cache_dir.$this->keydir($key);
		$file=$dir.$key.".php";
		if(file_exists($file)){
			@include($file);
			return $$key;
		}else{
			return false;
		}

	}
	 
	
	 
	/**
	*@写入php缓存 一般用作配置缓存 永久有效
	*/
	public function php_set($key,$val,$expire=3600)
	{
		$key=preg_replace("/[^\w]/","",$key);
		$dir=$this->cache_dir.$this->keydir($key);
		$file=$dir.$key.".php";
		$content='<?php'." \r\n".'$'.$key.'='.var_export($val,true).";\r\n?>";
		file_put_contents($file,$content);
	}
	
	
	/*
	*mem缓存
	*/
	
	 
	
	public function mem_set($k,$v,$expire=0){
		if(function_exists("cache_mem_set")){
			cache_mem_set($k,$v,$expire);
		}else{
			$this->file_set($k,$v,$expire);
		}
	}
	
	public function mem_get($k){
		if(function_exists("cache_mem_get")){
			return cache_mem_get($k);
		}else{
			return $this->file_get($k);
		}
	}
	
	/**redis 缓存**/
	public function redis_set($k,$v,$expire=0){
		if(function_exists("cache_redis_set")){
			cache_redis_set($k,$v,$expire);
		}else{
			$this->file_set($k,$v,$expire);
		}
	}
	
	public function redis_get($k){
		if(function_exists("cache_redis_get")){
			return cache_redis_get($k);
		}else{
			return $this->file_get($k);
		}
	}
	
	/**
	*mysql 缓存
	*/
	
 
		
	public function mysql_set($k,$v,$expire=3600){
		
		if(function_exists("cache_mysql_set")){
			cache_mysql_set($k,$v,$expire);
		}else{
			$this->file_set($k,$v,$expire);
		}
		
	}
	
	public function mysql_get($k){
		if(function_exists("cache_mysql_get")){
			return cache_mysql_get($k);
		}else{
			return $this->file_get($k);
		}
		 
	}
	
	
	/**
	@删除目录缓存
	*/
	public function clear($type=0)
	{
		$this->delFile($this->cache_dir,$type);
	}
	
	/**
	@删除目录下的所有文件 保留当前目录
	*/
	function delfile($dir,$rmdir=0)
	{
		$hd=opendir($dir);
		while(($f=readdir($hd))!==false)
		{
			if($f!=".." and $f!=".")
			{
				if(is_file($dir."/".$f)){
					unlink($dir."/".$f);
				}else
				{
					self::delfile($dir."/".$f."/",$rmdir);
				}
			}
		}
		
		closedir($hd);
		if($rmdir)
		{
			rmdir($dir);
		}
	}
	
	
	/*创建文件夹*/
	function umkdir($dir)
	{
		$dir=str_replace(ROOT_PATH,"",$dir);
		$arr=explode("/",$dir);
		foreach($arr as $key=>$val)
		{			
			$d="";
			for($i=0;$i<=$key;$i++)
			{
				$d.=$arr[$i]."/";
			}
			if(!file_exists(ROOT_PATH.$d))
			{ 
				mkdir(ROOT_PATH.$d,0755);
			} 
		}
		
	}

	

		
}

?>