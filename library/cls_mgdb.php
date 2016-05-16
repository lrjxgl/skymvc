<?php
/**
*mongodb类库 By 雷日锦 2016-05-16
*
**/
class mgdb{
	public $link;
	public $db;
	public function __construct($server="",$config=array("connect" => TRUE)){
		$this->connect($server,$config);
	}
	
	public function connect($server="",$config=array("connect" => TRUE)){
		return $this->link=new MongoClient($server,$config);
	}
	
	public function setDb($db){
		return $this->db=$this->link->$db;
	}
	public function select($table,$option=array(),&$rscount=false){
		if(!isset($option['where'])){
			$option['where']=array();
		}
		$res=$this->db->$table->find($option['where']);
		
		if(isset($option['fields'])){
			$res->fields($option['fields']);
		}
		
		if(isset($option['order'])){
			try{
				$res->sort($option['order']);
 				
			}catch(MongoCursorException $e){
				echo "error message: ".$e->getMessage()."\n";
   				 echo "error code: ".$e->getCode()."\n";

			}
		}
		
		if(isset($option['start'])){
			$res->skip($option['start']);
		}
		
		if(isset($option['limit'])){
			$res->limit($option['limit']);
		}
		$data=array();
		/*while($res->hasNext()){
			$data[]=$res->getNext();
		}
		*/
		if($res){
			foreach($res as $id=>$rs){
				 
				$rs['id']=$id;
				$data[$id]=$rs;
			}
		}
		if($rscount){
			$rscount=$res->count();
		}
		return $data;
	}
	
	public function GetCount($res){
		return $res->count($res);
	}
	
	public function selectRow($table,$option=array()){
		return $this->db->$table->findOne($option);
	}
	
	public function insert($table,$data=array()){
		$this->db->$table->insert($data);
	}
	
	public function update($table,$data,$where){
		$this->db->$table->update($where,$data);
	}
	
	public function delete($table,$where,$config=array()){
		$this->db->$table->remove($where,$config);
	}
	
	public function group($table,$keys, $initial, $reduce){
		return $this->db->$table->group($keys, $initial, $reduce);
	}
	
	public function distinct($table,$field,$where=array()){
		return $this->db->$table->distinct($field,$where);
	}
	
	public function drop($table){
		return $this->db->$table->drop();
	}
	/**建立索引**/
	public function addIndex($table,$data,$option=array()){
		$this->db->$table->createIndex($data,$option);
	}
	/*删除索引*/
	public function deleteIndex($table,$data){
		$this->db->$table->deleteIndex($data);
	}
	
	
	
}

?>