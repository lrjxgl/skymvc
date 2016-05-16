<?php
/**
**拼图程序 By雷日锦 2013-09-14
*/
class  pintu{
	
	public function __construct(){
		
	}
	/*九宫格拼图*/
	function nine($file,$images=array(),$width=200,$height=200)
	{
		$im=@imagecreatetruecolor(200, 200);
		$bg = imagecolorallocate($im,250,250,250);
		imagefill($im, 0, 0, $bg);
		$white= imagecolorallocate($im,255,255,255);
		//64 8 
		//画x轴
		imagefilledrectangle($im,0,64,200,68,$white);
		imagefilledrectangle($im,0,132,200,136,$white);
		//画y轴
		imagefilledrectangle($im,64,0,68,200,$white);
		imagefilledrectangle($im,132,0,136,200,$white);
		//拷贝图片
		if(!empty($images))
		{
			$ks[0]=array(0,0,0,0);
			$ks[1]=array(68,0,0,0);
			$ks[2]=array(136,0,0,0);
			$ks[3]=array(0,68,0,0);
			$ks[4]=array(68,68,0,0);
			$ks[5]=array(136,68,0,0);
			$ks[6]=array(0,136,0,0);
			$ks[7]=array(68,136,0,0);
			$ks[8]=array(136,136,0,0);
			foreach($images as $k=>$filename){
				$gm=getimagesize($filename);
				if($gm[0]<5 ) continue;
				$image = $this->imgcreate($filename,$gm['mime']);
				
				list($width, $height) = $gm;
				 
				imagecopyresampled($im, $image, $ks[$k][0], $ks[$k][1], 0, 0, 64, 64, $width, $height);
				 
			}
		}
		imagejpeg($im,$file,100);
		 
	}
	
	public function imgcreate($img,$mime){
		switch($mime)
		{
			case 'image/gif':
				return imagecreatefromgif($img);
			break;
			case "image/jpeg":
			case "image/jpg":
				return imagecreatefromjpeg($img);
			break;
			case 'image/png':
				return imagecreatefrompng($img);
			break;
			case 'image/bmp':
			case "image/x-ms-bmp":
				return imagecreatefromwbmp($img);
			break;
		}
	}
	
}
?>