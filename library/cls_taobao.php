<?php
/*
header("Content-type:text/html;charset=utf-8");

$tao= new taobao();
$d=$tao->stole("http://detail.tmall.com/item.htm?spm=a1z10.4.w5003-7492062920.5.ID5r4O&id=39235427764&scene=taobao_shop");
print_r($d);
*/
class taobao
{  
    public $appkey = "21258382";  
    public $secretKey = "ac5e8a5a528170d51708515a50641b38";
	public $appconfig;  
    function __construct(){
				
    }
 	
	public function setConfig($appconfig){
		if(!empty($appconfig)){
			$this->appconfig=$appconfig;
		}
        shuffle($this->appconfig);
        $this->appkey    = $this->appconfig[0][0];
        $this->secretKey = $this->appconfig[0][1];
	}
	//采集详细页面
	public function Stole($url){
		$a=get_headers($url,1);
		if(isset($a['location'])){
			$url=$a['location'];
		}
		$arr=parse_url($url);
		parse_str($arr['query'],$a);
		$id=$a['id'];
		$c=file_get_contents($url);
		$c=iconv("gbk","utf-8",$c);
		$site="taobao";
		if(strpos($url,"tmall.com")!==false){
			$site="tmall";
		}
		switch($site){
			case "taobao":
				//TaoBao
				preg_match("/<img id=\"J_ImgBooth\" data-src=\"(.*)\"[^>]*>/iUs",$c,$a);
				$data['img']=addslashes(str_replace("_.webp","",$a[1])); 
				
				$data['taoid']=addslashes($id);
				preg_match("/<h3 class=\"tb-main-title\" data-title=\"(.*)\">/iUs",$c,$a);
				$data['title']=addslashes(trim($a[1]));
				preg_match("/<strong id=\"J_StrPrice\".*<em class=\"tb-rmb-num\">(.*)<\/em>/iUs",$c,$a);
				$data['price']=intval($a[1]*0.9);
				break;
			case "tmall":
				$data['taoid']=addslashes($id);
				preg_match("/<img id=\"J_ImgBooth\".*src=\"(.*)\"[^>]*>/iUs",$c,$a);
				$data['img']=addslashes(str_replace("_.webp","",$a[1])); 
				preg_match("/<h1 data-spm=\".*\">(.*)<\/h1>/iUs",$c,$a);
				$data['title']=addslashes(trim($a[1]));
				preg_match("/<strong class=\"J_originalPrice\">(.*)<\/strong>/iUs",$c,$a);
				$data['price']=intval($a[1]*0.9);
				break;
		}
		return $data;
	}
	
    //获取淘客产品列表
    public function getlist($keyword, $page = 0)
    {
        if ($page > 10)
            return false;
        $opt     = "timestamp=" . date("Y-m-d H:i:s") . "&v=2.0&app_key={$this->appkey}&method=taobao.taobaoke.items.get&partner_id=top-apitools&format=json&keyword=" . urlencode($keyword) . "&fields=num_iid,title,pic_url,price,url,desc,click_url&sign_method=md5&page_no={$page}&page_size=40";
        $sign    = $this->getsign($opt);
        $url     = str_replace(" ", "%20", "http://gw.api.taobao.com/router/rest?sign=$sign&{$opt}");
        $content = $this->curl_get_contents($url);
        
        $arr  = json_decode($content, true);
        $data = array();
        if ($arr && isset($arr['taobaoke_items_get_response']['taobaoke_items']['taobaoke_item'])) {
            $data = $arr['taobaoke_items_get_response']['taobaoke_items']['taobaoke_item'];
        }
        return $data;
    }
    //获取淘客产品列表
    public function getlistbycid($cid, $page = 0)
    {
        if ($page > 10)
            return false;
        $opt     = "timestamp=" . date("Y-m-d H:i:s") . "&v=2.0&app_key={$this->appkey}&method=taobao.taobaoke.items.get&partner_id=top-apitools&format=json&cid=" . $cid . "&fields=num_iid,title,pic_url,price,click_url,desc&sign_method=md5&page_no={$page}&page_size=40";
        $sign    = $this->getsign($opt);
        $url     = str_replace(" ", "%20", "http://gw.api.taobao.com/router/rest?sign=$sign&{$opt}");
        $content = $this->curl_get_contents($url);
        
        $arr  = json_decode($content, true);
        $data = array();
        if ($arr && isset($arr['taobaoke_items_get_response']['taobaoke_items']['taobaoke_item'])) {
            $data = $arr['taobaoke_items_get_response']['taobaoke_items']['taobaoke_item'];
        }
        return $data;
    }
	/*获取商品详细信息*/
	function getDetail($url,$iswap=false){
		$arr=parse_url($url);
		parse_str($arr['query'],$a);
		$id=$a['id'];
		 	
		$opt="timestamp=".urlencode(date("Y-m-d H:i:s"))."&v=2.0&app_key={$this->appkey}&method=taobao.taobaoke.items.detail.get&format=json&num_iids={$id}&is_mobile={$iswap}&fields=click_url,shop_click_url,pic_url,seller_credit_score,num_iid,title,nick,desc,price,uid&sign_method=md5";
		$sign=$this->getsign($opt);
		
		$url="http://gw.api.taobao.com/router/rest?sign={$sign}&".$opt;
		$content=$this->curl_get_contents($url);
		
		$arr=json_decode($content,true);
		 
		$data=array();
		if(isset($arr['taobaoke_items_detail_get_response']['taobaoke_item_details']['taobaoke_item_detail'])){
			$data=$arr['taobaoke_items_detail_get_response']['taobaoke_item_details']['taobaoke_item_detail'][0];
			$data['nick']=$data['item']['nick'];
			$data['num_iid']=$data['item']['num_iid'];
			$data['pic_url']=$data['item']['pic_url'];
			$data['price']=$data['item']['price'];
			$data['title']=$data['item']['title'];
			$data['content']=$data['item']['desc'];
			$data['error']=0;
			unset($data['item']);
		}else{
			return $this->getunTao($id);//产品不存在
		}
		 
		return $data;
	}
	
	//获取非淘宝客商品
	public function getunTao($id){
		$opt="timestamp=".urlencode(date("Y-m-d H:i:s"))."&v=2.0&app_key={$this->appkey}&method=taobao.item.get&format=json&num_iid={$id}&fields=detail_url,desc,pic_url,num_iid,title,nick,price,uid&sign_method=md5";
		$sign=$this->getsign($opt);
		
		$url="http://gw.api.taobao.com/router/rest?sign={$sign}&".$opt;
		$content=$this->curl_get_contents($url);
		$arr=json_decode($content,true);
		 
		$data=array();
		if(isset($arr['item_get_response']['item'])){
			$data=$arr['item_get_response'];
			$sdata['click_url']=$data['item']['detail_url'];
			$sdata['nick']=$data['item']['nick'];
			$sdata['num_iid']=$data['item']['num_iid'];
			$sdata['pic_url']=$data['item']['pic_url'];
			$sdata['price']=$data['item']['price'];
			$sdata['title']=$data['item']['title'];
			$sdata['content']=$data['item']['desc'];
			$sdata['error']=0;
			return $sdata;
		}else{
			return array('error'=>1,"message"=>"无法获取淘宝信息"); 
		}
		
	}
    //生成标签
    public function getsign($str)
    {
        parse_str($str, $arr);
        ksort($arr);
        $w = $this->secretKey;
        foreach ($arr as $k => $v) {
            $w .= "$k$v";
        }
        $w .= $this->secretKey;
        return strtoupper(md5($w));
    }
    //获取产品链接
    public function getUrl($keyword, $page = 0)
    {
        $arr     = array();
        $content = $this->curl_get_contents("http://s.taobao.com/search?q=" . urlencode($keyword) . "&s=$page");
        
        preg_match_all("/(http:\/\/item.taobao.com\/[^\"]*)\"/iUs", $content, $a1);
        
        preg_match_all("/(http:\/\/detail.tmall.com\/[^\"]*)\"/iUs", $content, $a2);
        if (isset($a1[1]) && isset($a2)) {
            $arr = array_merge($a1[1], $a2[1]);
        } elseif (isset($a1[1])) {
            $arr = $a1[1];
        } elseif (isset($a2)) {
            $arr = $a2;
        }
        return array_unique($arr);
    }
    //获取店铺id 跟 产品id
    public function getuserAutionid($id)
    {
		$t=0;
		$url="http://item.taobao.com/item.htm?id={$id}";		 
        $content = file_get_contents($url);
        preg_match("/userNumId=(\d+)/i", $content, $a);		 
		if(!isset($a[1])){
			$url="http://detail.tmall.com/item.htm?id={$id}";
			$content = file_get_contents($url);
        	preg_match("/sellerId=(\d+)/i", $content, $a);
			$t=1;	
		}
        $userNumId = $a[1]; //用户id
        $arr       = parse_url($url);
        parse_str($arr['query'], $a);
        $auctionNumId = $a['id']; //产品id
        return array(
            "userNumId" => $userNumId,
            "auctionNumId" => $auctionNumId,
			"t"=>$t
        );
    }
    //获取评论
    public function getCommentList($id)
    {
		 
		$arr=$this->getuserAutionid($id);
		
        if($arr['t']){
			$c = $this->curl_get_contents("http://rate.tmall.com/list_detail_rate.htm?itemId={$arr['auctionNumId']}&spuId=&sellerId={$arr['userNumId']}");
			$c=iconv("gbk","utf-8",$c);
			$temp=explode("\"rateList\":",$c);
			$c=$temp[1];
			$temp=explode("}],\"tags\"",$c);
			$c=$temp[0]."}]";
			$t=json_decode($c,true);
			if(!empty($t)){
				foreach($t as $k=>$v){					 
					$v['content']=$v['rateContent'].(isset($v['appendComment']['content'])?$v['appendComment']['content']:"");
					$a['comments'][$k]['content']=$v['content'];
					$a['comments'][$k]['rateId']=$v['id'];
				}
			}
			
		}else{
			$c = $this->curl_get_contents("http://rate.taobao.com/feedRateList.htm?userNumId={$arr['userNumId']}&auctionNumId={$arr['auctionNumId']}&currentPageNum=1&rateType=&orderType=sort_weight&showContent=1&attribute=&callback=jsonp_reviews_list");
			$c=iconv("gbk","utf-8",$c);
			$c=str_replace("jsonp_reviews_list(","",$c);
			$c=str_replace("]})","]}",$c);
        	//preg_match_all("/,\"content\":\"(.+)\",\"date\"/iUs", $c, $a);
			$a=json_decode($c,true);
			
		}
        if(!empty($a)){
			return $a['comments'];
		}else{
			return false;
		}
      //  return $a[1];
    }
    //获取分类id 
    public function getcatid($cid)
    {
        $opt  = "timestamp=" . urlencode(date("Y-m-d H:i:s")) . "&v=2.0&app_key={$this->appkey}&method=taobao.itemcats.get&partner_id=top-apitools&format=json&parent_cid={$cid}&fields=cid,name&sign_method=md5";
        $sign = $this->getsign($opt);
        $url  = str_replace(" ", "%20", "http://gw.api.taobao.com/router/rest?sign={$sign}&{$opt}");
        $c    = $this->curl_get_contents($url);
        $a    = json_decode($c, 1);
        if (isset($a['itemcats_get_response']['item_cats']['item_cat'])) {
            return $a['itemcats_get_response']['item_cats']['item_cat'];
        } else {
            return false;
        }
        
    }
    //获取商品地址
    function geturlbyurl($url, $page = 0)
    {
        $arr = array();
        if (strpos($url, "&s=") === false) {
            $url .= "&s=$page";
        } else {
            $url = preg_replace("/&s=(\d*)/i", "&s=$page", $url);
        }
        $content = $this->curl_get_contents($url);
        preg_match_all("/(http:\/\/item.taobao.com\/item[^\"]*)\"/iUs", $content, $a1);
        
        preg_match_all("/(http:\/\/detail.tmall.com\/[^\"]*)\"/iUs", $content, $a2);
        if (isset($a1[1]) && isset($a2)) {
            $arr = array_merge($a1[1], $a2[1]);
        } elseif (isset($a1[1])) {
            $arr = $a1[1];
        } elseif (isset($a2)) {
            $arr = $a2;
        }
        return array_unique($arr);
    }
    //获取商品地址
    function geturlbyshop($url, $page = 1)
    {
        $arr = array();
        $url = "http://" . $url . "/search.htm?spm=a1z10.3.0.106.Plq3G9&browseType=&search=y";
        if (strpos($url, "&pageNum=") === false) {
            $url .= "&pageNum=$page";
        } else {
            $url = preg_replace("/&pageNum=(\d*)/i", "&pageNum=$page", $url);
        }
        
        $content = $this->curl_get_contents($url);
        preg_match_all("/(http:\/\/item.taobao.com\/item[^\"]*)\"/iUs", $content, $a1);
        
        preg_match_all("/(http:\/\/detail.tmall.com\/[^\"]*)\"/iUs", $content, $a2);
        if (isset($a1[1]) && isset($a2)) {
            $arr = array_merge($a1[1], $a2[1]);
        } elseif (isset($a1[1])) {
            $arr = $a1[1];
        } elseif (isset($a2)) {
            $arr = $a2;
        }
        return array_unique($arr);
    }
	
	
	function searchShop($w,$s=0){
		$url="http://s.taobao.com/search?spm=0.0.0.0.XxgxOg&app=shopsearch&q=".urlencode($w)."&fs=1&olu=yes&sort=credit-desc&s=$s";
		$content=iconv("gbk","utf-8",file_get_contents($url));	
		preg_match_all("/<span class=\"shop-info-list\">(.*)<\/span>/iUs",$content,$d);//店铺信息
		preg_match_all("/<span class=\"info-sale\">(.*)件宝贝<\/span>/iUs",$content,$e);//店铺销售
		preg_match_all("/<h4>(.*)<\/h4>/iUs",$content,$g);//店铺名称
		preg_match_all("/<li class=\"list-img\">.*<img src=\"(.*)\".*<\/li>/iUs",$content,$logo);//logo
		$d=$d[1];
		if($d){
			$shoplist=array();
			foreach($d as $k=>$v){			
				preg_match("/<a trace=\"shop\" data-uid=\"(\d+)\" target=\"_blank\" href=\"([^\"]*)\">(.*)<\/a>.* data-nick=\"(.*)\"/iUs",$v,$dd);
				preg_match_all("/<em>(.*)<\/em>/iUs",$e[0][$k],$ee);
				preg_match("/<a.*>(.*)<\/a>/iUs",$g[1][$k],$sn);//店铺名字
				preg_match("/seller-rank-(\d+)\"/iUs",$g[1][$k],$sr);//店铺等级
				if(!empty($dd)){
					$shop=array(					
						"shop_uid"=>$dd[1],
						"word"=>$w,
						"shopurl"=>$dd[2],
						"shopname"=>trim($sn[1]),
						"nickname"=>urldecode($dd[4]),
						"goods_num"=>$ee[1][1],
						"sold_num"=>$ee[1][0],
						"rank"=>isset($sr[1])?intval($sr[1]):0,
						"ismall"=>isset($sr[1])?0:1,
						"goodsgrade"=>round($ee[1][0]/$ee[1][1],2),
						"logo"=>$logo[1][$k]
					);
					$shoplist[]=$shop;
				}
			}
		}
		return $shoplist;
	}
	
	function curl_get_contents($url,$timeout=60){
		 $ch = curl_init();
		 curl_setopt($ch, CURLOPT_URL, $url);
		 curl_setopt($ch, CURLOPT_HEADER, 0);
		 curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		 curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		 curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)");
		 $content= curl_exec($ch);
		 curl_close($ch);
		 return $content;
	}
}

?>