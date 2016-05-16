<style>
.month{width:240px; height:260px; float:left; margin-right:10px; background-color:#C60; margin-bottom:10px;}
.month>.hd{text-align:center; height:30px; line-height:30px;}
.day{ padding:10px; float:left;}
.day:hover{background-color:#F93; cursor:pointer;}
.clear{clear:both;}
</style>
<?php

rili::show(strtotime("-2 year"));
class rili{
	public static $baseurl="cls_rili.php?";
	public static function show($dateline=0){
		if($dateline==0) $dateline=time();
		/*for($i=1;$i<=12;$i++){
			$months[$i]=self::day($i,date("Y",$dateline));
		}
		*/
		echo '<div class="month"><div class="hd"><span style="float:left">&lt;&lt;</span>上月份'.self::mname(date("m",$dateline)).'<span style="float:right;">下月份</span></div>'.self::day(date("m",$dateline),date("Y",$dateline)).'<div class="clear"></div></div>';
		/*
		foreach($months as $k=>$v){
			echo '<div class="month"><div class="hd">'.self::mname($k).'</div>'.$v.'<div class="clear"></div></div>';
		}
		*/
	}
	
	public function day($m,$year=0){
		if(!$year) $year=date("Y");
		$a1=array(1,3,5,7,8,10,12);
		$a2=array(4,6,9,11);
		$days=0;
		if($m==2){
			$days=self::m2($year);
		}elseif(in_array($m,$a1)){
			$days=31;
		}else{
			$days=30;
		}
		$str="";
		for($i=1;$i<=$days;$i++){
			$str.="<div class='day'>".$i."</div>";
		}
		return $str;
	}
	
	public function m2($year){
		if(($year%4==0 && $year%100!=0) or ($year%400==0)){
 			return 29;
		}else{
			return 28;
		}
	}
	
	public function mname($m){
		$date=array(
			"一月","二月","三月","四月","五月","六月","七月","八月","九月","十月","十一月","十二月"
		);
		return $date[$m-1];
	}
	
}
?>