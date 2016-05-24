<?php

/*
* Author 雷日锦 QQ:362606856
* 
*/
 /*
for($i=0;$i<2;$i++){
	$urls[]="http://mil.qq.com/";
}
   
 $sp=new Spider();
 $sp->start($urls,function($data){
	 print_r($data); 
 });
 $st=microtime(true);
 echo "访问时间：".(microtime(true)-$st);
 //$data=$sp->redata;
 //print_r($data);
 */
class Spider{
	public $mh;
	public $urls;
	public $conn;
	public $fun;
	public function __construct(){
		
		 
	}
	
	public function start($urls,$fun){
		$this->fun=$fun;
		$this->urls=$urls;
		$this->mh = curl_multi_init();
		foreach ($urls as $i => $url) {   
		  $this->conn[$i] = curl_init($url);   
		  curl_setopt($this->conn[$i], CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 6.0)");   
		  curl_setopt($this->conn[$i], CURLOPT_HEADER ,0);   
		  curl_setopt($this->conn[$i], CURLOPT_CONNECTTIMEOUT,60);   
		  curl_setopt($this->conn[$i],CURLOPT_RETURNTRANSFER,true); 
		  curl_setopt($this->conn[$i], CURLOPT_FOLLOWLOCATION, true);
		  curl_multi_add_handle ($this->mh,$this->conn[$i]);   
		}
		$active = false;
		do {   
		 	curl_multi_exec($this->mh,$active);   
		} while ($active); 

		foreach ($this->urls as $i=>$url) {
			$data = curl_multi_getcontent($this->conn[$i]);
		 	call_user_func($this->fun,array("url"=>$url,"content"=>$data));    
			unset($data); 
			curl_multi_remove_handle($this->mh, $this->conn[$i]);  
		}
		$this->close();  
	}
	
	
	public function close(){
		curl_multi_close($this->mh);  
	}
	
} 
 
   
 
   
?>