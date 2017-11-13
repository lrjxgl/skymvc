
<?php
	
class ftp 
{ 
  public $result;               
  public $conn_id;          
   
  /** 
  * 方法：FTP连接 
  * @FTP_HOST -- FTP主机 
  * @FTP_PORT -- 端口 
  * @FTP_USER -- 用户名 
  * @FTP_PASS -- 密码 
  */
  function __construct($FTP_HOST,$FTP_PORT,$FTP_USER,$FTP_PASS) 
  { 
    $this->conn_id = @ftp_connect($FTP_HOST,$FTP_PORT) or die("FTP服务器连接失败"); 
    @ftp_login($this->conn_id,$FTP_USER,$FTP_PASS) or die("FTP服务器登陆失败"); 
    @ftp_pasv($this->conn_id,1); // 打开被动模拟 
  } 
  
  function chmod($mod,$file){
  	if(ftp_chmod($this->conn_id,$mod,$file)!==false){
  		return true;
  	}
  	return false;
  }
  
  function down_dir($from,$to,$mod="666"){
  	$files = ftp_rawlist($this->conn_id, $from,true);
  	if(empty($files)) return false; 
  	foreach($files as $f){
  		$type=substr($f,0,1);
  		 
  		if(trim($type)=="d"){
  			$file=str_replace(substr($f,0,strrpos($f," ")+1),"",$f);
  			$this->down_dir($from."/".$file,$to,$mod);
  		}else{
  			$file=str_replace(substr($f,0,strrpos($f," ")+1),"",$f);
  			$this->down_file($from."/".$file,$to.$from."/".$file,$mod);
  		}
  		 
  	}
  	return true;
	
  }
  
  function down_file($from,$to,$mod="666"){
  	$dir=dirname($to); 	 
  	if($dir!="." && !file_exists($dir)){
  		mkdir($dir,$mod,true);  		
  	}
  	$fp=fopen($to,"w");
  	if (ftp_fget($this->conn_id, $fp, $from, FTP_ASCII, 0)) {
  		return true;
  	}
  	return false;
  	fclose($fp);
  }
  
  function upload_dir($from,$to="."){
  	$dh=opendir($from);
  	while(($f=readdir($dh))!==false){
  		if($f=="." || $f=="..") continue;
  		if(is_file($from."/".$f)){
  			$this->upload_file($from."/".$f,$to."/".$from."/".$f);
  		}else{
  			$this->upload_dir($from."/".$f,$to);
  		}
  	}
  	
  }
  
  function upload_file_more($files=array(),$to,$type=true){
  	foreach($files as $from){
  		$this->upload_file($from,$to,$type);
  	}
  }
  
  function upload_file($from,$to,$type=true) 
  { 
    if($type) $this->umkdir($to); 
    $this->result = @ftp_put($this->conn_id,$to,$from,FTP_BINARY); 
    if(!$this->result) echo "文件上传失败,请检查权限及路径是否正确！"; 
  } 
   
 
  function move_file($from,$to,$type=true) 
  { 
    if($type) $this->umkdir($from); 
    $this->result = @ftp_rename($this->conn_id,$from,$to); 
    if(!$this->result) echo "文件移动失败,请检查权限及原路径是否正确！"; 
  } 
   
 
  function delete_file($file) 
  { 
    $this->result = @ftp_delete($this->conn_id,$file); 
    if(!$this->result) echo "文件删除失败,请检查权限及路径是否正确！"; 
  } 
   
  /** 
  * 方法：生成目录 
  * @path -- 路径 
  */
  function umkdir($path) 
  { 
    $path_arr = explode('/',$path);       // 取目录数组 
    $file_name = array_pop($path_arr);      // 弹出文件名 
    $path_div = count($path_arr);        // 取层数 
   
    foreach($path_arr as $val)          // 创建目录 
    { 
      if(@ftp_chdir($this->conn_id,$val) == FALSE) 
      { 
        $tmp = @ftp_mkdir($this->conn_id,$val); 
        if($tmp == FALSE) 
        { 
          echo "目录创建失败,请检查权限及路径是否正确！"; 
          exit; 
        } 
        @ftp_chdir($this->conn_id,$val); 
      } 
    } 
       
    for($i=1;$i<=$path_div;$i++)         // 回退到根 
    { 
      @ftp_cdup($this->conn_id); 
    } 
  } 
   
  /** 
  * 方法：关闭FTP连接 
  */
  function close() 
  { 
    @ftp_close($this->conn_id); 
  } 
} 
?>