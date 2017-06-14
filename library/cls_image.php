<?php
/*
图片处理 
缩略图及打水印
*/
 
class image{
public $quality=75;
function __construct(){
}
/**
缩略图函数
*/
function makethumb($dstimg,$img,$dstw,$dsth=999,$all=false)
{
	if(substr($img,0,5)!="http:" && substr($img,0,6)!="https:"){
		$img=ROOT_PATH."/".$img;
	}
	if(!file_exists($img))
	{
		return false;
	}
	
	list($width,$height) = getimagesize($img);
	$percent=$dstw/$width;//固定宽度
	/*
	if($width>$height)
	{
		$percent=$dstw/$width;
	}else
	{
		$percent=$dsth/$height;
	}*/
	
	$new_width = $width * $percent;
	$new_height = $height * $percent;
	if($all){
		$new_width=$dstw;
		$new_height=$dsth;
		if($all==1){
			if($height>$width){
				$height=$width;
			}else{
				$width=$height;
			}
		}else{
			$height=$new_height;
			$width=$new_width;
		}
		
	}
	$im = imagecreatetruecolor($new_width, $new_height);
	$alpha = imagecolorallocatealpha($im, 0, 0, 0, 127);
	imagefill($im, 0, 0, $alpha);
	$imgtype=$this->getimgtype($img);
	$image = $this->imagecreatefrom($img,$imgtype);
	
	imagecopyresampled($im, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
	imagesavealpha($im, true);
	$this->imagesave($im, $dstimg,$imgtype);
	return $dstimg;
}


/**
增加水印

$config=array(
	"dstimg"=>"要打水印的图片",
	//打水印的位置 0随机 1左上 2中上 3右上 4左中 5中中 6右中 7左下 8中下 9右下 
	 
	"warterpos"=>0,
	"img"=>"水印图片",
	"str"=>"水印文字",
	"size"=>文字大小,
	"font"=>"字体文件",
	"color"=>"文字颜色",
	"show"=>0,//0保存文件 1直接输出,
	"type"=>1,//水印类型 1 文字 0图片
)
*/
function addwater($config)
{
	
	//初始化处理
	if(!isset($config['font'])){
		$config['font']=str_replace("\\","/",dirname(realpath(__FILE__)))."/".iconv("utf-8","gbk","迷你简美黑.ttf");
	}

	if(!isset($config['color'])){
		$config['color']="#FF6000";
	}
	$color=$config['color'];
	if(!isset($config['size'])){
		$config['size']=16;	
	}
	
	if(!isset($config['str'])){
		$config['str']="Skymvc";
	}
	
	if(!isset($config['type'])){
		$config['type']=1;
	}
	
	if(!isset($config['warterpos'])){
		$config['warterpos']=9;
	}
	if(substr($config['dstimg'],0,5)!="http:" && substr($config['dstimg'],0,6)!="https:"){
		$config['dstimg']=ROOT_PATH."/".$config['dstimg'];
	}
	if(!file_exists($config['dstimg']))
	{
		return false;
	}
	 
	
	list($dw,$dh)=getimagesize($config['dstimg']);	
	$dsttype=$this->getimgtype($config['dstimg']);
	$dstim=$this->imagecreatefrom($config['dstimg'],$dsttype);
	if(!$config['type'])
	{
		//水印图片
		list($w,$h)=getimagesize($config['img']);
		$imgtype=$this->getimgtype($config['img']);
		$im=$this->imagecreatefrom($config['img'],$imgtype);
	}else
	{
		if(!file_exists($config['font'])) return false;
		$temp=imagettfbbox($config['size'],0,$config['font'],$config['str']);
		 
		$w=$temp[2]-$temp[6];
		$h=$temp[3]-$temp[7];	
	}
	if(($dw<$w) || ($dh<$h))
	{	
		return false;
	}
	
	switch($config['warterpos'])
	{
		case 0://随机
			$posX=rand(0,($dw-$w));
			$posY=rand(0,($dh-$h));	
			break;
		case 1://左上
			if(!$config['type'])
			{
			$posX=0;
			$posY=0;
			}else
			{
			$posX=0;
			$posY=$h;
			}
			
			break;
		case 2://中上
			if(!$config['type'])
			{
			$posX=($dw-$w)/2;
			$posY=0;
			}else
			{
			$posX=($dw-$w)/2;
			$posY=$h;
			}
			break;
		case 3://右上
			if(!$config['type'])
			{
			$posX=$dw-$w;
			$posY=0;
			}else
			{
			$posX=$dw-$w;
			$posY=$h;
				
			}
			break;
		case 4://左中
			$posX=0;
			$posY=($dh-$h)/2;
			break;
		case 5://中中
			$posX=($dw-$w)/2;
			$posY=($dh-$h)/2;
			break;
		case 6://右中
			$posX=$dw-$w;
			$posY=($dh-$h)/2;
			break;
		case 7://左下
			$posX=0;
			$posY=$dh-$h;
			break;
		case 8://中下
			$posX=($dw-$w)/2;
			$posY=$dh-$h;
			break;
		case 9://右下
			$posX=$dw-$w;
			$posY=$dh-$h;
			break;
		default://随机
			$posX=rand(0,($dw-$w));
			$posY=rand(0,($dh-$h));	
			break;		
	}
	imagealphablending($dstim,true);
	if(!$config['type'])
	{
		//处理图片水印
		imagecopy($dstim,$im,$posX,$posY,0,0,$w,$h);
	}else
	{
		//处理文字水印
		if(!empty($color) && (strlen($color)==7))
		{
			$R=hexdec(substr($color,1,2));
			$G=hexdec(substr($color,3,2));
			$B=hexdec(substr($color,5));
		}else
		{
			$R=$G=$B="00";
		}
		$grey=imagecolorallocate($dstim,$R,$G,$B);
		imagettftext($dstim,$config['size'],0,$posX,$posY,$grey,$config['font'],$config['str']);	
		
	}
	if($config['show']){
		header("Content-type:image/jpeg");
		imagejpeg($dstim);
		exit;
	}else{
		$this->imagesave($dstim,$config['dstimg'],$dsttype);
	}
	return true;
	
}

//获取图片后缀
function getimgtype($img)
{
	$im=getimagesize($img);
	switch($im['mime'])
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
			break;
			case 'image/png':
			return 'png';
			break;
			default:
			return '';
		}
}


//输出图像
function imagesave($im,$dstimg='',$imgtype='jpeg')
{
	switch($imgtype)
	{
		case "gif":
				imagegif($im,$dstimg);
				break;	
		case "jpg":
				imagejpeg($im,$dstimg,$this->quality);
				break;
		case "png":
				imagepng($im,$dstimg);
				break;
		case "bmp":
				imagewbmp($im,$dstimg);
				break;
				
	}
}

function imagecreatefrom($img,$imgtype)
{
	switch($imgtype)
	{
		case 'gif':
			return imagecreatefromgif($img);
		break;
		case "jpg":
			return imagecreatefromjpeg($img);
		break;
		case "png":
			return imagecreatefrompng($img);
		break;
		case 'bmp':
			return imagecreatefromwbmp($img);
		break;
	}
}

}
/*
测试

$img=new image();
$img->addwater(array("dstimg"=>"1.jpg","warterpos"=>5,"type"=>1,"img"=>'2.jpg',"str"=>"测试一下"));
*/
?>