<?php
/*
	@微信公众号助手
	@Author 雷日锦 
	2014-06-18
*/
/*
date_default_timezone_set('PRC');  //设置默认时区
set_time_limit(0);
if(!defined("ROOT_PATH")){
	define("ROOT_PATH",dirname(dirname(dirname(__FILE__)))."/");
}
$wx=new weixinapi();

if(!$wx->login("username","password")){
	exit("登陆失败");
}

$data=array(
			0=>array(
				"title"=>"这不是不是是测试".date("Y-m-d H:i:s").rand(),
				"content"=>"测试".date("Y-m-d H:i:s").rand(),
				"digest"=>"描述",
				"author"=>"作者",
				"img"=>"@".realpath(ROOT_PATH."attach/product/0/0/65/44/6544/1402960179b55aad51cdae2bc60b7fa88de8d931a2.jpg"),
				"show_cover_pic"=>1,
				"sourceurl"=>"原文链接"
			),
			1=>array(
				"title"=>"测试".date("Y-m-d H:i:s").rand(),
				"content"=>"测试".date("Y-m-d H:i:s").rand(),
				"digest"=>"描述",
				"author"=>"作者",
				"img"=>"@".realpath(ROOT_PATH."attach/product/0/0/65/44/6544/1402960179b55aad51cdae2bc60b7fa88de8d931a2.jpg"),
				"show_cover_pic"=>1,
				"sourceurl"=>"原文链接"
			),
		);
$wx->sc_post($data,200306521);
*/
class weixinapi{
	
	public $token=NULL;
	public $ticket=NULL;
	public $ticket_id=NULL;
	public $domain="https://mp.weixin.qq.com";
	public function __construct(){
		
	}
	
	function curl_post($url, $data)
	{
		if($this->token){
			if(is_array($data)){
				$data['token']=$this->token;
			}else{
				$data.="&token=".$this->token;
			}
		}
		$cookie_jar =str_replace("/","\\",dirname(__FILE__)."/cookietest.txt") ;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_POST, TRUE); 
		curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)"); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt ($ch,CURLOPT_REFERER,'https://mp.weixin.qq.com');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}
	
	function curl_get_contents($url){
		if($this->token){
			$url.="&token=".$this->token;
		}
		$cookie_jar =str_replace("/","\\",dirname(__FILE__)."/cookietest.txt") ;
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt ($ch,CURLOPT_REFERER,'https://mp.weixin.qq.com');
		curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)"); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
		
		
		$c=curl_exec($ch);
		curl_close($ch);
		return $c;
	}
	
	public function login($username,$pwd){
		$wx="username=".$username."&pwd=".md5($pwd)."&f=json&imgcode=";
		$c=$this->curl_post("https://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN",$wx);
		$data=json_decode($c,true);
		preg_match("/token=(\w+)/i",$data['redirect_url'],$tok);
		if($tok){
			$this->token=$tok[1];
			return true;
		}else{
			return false;
		}
	}
	
	public function userlist($page=0,$pagesize=1000){
		$url=$this->domain."/cgi-bin/contactmanage?t=user/index&pagesize=".$pagesize."&pageidx=".$page."&type=0&groupid=0&lang=zh_CN";
		$c=$this->curl_get_contents($url);
		preg_match("/friendsList : \((.*)\)\.contacts,/iUs",$c,$a);
		if($a){
			$d=json_decode($a[1],true);
			return $d['contacts'];
		}else{
			return false;
		}
	}
	
	public function sendPm($uid,$content){
		$data="type=1&content=".$content."&tofakeid=".$uid."&imgcode=&lang=zh_CN&random=".rand()."&f=json&ajax=1&t=ajax-response";
		return $this->curl_post($this->domain."/cgi-bin/singlesend",$data);		
	}
	/*素材列表*/
	public function sc_list($page=0,$pagesize=10){
		$url=$this->domain."/cgi-bin/appmsg?begin=".$page."&count=".$pagesize."&t=media/appmsg_list&type=10&action=list&lang=zh_CN";
		$c=$this->curl_get_contents($url);
		preg_match("/wx.cgiData = (.*);[^;]*wx.cgiData.count/iUs",$c,$a);
		$data=json_decode($a[1],true);
		return $data;
	}
	/*
	$data='AppMsgId=&count=2&title0=%E6%B5%8B%E8%AF%95123&content0=%3Cp%3E%E6%8C%89%E6%97%B6%E5%A4%A7%E5%A4%A7%3C%2Fp%3E&digest0=%E6%8C%89%E6%97%B6%E5%A4%A7%E5%A4%A7&author0=%E6%B5%8B%E8%AF%952&fileid0=200306107&show_cover_pic0=1&sourceurl0=&title1=%E6%B5%8B%E8%AF%953&content1=%3Cp%3E%E6%B5%8B%E8%AF%953%3C%2Fp%3E&digest1=%E6%B5%8B%E8%AF%953&author1=&fileid1=200306111&show_cover_pic1=1&sourceurl1=&vid=&ajax=1&token=122318833&lang=zh_CN&random=0.25288362335413694&f=json&t=ajax-response&sub=create&type=10';
		素材发布
		格式：
		array(
			0=>array(
				"title"=>"测试",
				"content"=>"测试",
				"digest"=>"描述",
				"author"=>"作者",
				"img"=>"图片",
				"show_cover_pic"=>1,
				"sourceurl"=>"原文链接"
			),
			1=>array(
				"title"=>"测试",
				"content"=>"测试",
				"digest"=>"描述",
				"author"=>"作者",
				"img"=>"图片",
				"show_cover_pic"=>1,
				"sourceurl"=>"原文链接"
			),
		)
	*/
	public function sc_post($data,$id=""){
		if(empty($data)) return false;
		$postdata['AppMsgId']=$id;
		$postdata['count']=count($data);
		$postdata['ajax']=1;
		$postdata['vid']="";
		$postdata['token']=$this->token;
		$posdata['lang']="zh_CN";
		$postdata['random']=rand();
		$postdata["f"]="json";
		$postdata['t']="ajax-response";
		if($id){
			$postdata['sub']="update";
		}else{
			$postdata['sub']="create";
		}
		$postdata['type']=10;
		foreach($data as $k=>$v){
			$postdata['title'.$k]=$v['title'];
			$postdata['content'.$k]=$v['content'];
			$postdata['digest'.$k]=$v['digest'];
			$postdata['author'.$k]=$v['author'];
			$postdata['fileid'.$k]=$this->sc_upload($v['img']);
			$postdata['show_cover_pic']=$v['show_cover_pic'];
			$postdata['sourceurl']=$v['sourceurl'];
		}
		
		echo $c=$this->curl_post($this->domain."/cgi-bin/operate_appmsg",$postdata);
		$res=json_decode($c,true);
		if($res && $res['msg']=='OK'){
			return true;
		}else{
			return false;
		}
	}
	/*获取ticket_id*/
	public function sc_ticked_id(){
		$url="https://mp.weixin.qq.com/cgi-bin/appmsg?t=media/appmsg_edit&action=edit&type=10&isMul=1&isNew=1&lang=zh_CN";
		$c=$this->curl_get_contents($url);
		preg_match("/ticket:\"(.*)\",/iUs",$c,$a);
		if($a && isset($a[1])){
			$this->ticket=$a[1];
		}else{
			return false;
		}
		preg_match("/user_name:\"(.*)\",/iUs",$c,$a);
		if($a && isset($a[1])){
			$this->ticket_id=$a[1];
		}else{
			return false;
		}
	}
	
	/*sc文件上传*/
	public function sc_upload($file){
		if(!$this->ticket) $this->sc_ticked_id();
		$data=array (
			"folder" => "/cgi-bin/uploads",
			"Filename"=>basename($file),
			// 要上传的本地文件地址
			"file" => $file
		);
		$url="https://mp.weixin.qq.com/cgi-bin/filetransfer?action=upload_material&f=json&ticket_id=".$this->ticket_id."&ticket=".$this->ticket."&token=".$this->token."&lang=zh_CN";
	 	$res=$this->curl_post($url,$data);
		$res=json_decode($res,1);
		if($res['base_resp']['err_msg']=='ok') return $res['content'];
		return 200306463; 
	}
	
	
}

?>