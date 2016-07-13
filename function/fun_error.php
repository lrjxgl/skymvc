<?php
function showError($sql='')
{
     
	$array=debug_backtrace();
	unset($array[0]);
	echo $str='<style>
		.debug-list{background-color:rgb(240, 226, 126); padding:20px 40px; }
		.debug-list .item{margin-bottom:10px; display:block; border-bottom:1px solid #ccc; line-height:25px; padding:0px 5px;}
	</style>';
 	if($sql){
		$html="<div class='debug-list'><h1  style='color:red'>SQL错误提示</h1><div class='item'>系统：PHP " . PHP_VERSION . " (" . PHP_OS . ")</div><div class='item'>".$sql."</div>\n"; 
	}else{
		$html="<div class='debug-list'><h1  style='color:red'>错误提示</h1><div class='item'>系统：PHP " . PHP_VERSION . " (" . PHP_OS . ")</div>\n";
	}
	foreach($array as $row)
    {
       $html .="<div class='item'>".$row['file'].':'.$row['line'].'行,调用方法:'.$row['function']." </div>";
    }
	$html.='<div class="item">请联系管理员</div></div>';
	$html=str_replace(TABLEPRE,"",$html);
	 echo $html;
    /* Don't execute PHP internal error handler */
   return false;
}
function my_error_handler($errno=NULL, $errstr=NULL, $errfile=NULL, $errline=NULL ){
	if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
	
	echo $str='<style>
		.debug-list{background-color:rgb(240, 226, 126); padding:20px 40px; }
		.debug-list .item{margin-bottom:10px; display:block; border-bottom:1px solid #ccc; line-height:25px; padding:0px 5px;}
	</style>';
	
	$html="<div class='debug-list'><h1  style='color:red'>错误提示</h1><div class='item'>系统：PHP " . PHP_VERSION . " (" . PHP_OS . ")</div>\n";
    skyLog("error.txt","<div class='item'>错误类型：WARNING<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>");
    switch ($errno) {
     
	
    case E_USER_WARNING:
       $html.="<div class='item'>错误类型：USER_WARNING<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break;
	case E_USER_NOTICE:
        $html.="<div class='item'>错误类型：USER_NOTICE<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break;
		
	case E_WARNING:
       $html.="<div class='item'>错误类型：WARNING<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break;

   case E_NOTICE:
       $html.="<div class='item'>错误类型：NOTICE <br>内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break; 

    default:
        $html.="<div class='item'>错误类型：未知<br> 内容：$errstr  错误位置：$errfile 行：$errline </div>\n";
        break;
    }
 
	$array=debug_backtrace();
	unset($array[0]);
 
 
	
	foreach($array as $row)
    {
       $html .="<div class='item'>".$row['file'].':'.$row['line'].'行,调用方法:'.$row['function']." </div>";
    }
	$html.='<div class="item">请联系管理员</div></div>';
	 echo $html;
	 exit();
}
set_error_handler("my_error_handler");

?>