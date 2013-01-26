<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("common.php");
require_once("character_lib.php");
require_once("logs_lib.php");

if(!isset($_SESSION))
{
session_start(); 
}

/**
 * 
 */
class Document  {
	
	private $id;
	private $name;
	private $content;
	private $edit_level;
	private $last_edit_time;
	private $last_edit_by;
	
	public function __construct($row=null,$id=null) {
      
	    if (isset($id)){
	      $this->id=$id;
	    }
	    if (isset($row)){
	      $this->parse($row);
	    }   
  	}
  
	private function parse($row){
	      
	    if(isset($row)){
	      
	      if (isset($row['id']) && $row['id']!="") $this->id=$row['id'];
	      if (isset($row['name'])) $this->name=$row['name'];
	     
	      if (isset($row['content'])) $this->content=$row['content'];
	      if (isset($row['edit_level'])) $this->edit_level=$row['edit_level'];
	      
	      if (isset($row['last_edit_time'])){
	        $this->last_edit_time=$row['last_edit_time'];
	      }else{
	        $this->last_edit_time=date("YmdHis");        
	      }
	     
	     if (isset($row['last_edit_by'])){
	        $this->last_edit_by=$row['last_edit_by'];
	      }else{
	        $this->last_edit_by=0;        
	      }
	      
	    }
	}
	  
	public function editDoc($name,$content,$charObj,$edit_level=null){
	    	
		//se this->id è settato è una modifica, altrimenti è una creazione	
		//$charObj=new Character();	
		
		if(isset($this->id)){
			$this->readFromDb();
			$strLog="Modifica documento \"$this->name\"";
		}else{
			$strLog="Creazione documento \"$name\"";
		}
		//echo $strLog;
			
	    $row=array();  
	    $row['name']=$name;
	    $row['content']=$content;
	    $row['last_edit_by']=$charObj->Account()->getId();
		
		if (isset($edit_level)){
			$row['edit_level']=$edit_level;
		}else{
			$row['edit_level']=$this->edit_level;
		}
	    
	    $this->parse($row);
	    
	    if($this->writeToDb()){
	    		
	    	$log= new Log();
			$log->newLog($charObj->Account()->getId(), $this->id, $strLog, 8);
			 return true;
	    }
		
		return false;
	                              
	}

	public function readFromDb(){
		
		if (!isset($this->id))
			return false;
		
		$query="SELECT * FROM docs WHERE id='{$this->id}'";
		
		$result = mysql_query($query) or die(mysql_error());
		
		if(mysql_num_rows($result)>0){
			$this->parse(mysql_fetch_array($result));
			return true;		
		}
		
		return false;
		
		
	}

	public function writeToDb(){
    
	    if (isset($this->id)){ //l'id è settato, faccio l'update
	      $query="UPDATE docs SET
	              name='{$this->name}',
	              content='{$this->content}',
	              edit_level='{$this->edit_level}',
	              last_edit_time='{$this->last_edit_time}',
	              last_edit_by='{$this->last_edit_by}'
	              WHERE id='{$this->id}'";      
	    }else{
	      $query="INSERT INTO docs SET
	              name='{$this->name}',
	              content='{$this->content}',
	              edit_level='{$this->edit_level}',
	              last_edit_time='{$this->last_edit_time}',
	              last_edit_by='{$this->last_edit_by}'"; 
	    }
	    
	    if (isset($query)){
	          
	        $result = mysql_query($query) or die(mysql_error());
	        if (!isset($this->id))
	          $this->id=mysql_insert_id();
	        return true;
	      }
	    
	    return false; //ritorno -1 quando non son riuscito a scrivere
    }
	
	public function deleteDoc($myAcc){
		 if (isset($this->id)){ //
		 	$query="DELETE FROM docs
		 			WHERE id='{$this->id}'";
					
			 $result = mysql_query($query) or die(mysql_error());
			 
			 $log= new Log();
			 $log->newLog($myAcc->getId(), $this->id, "Cancellazione documento \"{$this->name}\" (id: {$this->id}) da parte di {$myAcc->getUsername()} (id: {$myAcc->getId()})", 8);
			 
			 return true;
		 }
		 
		 return false;
		
	}
	
	// GETTERS
	
	public function getId(){
		return $this->id;
	}
	public function getName(){
		return $this->name;
	}
	public function getContent(){
		return $this->content;
	}
	public function getEditLevel(){
		return $this->edit_level;
	}
	public function getLastEditTime(){
		return $this->last_edit_time;
	}
	public function getLastEditBy(){
		return $this->last_edit_by;
	}
	
}

/**
 * 
 */
class DocList {
		
	protected $list=array();
  	protected $listsize;
  
	function __construct() {
		$this->listsize=0;
	}
	
	
	public function populateList(){
			
	    $query="SELECT *
	            FROM docs d
	            ";
	    
	    $result = mysql_query($query) or die(mysql_error());
	      
	    
	    while ($row=mysql_fetch_array($result)){
	      
	      $tmpDoc=new Document($row);
	      $this->list[]=$tmpDoc;
		  $this->listsize++;
	    }
	    
	  }
	  
	  public function getList(){
	    return $this->list;
	  }
	  public function getListSize(){
	    return $this->listsize;
	  }
		
	
	
}





?>