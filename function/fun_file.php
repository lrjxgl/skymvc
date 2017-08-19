<?php
if(!defined("ROOT_PATH")){
	define("ROOT_PATH",dirname(str_replace("\\", "/", dirname(__FILE__)))."/");
}

function getMime($url=""){
	$isremote=false;
	if(!file_exists($url)){
		$d=get_headers($url);
		if(empty($d)) return false;		
		if(!$d || preg_match("/200/i",$d[0])==false){
			if(preg_match("/Location:/i",$d[2])!=false){
				$url=trim(str_replace("Location:","",$d[2]));
			}else{
				return false;
			}
		}
		$isremote=true;		
	}
	if(function_exists("finfo_open") && !$isremote){
		$finfo    = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $url);
		finfo_close($finfo);
		return $mimetype;
	}
	
	if(function_exists("mime_content_type") && !$isremote){
		return mime_content_type($url);
	}
	
	$fh = @fopen($url, 'rb');
	$bin = fread($fh, 2); //读2字节
	fclose($fh);
	$data = unpack('C2chr', $bin);
	$strInfo = @unpack("C2chars", $bin);
	$typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
	$fileType = '';
	switch ($typeCode) {
		case 7784: $fileType = 'audio/midi'; break;//midi
		case 8297: $fileType = 'application/x-rar-compressed'; break;//rar
		case 255216: $fileType = 'image/jpg'; break;//jpng
		case 7173: $fileType = 'image/gif'; break;//gif
		case 6677: $fileType = 'image/bmp'; break;//bmp
		case 13780: $fileType = 'image/png'; break;//png
		case 8075: $fileType="zip";break;
		case 6787: $fileType="application/x-shockwave-flash";break;//swf
		default: $fileType='unknown';
	}
	return $fileType;
}


function download($url, $filename){ 
        if(substr($url,0,5)=='http:' or substr($url,0,6)=='https:'){
			 $a=get_headers($url,1);			
			 if(!$a || preg_match("/200/i",$a[0])==false){
				if(isset($a['Location'])){				
					$url=$a['Location'];
					$a=get_headers($url,1);
				}
			}
			$filesize=$a['Content-Length'];
		}else{
        	$filesize = sprintf("%u", filesize($url)); 
		}
        if (!$filesize) 
        { 
       		 return; 
        }        
        header("Content-type:application/octet-stream\n"); //application/octet-stream 
        header("Content-type:unknown/unknown;"); 
        header("Content-disposition: attachment; filename=\"".$filename."\""); 
        header('Content-transfer-encoding: binary');         
        if ($range = getenv('HTTP_RANGE')) // 当有偏移量的时候，采用206的断点续传头 
        { 
        $range = explode('=', $range); 
        $range = $range[1]; 

        header("HTTP/1.1 206 Partial Content"); 
        header("Date: " . gmdate("D, d M Y H:i:s") . " GMT"); 
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime($url))." GMT"); 
        header("Accept-Ranges: bytes"); 
        header("Content-Length:".($filesize - $range)); 
        header("Content-Range: bytes ".$range.($filesize-1)."/".$filesize); 
        header("Connection: close"."\n\n"); 
        } 
        else 
        { 
        header("Content-Length:".$filesize."\n\n"); 
        $range = 0; 
        }
       loadFile($url);               
    }
    
    function loadFile($filename, $retbytes = true) {
        $buffer = '';
        $cnt =0;        
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
          return false;
        }
        while (!feof($handle)) {
          $buffer = fread($handle, 1024*1024);
          echo $buffer;
          ob_flush();
          flush();
          if ($retbytes) {
            $cnt += strlen($buffer);
          }
        }
        $status = fclose($handle);
        if ($retbytes && $status) {
          return $cnt; // return num. bytes delivered like readfile() does.
        }
        return $status;
   }

/**
@删除目录下的所有文件 保留当前目录
*/
function delfile($dir,$rmdir=0)
{
	$dir=str_replace("..","",$dir);
	$hd=opendir($dir);
	while(false !== ($f=readdir($hd)))
	{
		if($f!=".." && $f!=".")
		{
			if(is_dir($dir."/".$f)){
				delfile($dir."/".$f."/",$rmdir);
			}else
			{
				unlink($dir."/".$f);
			}
		}
	}
	 
	closedir($hd);
	if($rmdir)
	{
		rmdir($dir);
	}
}


/*创建文件夹*/
function umkdir($dir)
{
	$dir=str_replace(ROOT_PATH,"",$dir);
	$dir=str_replace("//","/",$dir);
	$dir=str_replace("..","",$dir);
	$arr=explode("/",$dir);
	foreach($arr as $key=>$val)
	{
		$d="";
		for($i=0;$i<=$key;$i++)
		{
			$d.=$arr[$i]."/";
		} 
		if(!file_exists(ROOT_PATH.$d))
		{ 
			mkdir(ROOT_PATH.$d,0755);
		}
	}
}
/*需要跟cls_upload.php 中的方法一样*/
function dirId($id){
		return (($id/1000000)%100)."/".(($id/10000)%100)."/".(($id/100)%100)."/".($id%100)."/".$id."/";
}

function img_remote($content,$w=400,$h=400){
	preg_match_all("/<img.*src=['\"](.*)['\"][^>]*>/iUs",$content,$arr);
	if(is_array($arr[1])){
		foreach($arr[1] as $k=>$v){
			$content = str_replace($v , "/get.php?width=".$w."&height=".$h."&url=".urlencode($v) , $content);
		}
	}
	$content=preg_replace("/<img.*src=['\"](.*)['\"][^>]*>/iUs","<img src=\"\\1\">",$content);
	return $content;
}

function IMAGES_SITE($imgurl){
	if(empty($imgurl)) return "";
	if(stripos($imgurl,"http://")===false && stripos($imgurl,"https://")===false ){
		return IMAGES_SITE.$imgurl;
	}else{
		return $imgurl;
	}
}

?>