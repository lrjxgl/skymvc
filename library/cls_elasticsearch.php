<?php
if(!class_exists("curl")){
	require_once "cls_curl.php";	
}
class elasticSearch{
	public $curl;
	public $host;
	public $database;
	public function __construct($host="http://127.0.0.1:9200"){
		$this->curl=new curl();
		$this->host=$host;		
	}
	
	public function setHost($host){
		$this->host=$host;
	}
	public function setDb($db){
		$this->database=$db;
	}
	public function get($path="article/1",$data=array()){
		$url=$this->host."/".$path;
		$data=$this->curl->get($url,$data);
		return $data;
	}
	public function mget($path,$data="",$query="pretty"){
		$url=$this->host."/".$path."/_mget?";
		$this->curl->curl_json=true;
	 
		$data=$this->curl->get($url,$data);
		return $data;
	} 
	public function post($path="article",$data="",$query="pretty"){
		$url=$this->host."/".$path."/?".$query;
		if(is_array($data)){
			$data=json_encode($data);
		}
		$data=$this->curl->post($url,$data);
		return $data;
	}
	/****article/id******/
	public function put($path="article/id",$data="",$query="pretty"){
		$url=$this->host."/".$path."/?".$query;
		$data=$this->curl->put($url,$data);
		return $data;
	}
	/***创建唯一Id文档****/
	public function create($path="article/id",$data="",$query="pretty"){
		$url=$this->host."/".$path."/?op_type=create&".$query;
		if(is_array($data)){
			$data=json_encode($data);
		}
		$data=$this->curl->put($url,$data);
		return $data;
	}
	
	public function delete($path="article/id"){
		$url=$this->host."/".$path;
		$data=$this->curl->delete($url);
		return $data;
	}
	public function deleteByQuery($path="article",$data="",$query="pretty"){
		$url=$this->host."/".$path."/_delete_by_query?".$query;
		$data=$this->curl->post($url,$data);
		return $data;
	}
	public function postMore($data="",$query="pretty"){
		$url=$this->host."/_bulk?".$query;
		$data=$this->curl->post($url,$data);
		return $data;
	} 
	
	public function search($path,$data,$_source=""){
	 
		$url=$this->host."/".$path."/_search?".$_source;
		$res=$this->curl->get($url,$data);
		$sdata=json_decode($res,true);
		$data=array();
		if(isset($sdata['hits'])){
			foreach($sdata['hits']['hits'] as $v){
				$data[]=$v['_source'];
			}
			$data=array(
				"rscount"=>$sdata['hits']['total'],
				"data"=>$data
			);
		}
		return $data;
	}
	
	public function clear($path){
		
	}
} 
?>