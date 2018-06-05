<?php
	
	class curl{
		public $ch;
		public $curl_json=false;
		function __construct(){
					
		}
		function init($url){
			$this->ch=curl_init();
			$header = array(
	            "Content-Type:application/x-www-form-urlencoded; charset=UTF-8"
	    	);
	    	curl_setopt($this->ch, CURLOPT_URL, $url);
	    	curl_setopt($this->ch, CURLOPT_HTTPHEADER  , $header);
	    	curl_setopt($this->ch, CURLOPT_HEADER, 0);
			curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
			  
		}
		function setJson($json){
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
			    'Content-Type: application/json',
			    'Content-Length: ' . strlen($json))
			);
		}
		function get($url,$data=array()){
			$this->init($url);
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
			if($this->curl_json){
				$this->setJson($data);
				curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
			}else{
				curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
			} 			 
			$content= curl_exec($this->ch);
			curl_close($this->ch);
			return $content;
		}
		
		function post($url,$data=array(),$header=array()){
			$this->init($url,$header);
			//curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST'); 
		    curl_setopt($this->ch, CURLOPT_POST, TRUE);
		    if($this->curl_json){
				$this->setJson($data);
			} 
		    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 1);
			 curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
		    $ret = curl_exec($this->ch);
		    if($ret===false){
		    	exit( curl_error($this->ch) ) ;
		    }	
		    curl_close($this->ch);
		    return $ret;
		}
		
		function put($url,$data=array()){
			$this->init($url); 
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);			 
			$content= curl_exec($this->ch);
			
			if($content===false){
		    	exit( curl_error($this->ch) ) ;
		    }
			curl_close($this->ch);
			return $content;
		}
		
		function delete($url,$data=array()){
			$this->init($url);
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);	 		 
			$content= curl_exec($this->ch);
			curl_close($this->ch);
			return $content;
			
		}
	} 
?>