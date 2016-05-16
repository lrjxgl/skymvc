<?php
header("Content-type:text/html;charset=utf-8;");
if(!file_exists('install.lock'))
{
	umkdir("../config");//生成配置文件夹
	umkdir("../source/admin");//建立后台控制文件
	umkdir("../source/hook");
	umkdir("../source/model");//建立后台模型文件
	umkdir("../source/index");//建立前台控制文件
	umkdir("../attach");//建立附件目录
	umkdir("../themes/index");//模板目录
	umkdir("../themes/admin");//模板目录
	umkdir("../themes/wap");//手机目录
	umkdir("../static");//静态文件
	umkdir("../static/css");//css静态文件
	umkdir("../static/js");//js静态文件
	umkdir("../static/images");//静态文件
	umkdir("../plugin");//插件目录
	umkdir("../temp/compiled");//模版编译目录
	umkdir("../temp/caches");//缓存目录
	umkdir("../temp/html");//静态文件目录
	umkdir("../temp/log");//静态文件目录
	umkdir("../lang/chinese");//语言包
	
	//生成配置文件
 
	$str=' 
 
define("DOMAIN","skymvc.com");
define("WAP_DOMAIN",""); 
define("MYSQL_CHARSET","utf8");
define("TABLE_PRE","sky_");
/*
$dbconfig["master"]=array(
	"host"=>"127.0.0.1","user"=>"root","pwd"=>"123","database"=>"xyo2o"
);
 */
/**其他分表库**/
/*
$dbconfig["user"]=array(
	"host"=>"localhost","user"=>"root","pwd"=>"123","database"=>"skyshop"
);



$dbconfig["article"]=array(
	"host"=>"localhost","user"=>"root","pwd"=>"123","database"=>"skycms"
);
*/ 

/*分库配置*/
/* 
$VMDBS=array(
	"article"=>"article",
	"forum"=>"article"
);
*/ 
*/
/*缓存配置*/
$cacheconfig=array(
	"file"=>true,
	"php"=>true,
	"mysql"=>false,
	"memcache"=>false
);
/*Session配置 1为自定义 0为系统默认*/
define("SESSION_USER",0);
define("REWRITE_ON",0); 
define("REWRITE_TYPE","pathinfo");
define("TESTMODEL",1);//开发测试模式
define("HOOK_AUTO",false);//开放全局hook

 
 ';
	file_put_contents("../config/config.php","<?php\r\n{$str}\r\n?>");
//生成hook配置
$str='<?php
$config["hook"]=array(
	"index_default"=>array(
		"test"=>"run",
	),

);
?>';
file_put_contents("../config/hook.php",$str);
//生成cache配置文件
$str='<?php
$config[\'cache\']=array(	
	\'cache_type\'=>"file",
	"cache_dir"=>"temp/filecache",
	/*
	\'memcache\'=>array(
		"host"=>"localhost",
		"port"=>"11211",
	),
	"mysql"=>array(
	
	),
	*/
);

?>';
file_put_contents("../config/cache.php",$str);
//生成常数文件
$str='<?php
define("STATIC_SITE","http://".$_SERVER[\'HTTP_HOST\']."/");
define("IMAGE_SITE","http://".$_SERVER[\'HTTP_HOST\']."/");
define("APPINDEX","/index.php");
define("APPADMIN","/admin.php");
define("APPMODULE","/module.php");
define("OB_GZIP",false);
//模板
define("SKINS","index");
//模板
define("WAPSKINS","wap");
define("WAP_DOMAIN","wap.com");
?>';
file_put_contents("../config/const.php",$str);
//生成缩略图配置文件
$str='<?php
$config[\'thumb\']=array(
	array("w"=>100,"h"=>100,"all"=>1),
	array("w"=>220,"h"=>999,"all"=>0),
	array("w"=>400,"h"=>999,"all"=>0)
);
?>';
file_put_contents("../config/image.php",$str);
//生成数据表配置文件
$str='<?php 
	/*
	*表相关的配置 不能修改
	*/
?>';
file_put_contents("../config/table.php",$str);
//生成应用版本
$str='<?php
define("VERSION","SKYMVC1");
define("VERSION_NUM",1); 
define("ONLINEUPDATE","http://'.$_SERVER['HTTP_HOST'].'/onlineupdate/");
?>';
file_put_contents("../config/version.php",$str);


	//生成首页
	$str='<?php
error_reporting(E_ALL ^ E_NOTICE);
header("Content-type:text/html; charset=utf-8");
if(ini_get("register_globals"))
{
	die("请关闭全局变量");
}
 
require("config/config.php");
require("config/const.php");
define("ROOT_PATH",  str_replace("\\\\", "/", dirname(__FILE__))."/");
define("CONTROL_DIR","source/index");
define("MODEL_DIR","source/model");
define("HOOK_DIR","source/hook");
/*视图模版配置*/
$cache_dir="";//模版缓存文件夹
$template_dir="themes/".SKINS;//模版风格文件夹
$wap_template_dir="themes/".WAPSKINS;
$compiled_dir="";//模版编译文件夹
$html_dir="";//生成静态文件夹
$rewrite_on=REWRITE_ON;//是否开启伪静态 0不开 1开启
$smarty_caching=true;//是否开启缓存
$smarty_cache_lifetime=3600;//缓存时间
require("./skymvc/skymvc.php");
//用户自定义初始化函数
function userinit(&$base){
	$base->loadConfig("table");
	$base->smarty->assign("skins","/skins/index/");
	$base->smarty->assign("appindex",APPINDEX);
	$base->smarty->assign("appadmin",APPADMIN);
}

?>';
	file_put_contents("../index.php",$str);
	//生成admin首页
		$str='<?php
error_reporting(E_ALL ^ E_NOTICE);
header("Content-type:text/html; charset=utf-8");
if(ini_get("register_globals"))
{
	die("请关闭全局变量");
}
 
require("config/config.php");
require("config/const.php");
define("ROOT_PATH",  str_replace("\\\\", "/", dirname(__FILE__))."/");
define("CONTROL_DIR","source/index");
define("MODEL_DIR","source/model");
define("HOOK_DIR","source/hook");
/*视图模版配置*/
$cache_dir="";//模版缓存文件夹
$template_dir="themes/admin";//模版风格文件夹
$compiled_dir="";//模版编译文件夹
$html_dir="";//生成静态文件夹
$rewrite_on=REWRITE_ON;//是否开启伪静态 0不开 1开启
$smarty_caching=true;//是否开启缓存
$smarty_cache_lifetime=3600;//缓存时间
require("./skymvc/skymvc.php");
//用户自定义初始化函数
function userinit(&$base){
	$base->loadConfig("table");
	$base->smarty->assign("skins","/skins/admin/");
	$base->smarty->assign("appindex",APPINDEX);
	$base->smarty->assign("appadmin",APPADMIN); 
}

?>';
	file_put_contents("../admin.php",$str);


//控制文件admin/ctrl/index.ctrl.php
$str='<?php
class indexControl extends skymvc
{
	function __construct()
	{
		parent::__construct();//父类厨师话
		$this->loadModel("index");
	}

	public function onDefault()
	{
		if(ISWAP){
			$this->smarty->assign("welcome","这是手机版哦，欢迎使用skymvc，让我们共同努力！");
		}else{
			$this->smarty->assign("welcome","欢迎使用<a href=\"http://www.skymvc.com\" target=\"_blank\">skymvc</a>，让我们共同努力！");
		}
		$this->smarty->assign("who",$this->index->test());
		$this->smarty->display("index.html");
	}
}

?>';
file_put_contents("../source/index/index.ctrl.php",$str);
file_put_contents("../source/admin/index.ctrl.php",$str);
//hook文件
$str='<?php
class testHook extends skymvc {
	
	public function __construct(){
		parent::__construct();
	}
	
	public function run($data=array()){
		$data=$this->db->select("article",array("start"=>0,"limit"=>10));
		
	}
}
?>';
file_put_contents("../source/hook/test.hook.php",$str);
//模型文件  index.model.php
$str='<?php
class indexModel extends model
{
	public $base;
	function __construct(&$base)
	{
		parent::__construct($base);
		$this->base=$base;
	}

	function test()
	{
		$arr=array(
		"我是谁",
		"我是一只飞翔的鸟",
		"我盘旋在无际的天边",
		"观赏着美丽的大地"
		);
		return $arr;
	}
	
}

?>';
file_put_contents("../source/model/index.model.php",$str);
//生成模板文件 index.html
$str='<html>
<body>
<div style="width:600px; text-align:center; margin: 0 auto; background-color:#C4E6A2; margin-top:100px; height:400px; line-height:40px; ">
<h3 style="height:80px; line-height:80px;">{$welcome}</h3>

{foreach item=w from=$who}
{$w}<br>
{/foreach}


</div>

</body>

</html>';
file_put_contents("../themes/index/index.html",$str);
file_put_contents("../themes/admin/index.html",$str);
file_put_contents("../themes/wap/index.html",$str);
//生成跳转文件
$str='{include file=\'header.html\'}
<script language="javascript">
function movenew()
{
	document.location=\'{$url}\';
}
setTimeout(movenew,2000);

</script>
<div class="well">
{$message}，如果没有自动跳转请点击 <a href="{$url}">跳转</a>

</div> 

{include file=\'footer.html\'}';
file_put_contents("../themes/admin/gomsg.html",$str);
file_put_contents("../themes/index/gomsg.html",$str);
file_put_contents("../themes/wap/gomsg.html",$str);
	
	file_put_contents("install.lock","");
file_put_contents(".htaccess",'<FilesMatch "\.(bak|inc|lib|sh|tpl|lbi|dwt)$">
    order deny,allow
    deny from all
</FilesMatch>

RewriteEngine On
RewriteBase /
rewritecond %{request_filename} !-f
# direct one-word access
RewriteRule ^index\.html$    index\.php [L]
# access any object by its numeric identifier
#m/a/id-1-c-2-d-3-e-4.html
RewriteRule ^(\w+)/(\w+)/(\w+)-(\w+)-(\w+)-(\w+)-(\w+)-(\w+)-(\w+)-(\w+)\.html$  index.php?m=$1&a=$2&$3=$4&$5=$6&$7=$8&$9=$10 [t]
#m/a/id-1-c-2-d-3.html
RewriteRule ^(\w+)/(\w+)/(\w+)-(\w+)-(\w+)-(\w+)-(\w+)-(\w+)\.html$    index.php?m=$1&a=$2&$3=$4&$5=$6&$7=$8 [t]
#m/a/id-1-c-2.html
RewriteRule ^(\w+)/(\w+)/(\w+)-(\w+)-(\w+)-(\w+)\.html$    index.php?m=$1&a=$2&$3=$4&$5=$6 [t]
#m/a/id-1.html
RewriteRule ^(\w+)/(\w+)/(\w+)-(\w+)\.html$    index.php?m=$1&a=$2&$3=$4 [t]
#m a
RewriteRule ^(\w+)/(\w+)\.html$    index.php?m=$1&a=$2 [t]
#m首页
RewriteRule ^(\w+)\.html$    index.php?m=$1 [t]
');	
	echo "欢迎使用skymvc,<a href='../index.php'>开始使用</a>";
	
}else
{
	echo "欢迎私用skymvc,如果你要重新安装框架，请删除框架目录下的install.lock";
}

function umkdir($dir)
{
	$arr=explode("/",$dir);
	foreach($arr as $key=>$val)
	{
		$d="";
		for($i=0;$i<=$key;$i++)
		{
			$d.=$arr[$i]."/";
		}
		if(!file_exists($d) && (strpos($val,":")==false))
		{
			mkdir($d,0777);
			file_put_contents($d."/index.html","die access ");
		}
	}
}
?>