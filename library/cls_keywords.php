<?php
/**
*提取关键字类
*dict 词典目录 base基础词典 my.txt用户自定义词典
*
* Copyright 雷日锦 362606856@qq.com
**/
class keywords{
	public $run_time;//运行时间
	public $dict;//词典内容
	public $dir;//词典文件夹
	public $base;
	public $my;
	public function __construct(){
		
	}
	
	public function setDir($dir="dict/"){
		$this->dir=$dir?$dir:"dict/";
		$this->set(); 
	}
	/**
	*编译全部词典
	*/
	public function  save_all(){
		set_time_limit(0);
		if(file_exists($this->dir."dict_base.php") && file_exists($this->dir."dict_my.php")){
			$c1=filemtime($this->dir."dict_all.php");
			$c2=filemtime($this->dir."base.txt") ;
			$c3=filemtime($this->dir."my.txt") ;
			if($c1>$c2 && $c1>$c3){
				require($this->dir."dict_all.php");
				return $this;
			}
		}
		$this->save_my();
		$my=$this->my;
		$this->save_base();
		$base=$this->base;
		$my=$my."\r\n".$base;
		$x=explode("\r\n",$my);
		$temp=array();
		foreach($x as $v){
			if(!empty($v)){
				$v=trim($v);
				$temp[strlen($v)][]=$v;
			}
		}
		$arr=array();
		krsort($temp);
		if($temp){
			foreach($temp as $d){
				foreach($d as $c){
					$arr[]=$c;
				}
			}
		}
		$arr=array_unique($arr);
		$data="";
		$data.="<?php\r\n";
		$data.='$dict_all=array('."\r\n";
		foreach($arr as $k=>$v){
			if($k==0){
				$data.="'$v'";
			}else{
				$data.=",'$v'";
			}
		}
		$data.="\r\n);\r\n?>";
		file_put_contents($this->dir."dict_all.php",$data);
		return $this;
	}
	/*
	*编译基础词典
	*/
	public function save_base(){
		set_time_limit(0);
		if(file_exists($this->dir."dict_base.php")){
			$c1=filemtime($this->dir."dict_base.php");
			$c2=filemtime($this->dir."base.txt") ;
			if($c1>$c2){
				require($this->dir."dict_base.php");
				$this->base=file_get_contents($this->dir."base.txt");
				return $this;
			}
		}
		$my=file_get_contents($this->dir."base.txt");
		$x=explode("\r\n",$my);
		$temp=array();
		foreach($x as $v){
			if(!empty($v)){
				$v=trim($v);
				$temp[strlen($v)][]=$v;
			}
		}
		$arr=array();
		krsort($temp);
		if($temp){
			foreach($temp as $d){
				foreach($d as $c){
					$arr[]=$c;
				}
			}
		}
		 
		$arr=array_unique($arr);
		$data="";
		$data.="<?php\r\n";
		$data.='$dict_base=array('."\r\n";
		foreach($arr as $k=>$v){
			if($k==0){
				$data.="'$v'";
			}else{
				$data.=",'$v'";
			}
		}
		$data.="\r\n);\r\n?>";
		file_put_contents($this->dir."dict_base.php",$data);
		$this->base=$my;
		return $this;
	}
	/*
	*编译用户自定义词典
	*/
	public function save_my(){
		set_time_limit(0);
		if(file_exists($this->dir."dict_my.php")){
			$c1=filemtime($this->dir."dict_my.php");
			$c2=filemtime($this->dir."my.txt") ;
			if($c1>$c2){
				require($this->dir."dict_my.php");
				$this->my=file_get_contents($this->dir."my.txt");
				return $this;
			}
		}
		$my=file_get_contents($this->dir."my.txt");
		$x=explode("\r\n",$my);
		$temp=array();
		foreach($x as $v){
			if(!empty($v)){
				$v=trim($v);
				$temp[strlen($v)][]=$v;
			}
		}
		$arr=array();
		krsort($temp);
		if($temp){
			foreach($temp as $d){
				foreach($d as $c){
					$arr[]=$c;
				}
			}
		}
		$arr=array_unique($arr);
		$data="";
		$data.="<?php\r\n";
		$data.='$dict_my=array('."\r\n";
		foreach($arr as $k=>$v){
			if($k==0){
				$data.="'$v'";
			}else{
				$data.=",'$v'";
			}
		}
		$data.="\r\n);\r\n?>";
		file_put_contents($this->dir."dict_my.php",$data);
		$this->my=$my;
		return $this;
	}
	/*
	*设置要使用的词典
	*/
	public function set($type='base'){
		
		switch($type){
			case "my":
					if(file_exists($this->dir."dict_my.php")){
						require($this->dir."dict_my.php");
						
						$this->dict=$dict_my;
					}
					
				break;
			case 'all':
					if(file_exists($this->dir."dict_all.php")){
						require($this->dir."dict_all.php");
						$this->dict=$dict_all;
					}
				break;
			default :
					if(file_exists($this->dir."dict_base.php")){
						require($this->dir."dict_base.php");
						$this->dict=$dict_base;
					}
				break;
		}
		return $this;
	}
	/*
	*获取关键词
	*$str 文本内容
	*$limit 关键词数量 
	*/
	public function get($str,$limit=10000){
		$str=strip_tags($str);
		$start=microtime(true);
		$i=0;
		$temp=array();
		if(empty($this->dict)) return false;
		foreach($this->dict as $k=>$v){ 
		if(!empty($v)){
			if(strpos($str,$v)!==false){
				$temp[]=$v;
				$i++;
				if($i>$limit) break;
			}
		}
		}
		$this->run_time=microtime(true)-$start;
		return $temp;
	}
}


?>