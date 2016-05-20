<?php
/*
$solink= new solink(array("url"=>"http://www.skymvc.com"));
$solink->get_content();
 $solink->get_title();
print_r($solink->get_link());

*/
class solink{
	public $content;
	public $url;
	public $dir;
	public $host;//当前域名
	public $scheme;
	public $selfsite;
	public $domain;//主域名
	public function __construct($config=array()){
		$this->url=isset($config['url'])?$config['url']:"";//要采集的url
		$this->selfsite=isset($config['selfsite'])?$config['selfsite']:0;//1 只采集自己当前域名的站 0.采集主域名及子域名 2.采集所有
		if($this->url){  
			$this->parseurl();
		}
	}
	public function set($config=array()){
		$this->url=isset($config['url'])?$config['url']:"";//要采集的url
		$this->selfsite=isset($config['selfsite'])?$config['selfsite']:2;//1 只采集自己当前域名的站 0.采集主域名及子域名 2.采集所有  
		$this->parseurl();
	}
	public function get_content($content=''){
		if($content){
			$this->content=$this->toutf8($content);
		}else{
	 		$this->content=$this->toutf8($this->curl_get_contents($this->url));
		}
	 
	}
	public function get_link(){
		preg_match_all("/<a[^>]*href=[\"']([^\"']*)[\"']/iUs",$this->content,$a);
		if(isset($a[1])){
			foreach($a[1] as $v){
				$u=$this->builtlink($v);
				$u && $urls[]=$u;
			}
			return $urls;
		}
		return array();
	}
	public function get_title(){
		preg_match("/<title>(.*)<\/title>/iUs",$this->content,$a);
		if(isset($a[1])) return $a[1];
		return false;
	}
	
	public function get_keywords(){
		preg_match("/<meta.*name=[\'\"]keywords[\'\"][^>]*>/i",$this->content,$a);
		if(empty($a)) return false;
		$b=$a[0];
		preg_match("/content=[\'\"]([^'\"]*)/i",$b,$a);
		if(isset($a[1])) return $a[1];
		return false;
	}
	
	public function get_description(){
		preg_match("/<meta.*name=[\'\"]description[\'\"][^>]*>/i",$this->content,$a);
		if(empty($a)) return false;
		$b=$a[0];
		preg_match("/content=[\'\"]([^'\"]*)/i",$b,$a);
		if(isset($a[1])) return $a[1];
		return false;
	}
	
	public function parseurl(){
		$arr=parse_url($this->url);
		$this->host=$arr['host'];
		$this->scheme=$arr['scheme'];
		preg_match("/([\w]+\.(com|cn|net|org|cc|info|me|co|tv|name|tel|so)(\.cn)?)/i",$this->host,$c);
		$this->domain=$c[1];
		$this->dir=dirname($this->url); 
	}
	public function parsedomain($url){
		$d=array(
			".com",
			".cn",
			".com.cn",
			".net"
		);
	}
	public function builtlink($url){
		if(empty($url)) return "";
		if($this->selfsite==1){
			$a=parse_url($url);
			if($a['host'] && $a['host']!=$this->host) return "";
		}elseif($this->selfsite==0){
			$a=parse_url($url);
			//如果不是主域名
			if($a['host'] && strpos($url,$this->domain)===false) return "";
		}
		if($url{0}=="/"){
			return $this->scheme."://".$this->host.$url;
		}elseif(preg_match("/^http/i",$url)){
			return $url;
		}elseif(preg_match("/^javascript/i",$url)){
			return "";
		}elseif($url{0}=="#"){
			return "";
		}else{
			return $this->scheme."://".$this->host."/".$this->dir."/".$url;
		}
	}
	public function curl_get_contents($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch,CURLOPT_TIMEOUT,30);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$content=curl_exec($ch);
		curl_close($ch);
		return $content;
	}
	public function toutf8($str){
		$ico=array("gbk","utf-8","'ASCII'");
		foreach($ico as $c){
			if($str===iconv("utf-8","$c//IGNORE",iconv($c,"utf-8//IGNORE",$str))){
				if($c=='utf-8'){
				return $str;
				}
				return iconv($c,"utf-8",$str);
			}
		}
		return $str; 
	}
}
?>