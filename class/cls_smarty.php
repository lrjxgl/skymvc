<?php

if(!defined("ROOT_PATH")){
	define("ROOT_PATH",dirname(str_replace("\\", "/", dirname(__FILE__)))."/");
}
if(!defined("OB_GZIP")){
	define("OB_GZIP",false);
}
class Smarty
{
    public $template_dir   = '';//模版文件夹
    public $cache_dir      = '';//缓存文件夹
    public $compile_dir    = '';//编译文件夹
	public $html_dir		='';//生成静态文件夹
    public $cache_lifetime = 3600; // 缓存更新时间, 默认 3600 秒
    public $direct_output  = false;//直接输出
    public $caching        = false;//是否开启缓存
    public $template       = array();//模版
    public $force_compile  = false;//强制编译 
	public $rewrite_on	=false;//是否开启伪静态
	public $rewriterule=array();//伪静态规则
    public $_var           = array();//临时变量
    public $charset			="utf-8";//设置编码 
    public $_foreach       = array();//循环
    public $_current_file  = '';//当前文件
    public $_expires       = 0;//过期时间
    public $_errorlevel    = 0;//设置错误提醒级别
    public $_nowtime       = null;//当前时间戳
    public $_checkfile     = true;//检查文件
    public $_foreachmark   = '';//循环标记
    public $_seterror      = 0;//是否错误	
    public $_temp_key      = array();  // 临时存放 foreach 里 key 的数组
    public $_temp_val      = array();  // 临时存放 foreach 里 item 的数组
	public $html_file="";
	public $_vars;
	public $dir;

   public function __construct()
    {
        $this->Smarty();
    }

    public function Smarty()
    {
        $this->_errorlevel = error_reporting();
        $this->_nowtime    = time();      
    }
	/**
		注册变量 有ajax直接返回数据	
	*/
	public function goAssign($tpl_var, $value = ''){
		
		if(get('ajax')){
			skymvc_test_page_auto();
			C()->goALL("success",0,$tpl_var);
		}else{
			$this->assign($tpl_var,$value);
		}
	}

    /**
     * 注册变量
     *
     * @access  public
     * @param   mix      $tpl_var
     * @param   mix      $value
     *
     * @return  void
     */
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

    /**
     * 显示页面函数
     *
     * @access  public
     * @param   string      $filename
     * @param   sting      $cache_id
     *
     * @return  void
     */
    public function display($filename, $cache_id = '')
    {
		$_GET=get_post_Html($_GET); 
        
		$this->_seterror++;
        error_reporting(E_ALL ^ E_NOTICE);
		
        $this->_checkfile = false;
        $out = $this->fetch($filename, $cache_id);		
        $this->_seterror--;
		
		if(OB_GZIP){
			if(extension_loaded('zlib')){
				@ob_start("ob_gzhandler");
			}
		}else{
			ob_start();
		}
		
		 
		if(function_exists("shouQuanTpl")){
				$out=shouQuanTpl($out);
		}
		if($this->html_file){
			error_reporting(E_ALL ^ E_NOTICE);
			$this->umkdir(dirname($this->html_file));		
			file_put_contents($this->html_file,$out);
		}
		if(defined("CHECKBADWORD") && CHECKBADWORD==1){
			require(ROOT_PATH."config/badword.php");
			$out=preg_replace("/".$badword."/iUs","****",$out);
		}
		echo $out;
		ob_end_flush();
		
		if(false!==method_exists(C(),"sexit")){
			C()->sexit();
		}
		exit;
    }
	
	/**
	*生成html
	*/
	public function html($htmlfile,$expire=3600){
		$file=$this->html_dir."/".$htmlfile;
		$filestat = @stat($filename);
		if(file_exists($file) && !isset($_GET['forceHtml']) && $filestat['mtime']>time()-$expire){
			return false;
		}else{
			$this->html_file=$file;
		}
	}

    /**
     * 处理模板文件
     *
     * @access  public
     * @param   string      $filename
     * @param   sting      $cache_id
     *
     * @return  sring
     */
	
	public function fetchhtml($str){
		return $out = $this->_eval($this->fetch_str($str)); 
	}
	 
    public function fetch($filename, $cache_id = '',$dir="")
    {
		//$dir=!empty($dir)?$this->template_dir."/".$dir:$this->template_dir;
		$dir=str_replace("..","",$dir);
		$dir=!empty($dir)?$dir:$this->template_dir;
        if (!$this->_seterror)
        {
            error_reporting(E_ALL ^ E_NOTICE);
        }
        $this->_seterror++;
		if(is_object(C())){
			$this->assign("config",C()->config_item());
		}
        if (strncmp($filename,'str:', 4) == 0)
        {
            $out = $this->_eval($this->fetch_str(substr($filename, 4)));
        }
        else
        {
            if ($this->_checkfile)
            {
                if (!file_exists($filename))
                {
                    $filename = $dir . '/' . $filename;
                }
            }
            else
            {
                $filename = $dir . '/' . $filename;
            }

            if ($this->direct_output)
            {
                $this->_current_file = $filename;
				if(file_exists($filename)){
                	$out = $this->_eval($this->fetch_str(file_get_contents($filename)));
				}
            }
            else
            {
                if ($cache_id && $this->caching)
                {
                    $out = $this->template_out;
                }
                else
                {
                    if (!in_array($filename, $this->template))
                    {
                        $this->template[] = $filename;
                    }

                    $out = $this->make_compiled($filename);

                    if ($cache_id)
                    {
                     	$cachename = str_replace(array("/",":"),"_",$filename) . '_' . $cache_id ;
						
                        $data = serialize(array('template' => $this->template, 'expires' => $this->_nowtime + $this->cache_lifetime, 'maketime' => $this->_nowtime));
                        $out = str_replace("\r", '', $out);

                        while (strpos($out, "\n\n") !== false)
                        {
                            $out = str_replace("\n\n", "\n", $out);
                        }

                        $hash_dir = $this->cache_dir . '/' . substr(md5($cachename), 0, 1);
                        if (!is_dir($hash_dir))
                        {
                            mkdir($hash_dir);
                        }
                        if (file_put_contents($hash_dir . '/' . $cachename . '.php', '<?php exit;?>' . $data . $out, LOCK_EX) === false)
                        {
                            trigger_error('can\'t write:' . $hash_dir . '/' . $cachename . '.php');
                        }
                        $this->template = array();
                    }
                }
            }
        }

        $this->_seterror--;
        if (!$this->_seterror)
        {
            error_reporting($this->_errorlevel);
        }
		skymvc_test_page_auto();
        return $out; // 返回html数据
    }

    /**
     * 编译模板函数
     *
     * @access  public
     * @param   string      $filename
     *
     * @return  sring        编译后文件地址
     */
    public function make_compiled($filename)
    {
	 	$dir= $this->compile_dir."/".str_replace(ROOT_PATH,"",dirname($filename));
		umkdir($dir);
        $name =$dir."/".basename($filename) . '.php';
        if ($this->_expires)
        {
            $expires = $this->_expires - $this->cache_lifetime;
        }
        else
        {
            $filestat = @stat($name);
            $expires  = $filestat['mtime'];
        }

        $filestat = @stat($filename);

        if ($filestat['mtime'] <= $expires && !$this->force_compile)
        {
            if (file_exists($name))
            {
                $source = $this->_require($name);
                if ($source == '')
                {
                    $expires = 0;
                }
            }
            else
            {
                $source = '';
                $expires = 0;
            }
        }

        if ($this->force_compile || $filestat['mtime'] > $expires)
        {
            $this->_current_file = $filename;
			if(!file_exists($filename)){
			 
            	exit("模板".$filename.'不存在');
            	 
            	return false;
            }
            $source = $this->fetch_str(file_get_contents($filename));
			
            if (file_put_contents($name, $source, LOCK_EX) === false)
            {
                trigger_error('can\'t write:' . $name);
            }

            $source = $this->_eval($source);
        }

        return $source;
    }

    /**
     * 处理字符串函数
     *
     * @access  public
     * @param   string     $source
     *
     * @return  sring
     */
    public function fetch_str($source)
    {
       //5.2
	   return preg_replace_callback("/{([^\}\{\n]*)}/", array($this, 'select'), $source);
		//5.3
       // return preg_replace_callback("/{([^\}\{\n]*)}/", "self::select", $source);
    }

    /**
     * 判断是否缓存
     *
     * @access  public
     * @param   string     $filename
     * @param   sting      $cache_id
     *
     * @return  bool
     */
    public function is_cached($filename, $cache_id = '')
    {
       $cachename = str_replace(array("/",":"),"_", $this->template_dir . '/' .$filename) . '_' . $cache_id;
	  
        if ($this->caching == true && $this->direct_output == false)
        {
			
            $hash_dir = $this->cache_dir . '/' . substr(md5($cachename), 0, 1);
			 
            if ($data = @file_get_contents($hash_dir . '/' . $cachename . '.php'))
            {
                $data = substr($data, 13);
                $pos  = strpos($data, '<');
                $paradata = substr($data, 0, $pos);
                $para     = @unserialize($paradata);
                if ($para === false || $this->_nowtime > $para['expires'])
                {
                    $this->caching = false;

                    return false;
                }
                $this->_expires = $para['expires'];

                $this->template_out = substr($data, $pos);

                foreach ($para['template'] AS $val)
                {
                    $stat = @stat($val);
                    if ($para['maketime'] < $stat['mtime'])
                    {
                        $this->caching = false;

                        return false;
                    }
                }
            }
            else
            {
                $this->caching = false;

                return false;
            }

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 处理{}标签
     *
     * @access  public
     * @param   string      $tag
     *
     * @return  sring
     */
    public function select($tag)
    {
		if(is_array($tag)) $tag=$tag[1];
        $tag = stripslashes(trim($tag));
        if (empty($tag))
        {
            return '{}';
        }elseif(substr($tag,0,3)=='get'){
		/*
		*解析模版获取model数据
		*{get model=index module=book data=k fun=list($k,$v,$s) }
		*/	
			@preg_match("/module=(.*) /iU",$tag,$module);	 
			@preg_match("/model=(.*) /iU",$tag,$m);			
			@preg_match("/data=(.*) /iU",$tag,$k);
			@preg_match("/fun=([^}]*)/i",$tag,$fun);
			$fun=$fun[1];
			//解析fun变量 $a['x']['y'] => $this_var['a']['x']['y'];
			preg_match_all('/(\$[\w\.\[\]]+)/i',$fun,$b);
			
			foreach($b[1] as $v){
				if($v=="\$rscount") continue;
				$fun=str_replace($v,$this->parse_var($v),$fun);
			}
			
			//$fun=preg_replace("/\\$([\w_]+)([\[\w_\]]*)/i","{\$this->_var[\\1]\\2}",$fun);
			//解析数组 {$a}[][][];
			if(isset($module[1])){
				return '<?php C()->loadModulemodel("'.$module[1].'","'.$m[1].'");$this->assign("'.$k[1].'",$GLOBALS["'.$m[1].'Model"]->'.$fun.'); ?>';
			}else{
				return '<?php $this->assign("'.$k[1].'",M("'.$m[1].'")->'.$fun.'); ?>';
				/*
				return '<?php C()->loadmodel("'.$m[1].'");$this->assign("'.$k[1].'",$GLOBALS["'.$m[1].'Model"]->'.$fun.'); ?>';
				*/
			}
		}elseif(substr($tag,0,3)=='api'){
			/**
			*解析模板获取control数据
			*/
			@preg_match("/module=(.*) /iU",$tag,$module);	 
			@preg_match("/control=(.*) /iU",$tag,$m);			
			@preg_match("/data=(.*) /iU",$tag,$k);
			@preg_match("/fun=([^}]*)/i",$tag,$fun);
			$fun=$fun[1];
			//解析fun变量 $a['x']['y'] => $this_var['a']['x']['y'];
			preg_match_all('/(\$[\w\.\[\]]+)/i',$fun,$b);
			foreach($b[1] as $v){
				if($v=="\$rscount") continue;
				$fun=str_replace($v,$this->parse_var($v),$fun);
			}
			//$fun=preg_replace("/\\$([\w_]+)([\[\w_\]]*)/i","{\$this->_var[\\1]\\2}",$fun);
			//解析数组 {$a}[][][];
			if(isset($module[1])){
				return '<?php $this->assign("'.$k[1].'",CC("'.$module[1].'","'.$m[1].'")->'.$fun.'); ?>';
			}else{ 
				return '<?php $this->assign("'.$k[1].'",C("'.$m[1].'")->'.$fun.'); ?>';
			}
			
		}elseif(substr($tag,0,1)=='R'){//伪静态处理
			$tag=str_replace("'",'"',$tag);
			preg_match("/R\((.*)\)/i",$tag,$a);
			$a=$a[1];
			
			preg_match_all('/(\$[\w\.\[\]]+)/i',$a,$b);
			foreach($b[1] as $v){
				$tag=str_replace($v,$this->parse_var($v),$tag);
			}
			return '<?php echo '.$tag.';?>';
		}elseif ($tag{0} == '*' && substr($tag, -1) == '*') // 注释部分
        {
            return '';
        }
        elseif ($tag{0} == '$') // 变量
        {
            return '<?php echo ' . $this->get_val(substr($tag, 1)) . '; ?>';
        }elseif(substr($tag,0,3)=='php')
		{
			$tag=str_replace('$smarty','$this->_var',$tag);
 			return "<?$tag;?>";
					 
		}elseif(substr($tag,0,5)=='const' )
		{
			return '<?php echo ' . substr($tag, 6) . '; ?>';
		}elseif(substr($tag,0,4)=='math'){//计算函数
			return $this->_math($tag);
		}elseif(substr($tag,0,3)=='fun'){
			$f=substr($tag,4);
			preg_match("/\((.*)\)/i",$f,$a);
			if(isset($a[1])){
				$re="";
				$b=explode(",",$a[1]);
				foreach($b as $k=>$kb){
					if($k>0){
						$re.=",";
					}
					if(substr($kb,0,1)=='$'){
						$re.='$this->_var["'.str_replace("$","",trim($kb)).'"]';
					}else{
						$re.=$kb;
					}
				}
				$f=str_replace($a[1],$re,$f);
			} 
			
			 
			$ex="<?php echo $f; ?>";  
			return $ex;
		}elseif ($tag{0} == '/') // 结束 tag
        {
            switch (substr($tag, 1))
            {
                case 'if':
                    return '<?php endif; ?>';
                    break;

                case 'foreach':
                    if ($this->_foreachmark == 'foreachelse')
                    {
                        $output = '<?php endif; unset($_from); ?>';
                    }
                    else
                    {
                        array_pop($this->_patchstack);
                        $output = '<?php endforeach; endif; unset($_from); ?>';
                    }
                    $output .= "<?php \$this->pop_vars();; ?>";

                    return $output;
                    break;

                case 'literal':
                    return '';
                    break;

                default:
                    return '{'. $tag .'}';
                    break;
            }
        }
        else
        {
			$t_a=explode(' ', $tag);
            $tag_sel = array_shift($t_a);
            switch ($tag_sel)
            {
                case 'if':

                    return $this->_compile_if_tag(substr($tag, 3));
                    break;

                case 'else':

                    return '<?php else: ?>';
                    break;

                case 'elseif':

                    return $this->_compile_if_tag(substr($tag, 7), true);
                    break;

                case 'foreachelse':
                    $this->_foreachmark = 'foreachelse';

                    return '<?php endforeach; else: ?>';
                    break;

                case 'foreach':
                    $this->_foreachmark = 'foreach';
                    if(!isset($this->_patchstack))
                    {
                        $this->_patchstack = array();
                    }
                    return $this->_compile_foreach_start(substr($tag, 8));
                    break;

                case 'assign':
                    $t = $this->get_para(substr($tag, 7),0);
					 
					//=号bug处理
					$t['value']=str_replace("[eq]","=",$t['value']); 
                    if ($t['value']{0} == '$' or substr(trim($t['value']),0,6)=='array(')
                    {
                        /* 如果传进来的值是变量，就不用用引号 */
                        $tmp = '$this->assign(\'' . $t['var'] . '\',' . $t['value'] . ');';
                    }
                    else
                    {
                        $tmp = '$this->assign(\'' . $t['var'] . '\',\'' . addcslashes($t['value'], "'") . '\');';
                    }
                    // $tmp = $this->assign($t['var'], $t['value']);

                    return '<?php ' . $tmp . ' ?>';
                    break;

                case 'include':
                    $t = $this->get_para(substr($tag, 8), 0);
	
					/*  shop/()left.html */
					$t['file']=str_replace('$',"",$t['file']);
					preg_match_all("/\((.*)\)/iUs",$t['file'],$t_a);
					if(isset($t_a[1])){
						foreach($t_a[1] as $t_k=>$t_a_v){
							$t['file']=str_replace($t_a[0][$t_k],"'.\"".$this->parse_var($t_a_v)."\".'",$t['file']);
						}
					}
					 
					if(isset($t['dir'])){
						if(preg_match("/this->_var/i",$t['dir'])){
							return '<?php echo $this->fetch(' . "'$t[file]'" . ',0,'.$t['dir'].'); ?>';
						}else{
							return '<?php echo $this->fetch(' . "'$t[file]'" . ',0,'."'{$t['dir']}'".'); ?>';
						}
						
					}else{
                    	return '<?php echo $this->fetch(' . "'{$t['file']}'" . '); ?>';
					}
                    break;
       
                case 'literal':
                    return '';
                    break;

                case 'cycle' :
                    $t = $this->get_para(substr($tag, 6), 0);

                    return '<?php echo $this->cycle(' . $this->make_array($t) . '); ?>';
                    break;
                default:
                    return '{' . $tag . '}';
                    break;
            }
        }
    }

    /**
     * 处理smarty标签中的变量标签
     *
     * @access  public
     * @param   string     $val
     *
     * @return  bool
     */
	public function var_replace($d){
		return ".".str_replace("$","\$",$d[1]);
	}
    public function get_val($val)
    {
        if (strrpos($val, '[') !== false && strpos($val,'.')!==false)
        {
            //php5.2
			$val = preg_replace_callback("/\[([^\[\]]*)\]/is", array($this, 'var_replace'), $val);
			//php5.3
			//$val = preg_replace_callback("/\[([^\[\]]*)\]/is", "self::var_replace", $val);
        }
		
		 
		
        if (strrpos($val, '|') !== false)
        {
            $moddb = explode('|', $val);
            $val = array_shift($moddb);
        }

        if (empty($val))
        {
            return '';
        }		
	
        if (strpos($val, '.$') !== false)
        {
			
            $all = explode('.$', $val);

            foreach ($all AS $key => $val)
            {
                $all[$key] = $key == 0 ? $this->make_var($val) : '['. $this->make_var($val) . ']';
            }
			 
            $p = implode('', $all);
			 
        }
        else
        {
			/*加减乘除*/
			if(empty($moddb)){				 
				if(preg_match("/[\+\-\*\/%]/i",$val,$a)){
					preg_match("/\w+/i",$val,$c);
					$p=$this->make_var($c[0]);
					$p=str_replace($c[0],$p,$val);
				}else{
					$p = $this->make_var($val);
				}
			}else{
            	$p = $this->make_var($val);
			}
        }

        if (!empty($moddb))
        {
            foreach ($moddb AS $key => $mod)
            {
                $s = explode(':', $mod);
                switch ($s[0])
                {
                    case 'escape':
                        $s[1] = trim($s[1], '"');
                        if ($s[1] == 'html')
                        {
                            $p = 'htmlspecialchars(' . $p . ')';
                        }
                        elseif ($s[1] == 'url')
                        {
                            $p = 'urlencode(' . $p . ')';
                        }
                        elseif ($s[1] == 'decode_url')
                        {
                            $p = 'urldecode(' . $p . ')';
                        }
                        elseif ($s[1] == 'quotes')
                        {
                            $p = 'addslashes(' . $p . ')';
                        }
                        else
                        {
                            $p = 'htmlspecialchars(' . $p . ')';
                        }
                        break;

                    case 'nl2br':
                        $p = 'nl2br(' . $p . ')';
                        break;

                    case 'default':
                        $s[1] = $s[1]{0} == '$' ?  $this->get_val(substr($s[1], 1)) : "'$s[1]'";
                        $p = 'empty(' . $p . ') ? ' . $s[1] . ' : ' . $p;
                        break;

                    case 'cutstr':
                        $p = '$this->cutstr(' . $p . ",$s[1],'$s[2]')";
                        break;

                    case 'strip_tags':
                        $p = 'strip_tags(' . $p . ')';
                        break;
					case 'date':
						$s=str_replace("date:","",$mod);
						$s=str_replace("@",":",$s);//兼容以前的
						$p='date("' . $s . '",'. $p .')';   
						break;
					case '+':
						$p = $p .'+'. $s[1];	
						break;
					case '-':
						$p = $p .'-'. $s[1];	
						break;
					case '*':
						$p = $p .'*'. $s[1];	
						break;
					case '/':
						$p = $p .'/'. $s[1];	
						break;
					
                    default:
                        if(function_exists($s[0])){
							if(isset($s[1])){
								$pa=str_replace($s[0].":","",$mod);
								$p=$s[0].'('.$p.',"'.$pa.'")';
							}else{
								$p=$s[0].'('.$p.')';
							}
						}
					
                        break;
                }
            }
        }
		 
        return $p;
    }
	/**
	*解析变量
	*/
	public function parse_var($str){
		$str=str_replace('$',"",$str);
		$a=explode(".",$str);
		$data='$this->_var';
		foreach($a as $v){
			if(strpos($v,"[")!==false){
				$b=explode("[",$v);
				foreach($b as $bb){
					$bb=str_replace("]","",$bb);
					if(preg_match("/\d+/",$bb)){
						$data.='['.$bb.']';
					}else{
						$data.='["'.$bb.'"]';
					}
				}
			}else{
				$data.='["'.$v.'"]';		
			}
		}
		return '".'. $data .'."';
	}

    /**
     * 处理去掉$的字符串
     *
     * @access  public
     * @param   string     $val
     *
     * @return  bool
     */
   public  function make_var($val)
    {
        if (strrpos($val, '.') === false)
        {
            if (isset($this->_var[$val]) && isset($this->_patchstack[$val]))
            {
                $val = $this->_patchstack[$val];
            }
			if(substr_count($val,"[")>0){
				$p=preg_replace('/\$(\w+)/i',"\$this->_var['\\1']","$".$val);
			}else{
            	$p = '$this->_var[\'' . $val . '\']';
			}
        }
        else
        {
            $t = explode('.', $val);
            $_var_name = array_shift($t);
            if (isset($this->_var[$_var_name]) && isset($this->_patchstack[$_var_name]))
            {
                $_var_name = $this->_patchstack[$_var_name];
            }
            if ($_var_name == 'smarty')
            {
                 $p = $this->_compile_smarty_ref($t);
            }
            else
            {
                $p = '$this->_var[\'' . $_var_name . '\']';
            }
            foreach ($t AS $val)
            {
                $p.= '[\'' . $val . '\']';
            }
        }

        return $p;
    }

    /**
     * 处理insert外部函数/需要include运行的函数的调用数据
     *
     * @access  public
     * @param   string     $val
     * @param   int         $type
     *
     * @return  array
     */
    public function get_para($val, $type = 1) // 处理insert外部函数/需要include运行的函数的调用数据
    {
        $pa = $this->str_trim($val);
        foreach ($pa AS $value)
        {
            if (strrpos($value, '='))
            {
                list($a, $b) = explode('=', str_replace(array(' ', '"', "'", '&quot;'), '', $value));
                if ($b{0} == '$')
                {
                    if ($type)
                    {
                        eval('$para[\'' . $a . '\']=' . $this->get_val(substr($b, 1)) . ';');
                    }
                    else
                    {
                        $para[$a] = $this->get_val(substr($b, 1));
                    }
                }
                else
                {
                    $para[$a] = $b;
                }
            }
        }

        return $para;
    }

    /**
     * 判断变量是否被注册并返回值
     *
     * @access  public
     * @param   string     $name
     *
     * @return  mix
     */
    public function &get_template_vars($name = null)
    {
        if (empty($name))
        {
            return $this->_var;
        }
        elseif (!empty($this->_var[$name]))
        {
            return $this->_var[$name];
        }
        else
        {
            $_tmp = null;

            return $_tmp;
        }
    }

    /**
     * 处理if标签
     *
     * @access  public
     * @param   string     $tag_args
     * @param   bool       $elseif
     *
     * @return  string
     */
    public function _compile_if_tag($tag_args, $elseif = false)
    {
        preg_match_all('/\-?\d+[\.\d]+|\'[^\'|\s]*\'|"[^"|\s]*"|[\$\w\.]+|!==|===|==|!=|<>|<<|>>|<=|>=|&&|\|\||\(|\)|,|\!|\^|=|&|<|>|~|\||\%|\+|\-|\/|\*|\@|\S/', $tag_args, $match);

        $tokens = $match[0];
        // make sure we have balanced parenthesis
        $token_count = array_count_values($tokens);
        if (!empty($token_count['(']) && $token_count['('] != $token_count[')'])
        {
            // $this->_syntax_error('unbalanced parenthesis in if statement', E_USER_ERROR, __FILE__, __LINE__);
        }

        for ($i = 0, $count = count($tokens); $i < $count; $i++)
        {
            $token = &$tokens[$i];
            switch (strtolower($token))
            {
                case 'eq':
                    $token = '==';
                    break;

                case 'ne':
                case 'neq':
                    $token = '!=';
                    break;

                case 'lt':
                    $token = '<';
                    break;

                case 'le':
                case 'lte':
                    $token = '<=';
                    break;

                case 'gt':
                    $token = '>';
                    break;

                case 'ge':
                case 'gte':
                    $token = '>=';
                    break;

                case 'and':
                    $token = '&&';
                    break;

                case 'or':
                    $token = '||';
                    break;

                case 'not':
                    $token = '!';
                    break;

                case 'mod':
                    $token = '%';
                    break;
				
                default:
                    if ($token[0] == '$')
                    {
                        $token = $this->get_val(substr($token, 1));
                    }
                    break;
            }
        }

        if ($elseif)
        {
            return '<?php elseif (' . implode(' ', $tokens) . '): ?>';
        }
        else
        {
            return '<?php if (' . implode(' ', $tokens) . '): ?>';
        }
    }

    /**
     * 处理foreach标签
     *
     * @access  public
     * @param   string     $tag_args
     *
     * @return  string
     */
   public  function _compile_foreach_start($tag_args)
    {
        $attrs = $this->get_para($tag_args, 0);
        $arg_list = array();
        $from = $attrs['from'];
        /*
		if(isset($this->_var[$attrs['item']]) && !isset($this->_patchstack[$attrs['item']]))
        {
            $this->_patchstack[$attrs['item']] = $attrs['item'] . '_' . str_replace(array(' ', '.'), '_', microtime());
            $attrs['item'] = $this->_patchstack[$attrs['item']];
        }
        else
        {
            $this->_patchstack[$attrs['item']] = $attrs['item'];
        }
		*/
		$this->_patchstack[$attrs['item']] = $attrs['item'];
        $item = $this->get_val($attrs['item']);

        if (!empty($attrs['key']))
        {
            $key = $attrs['key'];
            $key_part = $this->get_val($key).' => ';
        }
        else
        {
            $key = null;
            $key_part = '';
        }

        if (!empty($attrs['name']))
        {
            $name = $attrs['name'];
        }
        else
        {
            $name = null;
        }

        $output = '<?php ';
      //  $output .= "\$_from = $from; if (!is_array(\$_from) && !is_object(\$_from)) { settype(\$_from, 'array'); }; \$this->push_vars('$attrs[key]', '$attrs[item]');";
		
		$output .= "\$_from = $from; if (!is_array(\$_from) && !is_object(\$_from)) { \$_from=array();}; \$this->push_vars('$attrs[key]', '$attrs[item]');";

        if (!empty($name))
        {
            $foreach_props = "\$this->_foreach['$name']";
            $output .= "{$foreach_props} = array('total' => count(\$_from), 'iteration' => 0);\n";
            $output .= "if ({$foreach_props}['total'] > 0):\n";
            $output .= "    foreach (\$_from AS $key_part$item):\n";
            $output .= "        {$foreach_props}['iteration']++;\n";
        }
        else
        {
            $output .= "if (count(\$_from)):\n";
            $output .= "    foreach (\$_from AS $key_part$item):\n";
        }
        return $output . '?>';
    }

    /**
     * 将 foreach 的 key, item 放入临时数组
     *
     * @param  mixed    $key
     * @param  mixed    $val
     *
     * @return  void
     */
    public function push_vars($key, $val)
    {
        if (!empty($key))
        {
            array_push($this->_temp_key, "\$this->_vars['$key']='" .$this->_vars[$key] . "';");
        }
        if (!empty($val))
        {
            array_push($this->_temp_val, "\$this->_vars['$val']='" .$this->_vars[$val] ."';");
        }
    }

    /**
     * 弹出临时数组的最后一个
     *
     * @return  void
     */
    public function pop_vars()
    {
        $key = array_pop($this->_temp_key);
        $val = array_pop($this->_temp_val);

        if (!empty($key))
        {
            eval($key);
        }
    }

    /**
     * 处理smarty开头的预定义变量
     *
     * @access  public
     * @param   array   $indexes
     *
     * @return  string
     */
   public  function _compile_smarty_ref(&$indexes)
    {
        /* Extract the reference name. */
        $_ref = $indexes[0];

        switch ($_ref)
        {
            case 'now':
                $compiled_ref = 'time()';
                break;

            case 'foreach':
                array_shift($indexes);
                $_var = $indexes[0];
                $_propname = $indexes[1];
                switch ($_propname)
                {
                    case 'index':
                        array_shift($indexes);
                        $compiled_ref = "(\$this->_foreach['$_var']['iteration'] - 1)";
                        break;

                    case 'first':
                        array_shift($indexes);
                        $compiled_ref = "(\$this->_foreach['$_var']['iteration'] <= 1)";
                        break;

                    case 'last':
                        array_shift($indexes);
                        $compiled_ref = "(\$this->_foreach['$_var']['iteration'] == \$this->_foreach['$_var']['total'])";
                        break;

                    case 'show':
                        array_shift($indexes);
                        $compiled_ref = "(\$this->_foreach['$_var']['total'] > 0)";
                        break;

                    default:
                        $compiled_ref = "\$this->_foreach['$_var']";
                        break;
                }
                break;

            case 'get':
                $compiled_ref = '$_GET';
                break;

            case 'post':
                $compiled_ref = '$_POST';
                break;

            case 'cookies':
                $compiled_ref = '$_COOKIE';
                break;

            case 'env':
                $compiled_ref = '$_G';
                break;

            case 'server':
                $compiled_ref = '$_SERVER';
                break;

            case 'request':
                $compiled_ref = '$_REQUEST';
                break;

            case 'session':
                $compiled_ref = '$_SESSION';
                break;		 
            default:

                break;
        }
        array_shift($indexes);

        return $compiled_ref;
    }

 

    public function str_trim($str)
    {
        /* 处理'a=b c=d k = f '类字符串，返回数组 */
        while (strpos($str, '= ') != 0)
        {
            $str = str_replace('= ', '=', $str);
        }
        while (strpos($str, ' =') != 0)
        {
            $str = str_replace(' =', '=', $str);
        }

        return explode(' ', trim($str));
    }

    public function _eval($content)
    {
        ob_start();
        eval('?' . '>' . trim($content));
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

   public  function _require($filename)
    {
        ob_start();
        include $filename;
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

   

   public function cycle($arr)
    {
        static $k, $old;

        $value = explode(',', $arr['values']);
        if ($old != $value)
        {
            $old = $value;
            $k = 0;
        }
        else
        {
            $k++;
            if (!isset($old[$k]))
            {
                $k = 0;
            }
        }

        echo $old[$k];
    }

   public function make_array($arr)
    {
        $out = '';
        foreach ($arr AS $key => $val)
        {
            if ($val{0} == '$')
            {
                $out .= $out ? ",'$key'=>$val" : "array('$key'=>$val";
            }
            else
            {
                $out .= $out ? ",'$key'=>'$val'" : "array('$key'=>'$val'";
            }
        }

        return $out . ')';
    }
	
	function cutstr($string, $length, $dot = ' ...') {
		if(strlen($string) <= $length) {
			return $string;
		}
	
		$pre = chr(1);
		$end = chr(1);
		$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), $string);
	
		$strcut = '';
		if(strtolower($this->charset) == 'utf-8') {
	
			$n = $tn = $noc = 0;
			while($n < strlen($string)) {
	
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1; $n++; $noc++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2; $n += 2; $noc += 2;
				} elseif(224 <= $t && $t <= 239) {
					$tn = 3; $n += 3; $noc += 2;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4; $n += 4; $noc += 2;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5; $n += 5; $noc += 2;
				} elseif($t == 252 || $t == 253) {
					$tn = 6; $n += 6; $noc += 2;
				} else {
					$n++;
				}
	
				if($noc >= $length) {
					break;
				}
	
			}
			if($noc > $length) {
				$n -= $tn;
			}
	
			$strcut = substr($string, 0, $n);
	
		} else {
			for($i = 0; $i < $length; $i++) {
				$strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
			}
		}
	
		$strcut = str_replace(array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
	
		$pos = strrpos($strcut, chr(1));
		if($pos !== false) {
			$strcut = substr($strcut,0,$pos);
		}
		return $strcut.$dot;
	}
	
	/*创建文件夹*/
	public function umkdir($dir)
	{
		if(empty($dir)) return false;
		$dir=str_replace(ROOT_PATH,"",$dir);
		$arr=explode("/",$dir);
		
		foreach($arr as $key=>$val)
		{
			$d="";
			for($i=0;$i<=$key;$i++)
			{
				if($arr[$i]!=""){				
					$d.=$arr[$i]."/";
				}
			} 
			if(!file_exists(ROOT_PATH.$d))
			{ 
				mkdir(ROOT_PATH.$d,0755);
			}
		}
	}
	/**
	*数学计算
	*/
	public function _math($str){
		preg_match("/equation=\"(.*)\"/iU",$str,$a);
		$eq=$a[1];
		preg_match("/format=\"(.*)\"/iU",$str,$f);
		$format="";
		if(isset($f[1])){
			$format=$f[1];
		}
		preg_match_all("/(\w+=\"[^\"]+\")/iUs",$str,$b);
		
		$temp=array();
		if(isset($b[1])){			 
			foreach($b[1] as $v){
				$e=explode("=",$v);
				$e[1]=str_replace('"','',$e[1]);
				if(defined($e[1])){
					$temp[$e[0]]=$e[1];
				}else{
					$temp[$e[0]]=is_numeric($e[1])?$e[1]:$this->get_val(substr($e[1],1));
				}
			}
			$eq=str_replace('"',"",$eq);
			$ss= preg_replace("/(\w+)/iUs",'$temp[\\1]',$eq);						  
			 eval("\$x= \"$ss\";");
			$res=$format?'<?php echo sprintf("'.$format.'",'.$x.') ?>':'<?php echo '.$x.'?>';
			return $res;			 
		}
	}
	/*url重写*/	
	public function rewriteurl($content)
	{
		if($this->rewriterule){
		foreach($this->rewriterule as $s)
		{
			$content=preg_replace($s[0],$s[1],$content);
		}
		}
		
		return $content;
	}
	
	
}


?>