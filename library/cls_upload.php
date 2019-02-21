<?php
if(!defined("ROOT_PATH")){
	define("ROOT_PATH",dirname(str_replace("\\", "/", dirname(__FILE__)))."/");
}
class upload{
	public $allowtype=array("gif","jpg","bmp","png",'jpeg');//允许上传的文件类型
	public $sysallowtype=array('gif','jpg','bmp','png','jpeg','txt','mpeg',"mp4","ogg",'avi','rm','rmvb','wmv','flv','mp3','wav','wma','swf','doc','pdf','zip','tar','svg');
	//系统默认允许上传
	//上传文件夹
	public $uploaddir="attach/uploads/";
	public $maxsize=4194304000;
	//是否图片上传
	public $upimg=true;
	public $safecheck=true;
	//根据id来分文件夹 可以根据id来删除整个文件夹防止垃圾文件
	public $iddir=0;
	function __construct()
	{
		
	}
	
	function uploadfile($file)
	{
		$FILE=$_FILES[$file];
		 
		//判断文件大小是否符合要求
		if($FILE['size'] > $this->maxsize or $FILE['size']==0) {
			@unlink($FILE['tmp_name']);
			return array('err'=>'上传文件过大','filename'=>'');
		}
		 
		
		if($this->iddir){
			$uploaddir=$this->uploaddir.$this->dirId($this->iddir);
		}else{
			$uploaddir=$this->uploaddir.date("Y/m/d")."/";
		}
		$this->umkdir($uploaddir);
		$bname=basename($FILE['name']);
		$f_type=strtolower(trim(substr(strrchr($bname, '.'), 1)));//获取文件后缀名
		//判断文件是否合法
		$fileType=$this->getTrueType($FILE['tmp_name']);
		if(!in_array($fileType,$this->sysallowtype) || !in_array($fileType,$this->allowtype)){
				@unlink($FILE['tmp_name']);
				return array('err'=>'文件格式'.$fileType.'禁止上传','filename'=>$FILE['name']);
		}
		$f= md5(time().$FILE['name']);
		$uploadfile=$uploaddir.$f.".$f_type";
		if($this->upimg==true)
		{			
			$fs=getimagesize($FILE['tmp_name']);
			if($fs[0]<5 || $fs[1]<5)
			{
				@unlink($FILE['tmp_name']);
				return  array("err"=>'图像必须大于5像素','filename'=>$FILE['name']);
			}else{
				return $this->move_file($FILE['tmp_name'],$uploadfile,$FILE['name'],$FILE['type'],$FILE['size']);
			}
		}

		return $this->move_file($FILE['tmp_name'],$uploadfile,$FILE['name'],$FILE['type'],$FILE['size']);
		 
		
	}
	
	public function move_file($from,$to,$original="",$type=".jpg",$size=0){
		if(move_uploaded_file($from,$to))
		{
			@unlink($from);
			return array('err'=>0,'filename'=>$to,"original"=>$original,"type"=>$type,"size"=>round($size/1048576,2));		
		}else
		{
			@unlink($from);
			return array('err'=>$original.'上传失败，请检查文件夹是否有写入权限','filename'=>'');			
		}
	}
	
	public function getTrueType($file){
			$mime=$this->getMime($file);
			return $this->getfiletype($mime);
	}
	
	public function getMime($file,$mime=""){
		
		if(function_exists("finfo_open")){
			$finfo=finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $file);
			$m=explode(";",$mimetype);
			return $m[0];
			finfo_close($finfo);
		}
		
		if(function_exists("mime_content_type")){
			return mime_content_type($file);	 
		}
		return $mime;
	}
	
	function getfiletype($ftype)
	{
		switch($ftype)
		{
			case 'image/gif':
				return 'gif';
			break;
			case 'image/bmp':
			case "image/x-ms-bmp":
				return 'bmp';
			break;
			case "image/jpeg":
				return 'jpg';
			case "image/pjpeg":
				return 'jpeg';
			break;
			case 'text/plain':
				return 'txt';
			case 'video/mpeg':
				return 'mpeg';
			case 'video/avi':
			case 'video/x-msvideo':
				return 'avi';
			case 'video/rm':
				return 'rm';
			case 'video/rmvb':
				return 'rmvb';
			case 'video/x-ms-wmv':
				return 'wmv';
			case 'application/octet-stream':
				return 'flv';
			case 'audio/x-mpeg':
			case 'audio/mpeg':	
			case 'audio/mp3':
				return 'mp3';
			case 'audio/wav':
				return 'wav';
			case 'audio/x-ms-wma':
				return 'wma';
			case 'application/x-shockwave-flash':
				return 'swf';
			case 'application/msword':
				return 'doc';
			case 'image/png':
				return 'png';
			break;
			case 'application/pdf':
				return 'pdf';
			case 'application/zip':
				return 'zip';
			case "application/x-tar":
				return 'tar';
			case "audio/ogg":
				return 'ogg';
			
			case "image/svg+xml":
				return "svg";
			case "video/mp4":
				return "mp4";
			default:	
			return $ftype;
		}
	}
	
	/*创建文件夹*/
	function umkdir($dir)
	{
		$dir=str_replace(ROOT_PATH,"",$dir);
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
	/*根据id来存储*/
	public function dirId($id){
		return (($id/1000000)%100)."/".(($id/10000)%100)."/".(($id/100)%100)."/".($id%100)."/".$id."/";
	}
	
	 
}
?>