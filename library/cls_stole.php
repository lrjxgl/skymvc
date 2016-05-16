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
	public function __construct(){
		
	}
	public function getContent($url){
		$url=str_replace("&amp;","&",$url);
		$url=str_replace("/../","/",$url);
		$this->dir=dirname($url)."/";
		$a=parse_url($url);
		$this->domain=$a['scheme']."://".$a['host']."/";	
		$this->content=$this->curl_get_contents($url);
		//替换链接位置
		
		$this->content=$this->replace_src();
		//替换图片
		$this->content=preg_replace("/<input([^>]*)type=\"image\"([^>]*)>/i","<img \\1 \\2>",$this->content);
		$this->charset=$this->getCharset();
	}
	
	function curl_get_contents($url){
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)"); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$c=curl_exec($ch);
		curl_close($ch);
		return $c;
	}
	public function replace_src($content=""){
		$content=$content?$content:$this->content;
		preg_match_all("/src=['\"](.*)['\"]/iUs",$content,$arr);
		if($arr && isset($arr[1])){
			foreach($arr[1] as $v){
				if(substr($v,0,1)=="/"){
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
		if(!defined("CHARSET")){
			define("CHARSET","utf-8");
		}
		$content=$content?$content:$this->content;
		preg_match("/<meta[^>]*(gbk|utf-8|gb2312|big5)[^>]*[^>]>/is",$content,$a);
		if(isset($a[1]) && !empty($a[1])){
			return strtolower($a[1]);
		}else{
			if($content==iconv(CHARSET,"gbk",iconv("gbk",CHARSET,$content))){
				return "gbk";
			}
			
			if($content==iconv(CHARSET,"gb2312",iconv("gb2312",CHARSET,$content))){
				return "gb2312";
			}
			
			if($content==iconv(CHARSET,"utf-8",iconv("utf-8",CHARSET,$content))){
				return "utf-8";
			}
			
			return CHARSET;
			
		}
		
	}
	
	/**
	*正则匹配一条
	*/
	public function preg_one($preg,$content=""){
		$content=$content?$content:$this->content;
		$preg=$this->getPreg($preg); 
		preg_match("/$preg/is",$content,$a);
		if(isset($a[1])){
			return $a[1];
		}else{
			return false;
		}
	}
	/*获取匹配数据*/
	public function preg_data($preg,$content=""){
		$content=$content?$content:$this->content;
		$tags=$this->getTag($preg);
		$preg=$this->getPreg($preg);
		preg_match("/$preg/iUs",$content,$a);
		$arr=array();
		if($tags){
			foreach($tags as $k=>$v){
				$arr[$v]=isset($a[$k+1])?$a[$k+1]:array();
			}
		}
		return $arr;
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
		
		//替换标
		$preg=preg_replace("/{title=([^}]*)}/iUs","\\1",$preg);
		$preg=str_replace("{title}",".*",$preg);
		
		//替换url
		$preg=preg_replace("/{url=([^}]*)}/iUs","\\1",$preg);
		$preg=str_replace("{url}",".*",$preg);
		//替换时间
		$preg=preg_replace("/{time=([^}]*)}/iUs","\\1",$preg);
		$preg=str_replace("{time}",".*",$preg);
		//替换作者
		$preg=preg_replace("/{author=([^}]*)}/iUs","\\1",$preg);
		$preg=str_replace("{author}",".*",$preg);
		//内容
		$preg=preg_replace("/{content=([^}]*)}/iUs","\\1",$preg);
		$preg=str_replace("{content}",".*",$preg);
		//简介
		$preg=preg_replace("/{description=([^}]*)}/iUs","\\1",$preg);
		$preg=str_replace("{description}",".*",$preg);
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