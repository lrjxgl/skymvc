<?php
/**
*url重写
*TYPE 
*	1 /m/a/b-1.html
*	2 index.php/m/a/b-1/c-2
*/
function R($url){
	if(!defined("REWRITE_ON") or !REWRITE_ON) return $url;
	return rewrite($url);
}
function rewrite($url){
	if(!defined("REWRITE_TYPE")){
		define("REWRITE_TYPE","");
	}
	if(empty($url)) return $url;
	$str="";
	$url=str_replace("&amp;","&",$url);
	$s=parse_url($url);
	$query=$s['query'];
	parse_str($query,$data);
	switch(REWRITE_TYPE){
		case "pathinfo":
				if(empty($data)) return $s['path'];
				$data['m']=isset($data['m'])?$data['m']:"index";
				$data['a']=isset($data['a'])?$data['a']:"default";
				$str=$s['path']."/".$data['m']."/".$data['a'];
				unset($data['m']);
				unset($data['a']);
				if(!empty($data)){
					foreach($data as $k=>$v){
						 $str.="/$k-$v";
					}
				}
				return $str;		
			break;
		case "rewrite":
				if(empty($data)) return "/index.html";
				$data['m']=isset($data['m'])?$data['m']:"index";
				$data['a']=isset($data['a'])?$data['a']:"default";
				$str="/".$data['m']."/".$data['a'];
				unset($data['m']);
				unset($data['a']);
				if(!empty($data)){
					$i=0;
					foreach($data as $k=>$v){
						if($i==0){
							$dot="/";
						}else{
							$dot="-";
						}
						 $str.=$dot."$k-$v";
						 $i++;
					}
				}
				$str.=".html";
				return $str;
			break;
		default:
				return $url;
			break;
		
	}
	
}
/**
*解析pathinfo 路径
*/
function url_get($url,$appindex="index.php|admin.php|module.php|app.php|shop.php"){
	if(preg_match("/(".$appindex.")\//i",$url)){
		$query=preg_replace("/.*(".$appindex.")/i","",$url);
		$basename=str_replace($query,"",$url);
		$para=explode("?",$query);
		$data=explode("/",$para[0]);
		unset($data[0]);
		if(isset($data[1])){
			$_GET['m']=$data[1];
			unset($data[1]);
		}
	 
		if(isset($data[2])){
			$_GET['a']=$data[2];
			unset($data[2]);
		}
		if(!empty($data)){
			foreach($data as $v){
				$c=explode("-",$v);
				if(isset($c[1])){
					$_GET[$c[0]]=urldecode($c[1]);
				}
			}
		}
		if(isset($para[1])){
			parse_str($para[1],$t_a);
			if($t_a){
				foreach($t_a as $k=>$v){
					$_GET[$k]=$v;
				}
			}
		}

	} 
	 
}

function parseStrLink($str){
	if(empty($str)) return false;
	$arr=str2arr($str);
	if(!isset($arr["m"])){
		return false;
	}
	if(substr($arr["m"],0,4)=="mod_"){
		$arr["path"]="/module.php";
		$arr["m"]=str_replace("mod_","",$arr["m"]);
	}
	$url=$arr['path']."?m=".$arr['m']."&a=".$arr['a']."&".$arr['param'];
	return $url;
}

?>