<?php
if(!class_exists("curl")){
	require_once "cls_curl.php";	
}
class elasticSearch{
	public $curl;
	public $host;
	public $database;
	public function __construct($host){
		$this->curl=new curl();
		$this->host=$host;		
	}
	
	public function setHost($host){
		$this->host=$host;
	}
	public function setDb($db){
		$this->database=$db;
	}
	public function get($path,$data=array()){
		$url=$this->host."/".$path;
		$data=$this->curl->get($url,$data);
		return $data;
	}
	
	public function post($path,$data="",$query="pretty"){
		$url=$this->host."/".$path."/?".$query;
		$data=$this->curl->post($url,$data);
		return $data;
	}
	
	public function put($path,$data="",$query="pretty"){
		$url=$this->host."/".$path."/?".$query;
		$data=$this->curl->put($url,$data);
		return $data;
	}
	/***创建唯一Id文档****/
	public function create($path,$data="",$query="pretty"){
		$url=$this->host."/".$path."/?op_type=create&".http_build_query($query);
		$data=$this->curl->put($url,$data);
		return $data;
	}
	
	public function delete($path,$data="",$query="pretty"){
		$url=$this->host."/".$path."/?".$query;
		$data=$this->curl->delete($url,$data);
		return $data;
	}
	
	public function postMore($data="",$query="pretty"){
		$url=$this->host."/_bulk?".$query;
		$data=$this->curl->post($url,$data);
		return $data;
	} 
	
	public function mget($path,$data="",$query="pretty"){
		$url=$this->host."/".$path."/_mget?";
		$this->curl->curl_json=true;
	 
		$data=$this->curl->get($url,$data);
		return $data;
	}
	
	public function search($path,$data,$_source=""){
	 
		$url=$this->host."/".$path."/_search?".$_source;
		$data=$this->curl->get($url,$data);
		return $data;
	}
	
	public function clear($path){
		
	}
} 
?>