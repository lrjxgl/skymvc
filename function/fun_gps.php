<?php
/*
根据ip获取gps
{
    address: "CN|北京|北京|None|CHINANET|1|None",   #地址
    content:       #详细内容
    {
    address: "北京市",   #简要地址
    address_detail:      #详细地址信息
    {
    city: "北京市",        #城市
    city_code: 131,       #百度城市代码
    district: "",           #区县
    province: "北京市",   #省份
    street: "",            #街道
    street_number: ""    #门址
    },
    point:               #百度经纬度坐标值
    {
    x: "116.39564504",
    y: "39.92998578"
    }
    },
    status: 0     #返回状态码
} 
*/ 
function ip2latlng($ip=""){
	if(empty($ip)){
		$ip=ip();
		if($ip=='127.0.0.1' or $ip="192.168.1.1"){
			$ip='58.23.255.212';
		}
	}
	$c=file_get_contents("http://api.map.baidu.com/location/ip?ak=".BDMAPKEY."&coor=bd09ll&ip=".$ip);
	$d=json_decode($c,true);
	return $d['content'];
}

 

/*获取两个gps之间的距离*/
function distanceByLnglat($lng1,$lat1,$lng2,$lat2)
{
	if(!$lng1 or !$lng2) return 0;
	//echo "$lng1,$lat1,$lng2,$lat2  ";
     $radlat1 = Rad($lat1);
     $radlat2 = Rad($lat2);
     $a = $radlat1 - $radlat2;
     $b = Rad($lng1) - Rad($lng2);
     $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radlat1) * cos($radlat2) * pow(sin($b / 2), 2)));
    $s = $s * 6378137.0;// 取WGS84标准参考椭球中的地球长半径(单位:m)
    $s = round($s * 10000) / 10000;
	return intval($s);
}

function Rad($d)
{
    return $d * M_PI / 180.0;
}
/*gps转百度坐标*/
function gps2baidu($lat,$lng)
{
	 
	$c=file_get_contents("http://api.map.baidu.com/geoconv/v1/?coords=$lng,$lat&from=0&to=4");
	$arr=json_decode($c,true);
	if(!$arr['status'])
	{
		$lat=$arr['result'][0]['x'];
		$lng=$arr['result'][0]['y'];
	}
	 
	return array($lat,$lng);
}


function getgps($lats,$lngs)
{
	 
	$lat=$lats[0]+$lats[1]/60+$lats[2]/3600000;
	$lng=$lngs[0]+$lngs[1]/60+$lngs[2]/3600000;
	return array($lat,$lng);
}

/*测试*/
/*
$exif=exif_read_data("1.jpg");
list($lat,$lng)=getgps($exif['GPSLatitude'],$exif['GPSLongitude']);
$latlng=gps2baidu($lat,$lng);
print_r($latlng);
*/		
?>