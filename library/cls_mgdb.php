<?php
/**
*mongodb类库 By 雷日锦 2016-05-16
*
**/
if(!function_exists("mgdb")){
	function mgdb($table=""){
		require_once "skymvc/library/cls_mgdb.php";
		$mgdb=new mgdb('mongodb://localhost:27017');
		return $mgdb;
	}
}
class mgdb{
	public $manager;
	public $writeConcern;
	public $database="db";
	public $table; 
	public function __construct($server='mongodb://localhost:27017',$database="db",$config=array("connect" => TRUE)){
		$this->manager= new MongoDB\Driver\Manager($server);
		$this->database=$database;
		$this->writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100);
	}
	
	public function connect($server="mongodb://localhost:27017",$database="db",$config=array("connect" => TRUE)){
		$this->manager= new MongoDB\Driver\Manager($server);
		$this->database=$database;
	}
	
	 
	public function select($table,$option=array(),&$rscount=false){
		$filter=isset($option['where'])?$option['where']:array();
		if(isset($filter['_id'])){
			$filter['_id']=new MongoDB\BSON\ObjectId($filter['_id']);
		} 
		$option=array(
			"limit"=>isset($option['limit'])?$option['limit']:0,
			"skip"=>isset($option['start'])?$option['start']:0,
			"projection"=>isset($option['fields'])?$option['fields']:array(),
			"sort"=>isset($option['order'])?$option['order']:array()
		);
		$query = new MongoDB\Driver\Query($filter, $option);
		$readPreference = new MongoDB\Driver\ReadPreference(MongoDB\Driver\ReadPreference::RP_PRIMARY);
		$cursor = $this->manager->executeQuery($this->database.'.'.$table, $query,$readPreference);
		$cursor->setTypeMap(['root' => 'array']);
		//查询总数
		$commands = [
	        'count' => $table,
	        'query' => $filter
	    ];
	    $command = new \MongoDB\Driver\Command($commands);
	    $ccrs = $this->manager->executeCommand($this->database, $command);
	    $info = $ccrs->toArray();
	    $rscount = $info[0]->n; 
	    if($rscount<=0){
	    	return false;
	    } 
		foreach($cursor as $row){
			$row['_id']=(string)$row['_id'];
			$data[]=$row;
		}
		return $data;
	}
	
	public function selectRow($table,$option=array()){
		
		$filter=isset($option['where'])?$option['where']:array();
		if(isset($filter['_id'])){
			$filter['_id']=new MongoDB\BSON\ObjectId($filter['_id']);
		} 
		$option=array(
			"limit"=>1,
			"skip"=>0,
			"projection"=>isset($option['fields'])?$option['fields']:array(),
			"sort"=>isset($option['order'])?$option['order']:array()
		);
		$query = new MongoDB\Driver\Query($filter, $option);
		$readPreference = new MongoDB\Driver\ReadPreference(MongoDB\Driver\ReadPreference::RP_PRIMARY);
		$cursor = $this->manager->executeQuery($this->database.'.'.$table, $query,$readPreference);
		$cursor->setTypeMap(['root' => 'array']);
		foreach($cursor as $row){
			$row['_id']=(string)$row['_id'];
			return $row;
		}
		 
	}
	
	public function selectCols($table,$option=array(),&$rscount=false){
		$res=$this->select($table,$option,$rscount);
		$data=array();
		 
		if(!empty($res)){
			foreach($res as $rs){
				$data[]=array_shift($rs);
			}
		}
		return $data;
	}
	
	public function selectOne($table,$option=array()){
		$filter=isset($option['where'])?$option['where']:array();
		if(isset($filter['_id'])){
			$filter['_id']=new MongoDB\BSON\ObjectId($filter['_id']);
		} 
		$option=array(
			"limit"=>1,
			"skip"=>0,
			"projection"=>isset($option['fields'])?$option['fields']:array(),
			"sort"=>isset($option['order'])?$option['order']:array()
		);
		$query = new MongoDB\Driver\Query($filter, $option);
		$readPreference = new MongoDB\Driver\ReadPreference(MongoDB\Driver\ReadPreference::RP_PRIMARY);
		$cursor = $this->manager->executeQuery($this->database.'.'.$table, $query,$readPreference);
		$cursor->setTypeMap(['root' => 'array']);
		foreach($cursor as $row){
			$row['_id']=(string)$row['_id'];
			return array_shift($row);
		}
		 
	}
	
	public function query($table,$config=array()){
			
		$query = new MongoDB\Driver\Query($config['filter'], $config['options']);
		$readPreference = new MongoDB\Driver\ReadPreference(MongoDB\Driver\ReadPreference::RP_PRIMARY);
		$cursor = $this->manager->executeQuery($this->database.'.'.$table, $query,$readPreference);
		$cursor->setTypeMap(['root' => 'array']);
		return $cursor;	 
	}
	
	public function insert($table,$data=array(),$multi=false){
		$bulk = new MongoDB\Driver\BulkWrite();
		$ids=array();
		if($multi){
			foreach($data as $v){
				$ids[]=$bulk->insert($v);
			}
		}else{
			$ids[]=$bulk->insert($data);
		}
		
		$result = $this->manager->executeBulkWrite($this->database.'.'.$table, $bulk, $this->writeConcern);
		if($multi){
			return $ids;
		}else{
			return $ids[0];
		}
	 
	}
	
	public function update($table,$data,$where,$options=array()){
		$bulk = new MongoDB\Driver\BulkWrite();
		if(isset($where['_id'])){
			$where['_id']=new MongoDB\BSON\ObjectId($where['_id']);
		} 
		$bulk->update($where,$data);
		$result = $this->manager->executeBulkWrite($this->database.'.'.$table, $bulk, $this->writeConcern);
		return $result; 
	}
	
	public function delete($table,$where,$config=array()){
		$bulk = new MongoDB\Driver\BulkWrite();
		if(isset($where['_id'])){
			$where['_id']=new MongoDB\BSON\ObjectId($where['_id']);
		} 		 
		$res=$bulk->delete($where,$config);
		$result = $this->manager->executeBulkWrite($this->database.'.'.$table, $bulk);
		return $result; 
	}
	
	public function postData($fields=""){
		$arr=explode(",",$fields);
		$post=stripslashes_deep($_POST);
		$data=array();
		foreach($arr as $k){
			if(isset($post[$k])){
				$data[$k]=$post[$k];
			}
		}
		return $data;
	}
		 
}

?>