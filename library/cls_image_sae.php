<?php
/*
图片处理 
缩略图及打水印
*/
class image{

function __construct(){
	
}
/**
缩略图函数
*/
/**
缩略图函数
*/
function makethumb($dstimg,$img,$dstw,$dsth=999,$all=false)
{
	$img = new SaeImage();
	$img_data=file_get_contents($simg);
    $img->setData( $img_data );
    $img->resize($dstw); // 等比缩放到200宽
	$new_data = $img->exec(); // 执行处理并返回处理后的二进制数据
	$s = new SaeStorage();

	$s->write( STO ,$dstimg , $new_data );
	return $s->getUrl(STO,$dstimg);
}


/**
增加水印
*/
function addwater($dstimg,$warterpos=9,$img='',$str="测试一下",$size=16,$font="skymvc/迷你简美黑.ttf",$color="#FF6000")
{

	
	list($dw,$dh)=getimagesize($dstimg);	
	$dsttype=$this->getimgtype($dstimg);
	$dstim=$this->imagecreatefrom($dstimg,$dsttype);
	if(!empty($img))
	{
	//水印图片
	list($w,$h)=getimagesize($img);
	$imgtype=$this->getimgtype($img);
	$im=$this->imagecreatefrom($img,$imgtype);	
	}else
	{
		$temp=@imagettfbbox($size,0,$font,$str);
		
		$w=$temp[2]-$temp[6];
		$h=$temp[3]-$temp[7];
				
	}
	if(($dw<$w) || ($dh<$h))
	{	
		return '需要加水印的图片比水印小，无法打水印';
	}
	switch($warterpos)
	{
		case 0://随机
			$posX=rand(0,($dw-$w));
			$posY=rand(0,($dh-$h));	
			break;
		case 1://左上
			if($img)
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
			if($img)
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
			if($img)
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
	if(!empty($img))
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
		@imagettftext($dstim,$size,0,$posX,$posY,$grey,$font,$str);
		
		
		
	}
	ob_start();
	$this->imagesave($dstim,$dsttype);
	$content=ob_get_contents();	
        ob_end_clean();
	$s = new SaeStorage();
        $newimg=preg_replace("/.*\.com\//i",'',$dstimg) ;
	$s->write( STO ,$newimg, $content);
	return $s->getUrl(STO,$newimg);
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
function imagesave($im,$imgtype)
{
	switch($imgtype)
	{
		case "gif":
				imagegif($im);
				break;	
		case "jpg":
				imagejpeg($im);
				break;
		case "bmp":
				imagepng($im);
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
	}
}

}
/*
测试

$img=new image();
$img->addwater("1.jpg",9,'2.jpg');
*/
?>