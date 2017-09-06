<?php
class checkcode
{
	public function __construct(){
		
	}
	public function randnum($len){
		$srcstr="ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
		mt_srand();//配置乱数种子
		$str="";
		$ct=strlen($srcstr)-1;
		for($i=0;$i <$len;$i++){
			$str.=$srcstr[mt_rand(0,$ct)];
		}
		
		if(function_exists("c")){
			C()->set_session('checkcode',$str);
		}else{
			$_SESSION['checkcode']=$str;
		}
		return $str;
	}
	
	public function getstr(){
		$a=rand(1,9);
		$b=rand(1,9);
		$q=array("+","-","*");
		shuffle($q);
		$e=$q[0];
		switch($e){
			case "+":
				$c=$a+$b;
				break;
			case "-":
				$c=$a-$b;
				break;
			case "*":
				$c=$a*$b;
				break;
			default:
				$c=$a+$b;
				break;
		}
		$r=rand(1,3);
		switch($r){
			case 1:
					$str="{$a} {$e} {$b}=?";
					$answer=$c; 
				break;
			case 2:
					$str="{$a} {$e}?={$c}";
					$answer=$b;
					break;
			default:
					$str="? {$e} {$b}={$c}";
					$answer=$a;
				break;			
		}
		if(function_exists("c")){
			C()->set_session('checkcode',$answer);
		}else{
			$_SESSION['checkcode']=$answer;
		}
		return $str;
	}
	/**
	* type 生成验证码的类型 1数字计算  2 随机字符串
	* width 验证码宽度
	* height 验证码高度
	*/
	
	public function setimg($type=1,$width=80,$height=25){
		switch($type){
			case 2:
					$str=$this->randnum(4); //随机生成的字符串
					 
				break;
			default:
					$str=$this->getstr();
				break;
		}
		
		header("Content-Type:image/jpeg");
		$im=imagecreate($width,$height);
		//背景色
		$back=imagecolorallocate($im,0xFF,0xFF,0xFF);
		//模糊点颜色
		$pix=imagecolorallocate($im,187,230,247);
		//字体色
		$font=imagecolorallocate($im,41,163,238);
		
		//绘模糊作用的点
		mt_srand();
		
		for($i=0;$i <1000;$i++)
		{
			imagesetpixel($im,mt_rand(0,$width),mt_rand(0,$height),$pix);
		}
		
		imagestring($im, 5, 7, 5,$str, $font);
		imagerectangle($im,0,0,$width-1,$height-1,$font);
		
		imagejpeg($im);
		
		imagedestroy($im);
	}

}
?>