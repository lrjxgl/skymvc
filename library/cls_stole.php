<?php
/*
$this->loadClass("stole",false,false);
$st= new stole();
$st->getContent("http://www.skymvc.com/");
$st->cutHtml('<ul class="data-list">');
$arr=$st->preg_all('<a class=\"aurl\" href=\"({url=.*})\">');
print_r($arr);
 
*/
class stole{
	public $content;
	public $charset;
	public $domain;
	public $dir;
	public $iswap=0;
	public $isCurl=true;
	public function __construct(){
		
	}
	public function getContent($url,$proxy=""){
		$url=str_replace("&amp;","&",$url);
		$url=str_replace("/../","/",$url);
		$url=str_replace("////","//",$url);
		$this->dir=dirname($url)."/";
		$a=parse_url($url);
		$this->domain=$a['scheme']."://".$a['host']."/";
		 
		if($proxy==""){
			if($this->isCurl){
				 
				$this->content=$this->curl_get_contents($url,$proxy);
			}else{
				$this->content=file_get_contents($url);
			}
		}else{
			 
			 $this->content=$this->curl_get_contents($url,$proxy);
		}
		
		if(empty($this->content)) return false;
		//替换链接位置
		 
		$this->content=$this->replace_src();
		//替换图片
		$this->content=preg_replace("/<input([^>]*)type=\"image\"([^>]*)>/i","<img \\1 \\2>",$this->content);
		
		$this->charset=$this->getCharset();
	 
		if($this->charset!="utf-8"){
			
			$this->content=mb_convert_encoding($this->content,"utf-8",$this->charset); 
		}
		
		 
	}
	
	function curl_get_contents($url,$proxy=""){
		$ch=curl_init();
		if(!empty($proxy)){
			$p=explode(":",$proxy);
			curl_setopt ($ch, CURLOPT_PROXY, $p[0]);
			$port=isset($p[1])?html($p[1]):"80";
			curl_setopt($ch, CURLOPT_PROXYPORT,$port);  
		}
		//$cookie = tempnam('./temp/cookies','cookie');
		//curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 2);  
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
		if($this->iswap){
			curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1"); 
		}else{
			curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36"); 
		}
		
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$c=curl_exec($ch);
		curl_close($ch);
		return $c;
	}
	public function replace_src($content=""){
		$content=$content?$content:$this->content;
		preg_match_all("/src=['\"](.*)['\"]/iUs",$content,$arr);
		$ins=array();
		if($arr && isset($arr[1])){
			foreach($arr[1] as $v){
				if(in_array($v,$ins)) continue;
				$ins[]=$v;
				if(substr($v,0,2)=="//"){
					$http=substr($this->domain,0,5)=="https"?"https:":"http:";
					$content=str_replace($v,$http.$v,$content);
				}elseif(substr($v,0,1)=="/"){
					$content=str_replace($v,$this->domain.$v,$content);
				}elseif(substr($v,0,5)=="http:" or substr($v,0,5)=="https"){
					
				}else{
					$content=str_replace($v,$this->dir.$v,$content);
				}
				
			}
		}
		return $content;
	}
	
	public function getCharset($content=""){
	 
		$content=$content?$content:$this->content;
		preg_match("/<meta[^>]*(gbk|utf-8|gb2312|big5)[^>]*[^>]>/is",$content,$a);
		if(isset($a[1]) && !empty($a[1])){
			return strtolower($a[1]);
		}else{
			$icon=mb_convert_encoding($content,"utf-8","utf-8");
			if($icon==$content){
				return "utf-8";
			}else{
				return "gbk";
			}
			
			
			
		}
		
	}
	
	/**
	*正则匹配一条
	*/
	public function preg_one($preg,$content=""){
		$content=$content?$content:$this->content;
		$preg=$this->getPreg($preg); 
		preg_match("/$preg/iUs",$content,$a);
		if(isset($a[1])){
			return $a[1];
		}else{
			return false;
		}
	}
	 
	/*
	*正则多条匹配
	*/
	public function preg_all($preg,$content=""){
		$content=$content?$content:$this->content;
		$tags=$this->getTag($preg);
		$preg=$this->getPreg($preg); 
		preg_match_all("/$preg/iUs",$content,$a);
		 
		if($tags){
			foreach($tags as $k=>$v){
				$arr[$v]=isset($a[$k+1])?$a[$k+1]:array();
			}
		}
		return $arr;
	}
	/*
	*过滤正则
	*/
	public function getPreg($preg){
		$preg=str_replace("/","\/",$preg);
		$preg=preg_replace("/{[\w]+=[^}]*}/iUs",".*",$preg);
		return $preg;
		
	}
	/*获取解析标签*/
	public function getTag($preg){
		preg_match_all("/\({(.*)}\)/iUs",$preg,$a);
		if(isset($a[1])){
			foreach($a[1] as $v){
				$b=explode("=",$v);
				$data[]=$b[0];
			}
			return $data;
		}
	}
	/**
	*根据html标签来截取内容
	*/
	public function cutHtml($start,$content="",$all=0){
		$content=$content?$content:$this->content;
		$d=explode($start,$content);
		preg_match("/<(\w+)/is",$start,$h);
		$s_html="<{$h[1]}";
		$e_html="</{$h[1]}>";
		 
		$earr=explode($e_html,$d[1]);//截止
		$html=$temp=$earr[0];
		foreach($earr as $k=>$v){  
			if(strpos($temp,$s_html)!==false){  
				$temp=preg_replace("/{$s_html}/iUs","",$temp,1);
				$temp .=$earr[$k+1];
				if($k>0){
					$html.=$e_html.$v;
				}
			}else{
				if($k>0){
					$html.=$e_html.$v;
				}
				break;
			}
		}
		//获取本身
		if($all){
			$html=$start.$html.$e_html;
		}else{
			if(strpos($start,">")===false){
				$html=preg_replace("/[^>]*>/iUs","",$html,1);
			}
		}
		$this->content=$html;
		return $html;
	}
	/*
	*移除html
	*/
	public function removeHtml($start,$content=''){
		$content=$content?$content:$this->content;
		$a=explode($start,$content);
		foreach($a as $v){
			$html=$this->cutHtml($start,$content,1);
			$this->content=$content=str_replace($html,"",$content);	
		}
		return $content;
	}
	
	public function removePreg($preg,$content='',$t=1){
		$content=$content?$content:$this->content;
		$preg=$this->getPreg($preg);
		$html=preg_replace("/$preg/iUs","",$content,$t);
		$this->content=$html;
		return $html;
	}
	
	public function remote_img($dir="",$content=''){
		$content=$content?$content:$this->content;
		preg_match_all("/<img.*src=[\'\"]+(.*)[\'\"][^>]*>/iUs",$content,$arr);
		$pics=$arr[1];
		
		if(empty($pics)) return $content;
		$dir=$dir?$dir:"attach/content/".date("Y/m/");
		umkdir($dir);
		foreach($pics as $k=>$pic)
		{
			$file=$dir.md5(time().$pic).".jpg";
			file_put_contents($file,$this->curl_get_contents($pic));
			$content=str_replace($pic,"/".$file,$content);
		}
		return $content;
	}
	
}
?>