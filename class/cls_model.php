<?php
class model{
	public $base;
	public $db;
	public $table;
	public $table_all=NULL;
	public $tmpTable=false;  
	function __construct(&$base)
	{
		$this->base=$base;
		if(isset($base->db)){
			$this->db=$base->db;	
		}
			
	}
	
	public function setDb($table=NULL){
		$this->db=setDb($table?$table:$this->table);
		if($this->db){
			$this->db->base=$this->base;
		}
	}
	
	public function setTable($table,$object_id=0,$table_num=10){
		$this->setDb($table);
		if($object_id){
			$this->tmpTable=$table."_".($object_id%$table_num);
		}else{
			$this->tmpTable=$table;
		}
		return $this;
	}
	public function clearTable(){
		$this->tmpTable=false;
	}
	/*加载模型*/
	public function loadModel($model,$base=NULL){
		$this->base->loadModel($model,$base);		
		if(is_array($model)){
			foreach($model as $m){
				$this->$m=$this->base->$m;
			}
		}else{
			$this->$model=$this->base->$model;
		}
	}
	/**
	*执行sql语句
	*/
	public function query($sql){
		 
		return $this->db->query($sql);
	}
	
	public function begin(){
		 
		return $this->db->query("BEGIN");
	}
	
	public function commit(){
		  
		return $this->db->query("COMMIT");
	}
	
	public function rollback(){
		 
		return $this->db->query("rollback");
	}
	
	/**
	 * 将结果集解析成数组
	 */
	public function fetch_array($result_type=MYSQL_ASSOC){
		return $this->db->fetch_array($result_type);	
	}
	
	/**
	 * 从结果集中取一行
	 */
	public function fetch_row($result_type=PDO::FETCH_ASSOC){
		return $this->db->fetch_row($result_type);	
	}
	
	/**
	*获取全部数据
	*/
	public function getAll($sql,$cache=0,$expire=60){
		return $this->db->getAll($sql,$cache,$expire);
	}
	/**
	*获取一个字段数据
	*/
	public function getOne($sql,$cache=0,$expire=60){
		return $this->db->getOne($sql,$cache,$expire);
	}
	/**
	*获取一列数据
	*/
	public function getCols($sql,$cache=0,$expire=60){
		return $this->db->getCOls($sql,$cache,$expire);
	}
	/**
	*获取一行数据
	*/
	public function getRow($sql,$cache=0,$expire=60){
		return $this->db->getRow($sql,$cache,$expire);
	}
	/**
	*获取全部数据
	* data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function select($option=array(),&$rscount=false,$cache=0,$expire=60){
		/*
		if(!isset($option['where'])){
			 
			exit('请确认sql select where条件'.$this->table);
		}
		*/
		$data=$this->db->select($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$option,$rscount,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/**
	*获取一个字段
	*data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function selectOne($data=array(),$cache=0,$expire=60){
		$data=$this->db->selectOne($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/**
	*获取一行信息
	*data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function selectRow($data=array(),$cache=0,$expire=60){
		if(!is_array($data)){
			$t=$data;
			$data=array();
			$data['where']=$t;
		}
		$data=$this->db->selectRow($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/**
	*获取一列信息
	*data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function selectCols($data=array(),&$rscount=false,$cache=0,$expire=60){
		$data=$this->db->selectCols($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data,$rscount,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/*
	*获取记录总数
	*data array("table","where"=>array(),"order","start","pagesize","fields")
	*/
	public function getCount($w=array(),$cache=0,$expire=60){
		$data=$this->db->getCount($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$w,$cache,$expire);
		$this->clearTable();
		return $data;
	}
	/**
	*插入数据
	*data array("title"=>'aaa');
	*/
	public function insert($data){
		$data=$this->db->insert($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data);
		$this->clearTable();
		return $data;
	}
	
	/**
	 * 批量插入数据***
	**/
	public function insertMore($data){
		$table=$this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table);
		
		if(!empty($data)){
			$i=0;
			$fields="";
			foreach($data[0] as $k=>$v){
				if($i>0){
					$fields.=",";
				}
				$fields.=$k;
				$i++;
			}
			$values="";
			foreach($data as $k=>$v){
				if($k>0){
					$values.=",";
				}
				$values.="("._implode($v).") ";
			}
		}
		$sql=" insert into ".TABLE_PRE.$table." ($fields) values $values ;";
		$this->db->query($sql);
		$this->clearTable();
		return $data;
	}
	/**
	*更新数据
	*data array("title"=>'aaa');
	*where array("id"=>1)
	*/
	public function update($data,$where=""){
		if(empty($where)){
			exit("UPDate 必须 含条件");
		}
		if(empty($data)) return false;
		$data=$this->db->update($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$data,$where);
		$this->clearTable();
		return $data;
	}
	/*****
	 * 批量更新 insert into ...on duplicate key update
	 * $undata[]=array(
	 * 	"id"=>1,
	 * 	"orderindex"=>2
	 * );
	 * ****/
	
	public function updateMore($updata=array()){
		$table=$this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table);
		if(!empty($updata)){
			$sql=" insert into ".table($table)."(";
			$i=0;
			foreach($updata[0] as $k=>$v){
				if($i>0){
					$sql.=",";
				}
				$i++;
				$sql.=$k;
			}
			$sql.=") values ";
			
			foreach($updata as $k=>$v){
				if($k>0){
					$sql.=",";
				}
				$sql.="("._implode($v).") ";
			}
			$sql.="on duplicate key update ";
			$i=0;
			foreach($updata[0] as $k=>$v){
				if($i>0){
					$sql.=",";
				}
				$i++;
				$sql.=" $k=values($k) ";
			}
		 
			$res=$this->db->query($sql);
			$this->clearTable();
			return $res;
		} 
	}
	/**
	/*修改数字类型
	*/
	public function changenum($fields,$num,$w){
		if(empty($w)){
			exit("UPDate 必须 含条件");
		}
		$data=$this->db->query("UPDATE ".TABLE_PRE.($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table))." SET $fields=$fields+{$num} WHERE $w ");
		$this->clearTable();
		return $data;
	}
	/**
	*删除数据
	*where array("id"=>1)
	*/
	public function delete($where=""){
		if(empty($where)){
			exit("Delete 必须 含条件");
		}
		$data=$this->db->delete($this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table),$where);
		$this->clearTable();
		return $data;
	}
	/*最近插入的id*/
	public function insert_id(){
		return $this->db->insert_id();
	}
	
	public function getFields(){
		$table=$this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table);
		return $this->db->getAll("show columns from ".TABLE_PRE.$table."");
	}
	public function postData($unPost=array()){
		$table=$this->tmpTable?$this->tmpTable:($this->table_all?$this->table_all:$this->table);
		$fields=$this->getFields();
		if($fields){
			foreach($fields as $k=>$v){
				if($k==0) continue;
				if(in_array($v['Field'],$unPost)) continue;
				if(isset($_POST[$v['Field']])){
					$format="h";
					$len=0;
					if(preg_match("/int/i",$v['Type'])){
						$format="i";
					}elseif(preg_match("/char/i",$v['Type'])){
						$format="h";
					}elseif(preg_match("/text/i",$v['Type']) or preg_match("/blog/i",$v['Type']) ){
						$format="x";
					}elseif(preg_match("/decimal/i",$v['Type'])){
						$format="r";
						$len=7;
					}
					if($v['Field']=='starttime' or $v['Field']=='endtime'){
						$data[$v['Field']]=strtotime(post($v['Field']));
					}elseif($v['Field']=='dateline'){
						$data[$v['Field']]=time();
					}else{				
						$data[$v['Field']]=post($v['Field'],$format,$len);
					}
				}
			}
		}
		return $data;
	}
	/*关闭数据库*/
	public function close(){
		//$this->db->close();
	}
	
	
	
}

?>