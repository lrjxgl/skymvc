<?php
$files=array(
	"function/xss.php",
	"function/function.php",
	"function/fun_error.php",
	"function/fun_file.php",
	"function/fun_gps.php",
	"function/fun_url.php",
	"class/cls_cache.php",
	"class/cls_pdo.php",
	"class/cls_model.php",
	"class/cls_session.php",
	"class/cls_smarty.php",
		
);
$str="";
foreach($files as $f){
	$str.=file_get_contents($f);
}
file_put_contents("class_fun_all.php",$str);
?>