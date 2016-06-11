<?php
class smarty{
	
	public $template_dir   = '';//模版文件夹
    public $cache_dir      = '';//缓存文件夹
    public $compile_dir    = '';//编译文件夹
	public $html_dir		='';//生成静态文件夹
	public $htm_lfile="";
	public function __construct(){
		
	}
	
	public function goAssign($tpl_var, $value = ''){
		
		if(get('ajax')){
			skymvc_test_page_auto();
			C()->goALL("success",0,$tpl_var);
		}else{
			$this->assign($tpl_var,$value);
		}
	}
	 public function assign($tpl_var, $value = '')
    {
		 
        if (is_array($tpl_var))
        {
            foreach ($tpl_var AS $key => $val)
            {
                if ($key != '')
                {
                    $this->_var[$key] = $val;
                }
            }
        }
        else
        {
            if ($tpl_var != '')
            {
                $this->_var[$tpl_var] = $value;
            }
        }
    }
	public function display($filename, $cache_id = ''){
		 
		$out = $this->fetch($filename, $cache_id);
		
		if(function_exists("shouQuanTpl")){
				$out=shouQuanTpl($out);
		}
		echo $out; 		
	}
	
	public function html($htmlfile,$expire=3600){
		$file=$this->html_dir."/".$htmlfile;
		if(file_exists($file) && !isset($_GET['forceHtml'])){
			return false;
		}else{
			$this->html_file=$file;
		}
	}
	
	public function fetch($filename, $cache_id = '',$dir=""){
		 
		ob_start();
		extract($this->_var);
		require $this->template_dir."/".$filename;
		$out=ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	public function is_cached($filename, $cache_id = ''){
		return true;
	}
	
	
	
	 
	
}
?>