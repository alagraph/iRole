<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("common.php");
require_once("character_lib.php");

if(!isset($_SESSION))
{
session_start();
}
 
/**
 * 
 */
class QuestElement {
    
  private $id;
  private $id_quest; 
  private $element_name;
  private $element_id;
  private $element_px; 
  private $element_note; 
  private $create_date;  
  
  private $exp_diff;
    
  
  public function __construct($row=null,$id=null) {
      
    $this->exp_diff=0;  
      
    if (isset($id)){
      $this->id=$id;
    }  
    
    if (isset($row)){
      $this->parse($row);
    }
    
  }
  
  public function parse($row){
      
    if(isset($row)){
      
      if (isset($row['id'])) $this->id=$row['id'];
      if (isset($row['id_quest'])) $this->id_quest=$row['id_quest'];
      if (isset($row['element_id'])){
        $this->element_id=$row['element_id'];
        $char=new Character($this->element_id);
        $char->checkExistance();
        
        if(!$char->exists()) die ("Personaggio inesistente");
        
        $this->element_name=$char->getCharName();
      } 
      if (isset($row['element_px']))   $this->element_px=$row['element_px']; 
      if (isset($row['element_note'])) $this->element_note=$row['element_note'];
      if (isset($row['create_date'])){
        $this->create_date=$row['create_date'];
      }else{
        $this->create_date=date("YmdHis");
      }
      
    }
  }

  public function readFromDb(){
    if(isset($this->id)){
      
      $query="SELECT * FROM quest_element WHERE id='{$this->id}'";
      $result = mysql_query($query) or die(mysql_error());
      
      while($row=mysql_fetch_array($result)){
        $this->parse($row);
      }
      
      
    }
  }
  
  public function writeToDb(){
    
    if (isset($this->id)){ //l'id è settato, faccio l'update
      $query="UPDATE quest_element SET
              element_id='{$this->element_id}',
              element_px='{$this->element_px}',
              create_date='{$this->create_date}',
              id_quest='{$this->id_quest}',
              element_note='{$this->element_note}'
              WHERE id='{$this->id}'";      
    }else{
      $query="INSERT INTO quest_element SET
              element_id='{$this->element_id}',
              element_px='{$this->element_px}',
              create_date='{$this->create_date}',
              id_quest='{$this->id_quest}',
              element_note='{$this->element_note}'"; 
    }
    
    
    if (isset($query)){
        $result = mysql_query($query) or die(mysql_error());
        
        //aggiungo anche i px al character
        $charObj=new Character($this->element_id);
        $charObj->addPx($this->exp_diff);
        
        if (!isset($this->id))
          $this->id=mysql_insert_id();
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere
  }

  public function delete(){
  		
  	if(!isset($this->element_id) || !isset($this->id)) return;
  	
  	//altrimenti posso cancellarlo
  	$this->setExpDiff(-($this->element_px));				
  			
  	$charObj=new Character($this->element_id);
    $charObj->addPx($this->exp_diff);
    
    $query="DELETE FROM quest_element WHERE id='{$this->id}'";
	$result = mysql_query($query) or die(mysql_error());
  	
  }
  
  public function setExpDiff($arg){
    $this->exp_diff=$arg;
  }
  
  public function getId(){
    return $this->id;
  }
  
  public function getName(){
    return $this->element_name;
  }
  
  public function getNote(){
    return $this->element_note;
  }
  
  public function getPx(){
    return $this->element_px;
  }
  
  public function getQuest(){
    return $this->id_quest;
  }
  
  
  
}
 
 
/**
 * 
 */
class Quest {
    
  private $id;
  private $name; 
  private $location; 
  private $duration;
  private $create_date;
  private $created_by;
  
  private $quest_elements=array();
  
  public function __construct($row=null,$id=null) {
    
    if(isset($row)){
      $this->parse($row);
    }
    if (isset($id)){ //lo leggo da db
      $this->id=$id;
    }
  }
  
  public function parse($row){
      
    if(isset($row)){
      
      if (isset($row['id'])) $this->id=$row['id'];
      if (isset($row['name'])) $this->name=$row['name'];
      if (isset($row['location'])) $this->location=$row['location'];
      if (isset($row['create_date'])){
        $this->create_date=$row['create_date'];
      }
      else{
        $this->create_date=date("YmdHis");
      } 
      if (isset($row['duration'])) $this->duration=$row['duration'];
      if (isset($row['created_by'])){
        $this->created_by=$row['created_by'];
      }
      else{
        $this->created_by=$_SESSION['char_id'];
      } 
    }
  }
  
  public function readFromDb(){
    
    if (isset($this->id)){
      $query="SELECT * FROM quests WHERE id='{$this->id}'";
      $result= mysql_query($query) or die(mysql_error());
      
      if (mysql_num_rows($result)>0){
        $row=mysql_fetch_array($result);
        $this->parse($row);
      }
    }
  }
  
  public function writeToDb(){
    
    if (isset($this->id)){ //l'id è settato, faccio l'update
      $query="UPDATE quests SET
              name='{$this->name}',
              location='{$this->location}',
              duration='{$this->duration}',
              create_date='{$this->create_date}',
              created_by='{$this->created_by}'
              WHERE id='{$this->id}'";      
    }else{
      $query="INSERT INTO quests SET
              name='{$this->name}',
              location='{$this->location}',
              duration='{$this->duration}',
              create_date='{$this->create_date}',
              created_by='{$this->created_by}'";
    }
    
    if (isset($query)){
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere
  }
  
  public function loadElements(){
    
    if (isset($this->id)){
      
      $query="SELECT * FROM quest_element WHERE id_quest='{$this->id}'";
      $result=mysql_query($query) or die(mysql_error());
      
      while ($row=mysql_fetch_array($result)){
        
        $tmpE=new QuestElement($row);
        $this->quest_elements[$tmpE->getId()]=$tmpE;        
      }
    }
  }

  public function delete(){
  	
	if (!isset($this->id)) return;
	
	$this->loadElements();
	foreach ($this->quest_elements as $key => $value) {
		$value->delete();
	}
	
	//eventually delete the bonus for the master too
	
	//and delete myself at the end
	$query = "DELETE FROM quests WHERE id='{$this->id}'";
	mysql_query($query) or die(mysql_error());
	
	
	
  }

  public function addElement($row){
      
    $tmpE=new QuestElement($row);
    $this->quest_elements[$tmpE->getId()]=$tmpE;
    
  }
  
  public function getName(){
    return $this->name;
  }
  
  public function getMaster(){
    return $this->created_by;
  }
  
  public function getLocation(){
    return $this->location;
  }
  
  public function getId(){
    return $this->id;
  }
  
  public function getDuration(){
    return $this->duration;
  }
  
  public function getElements(){
    return $this->quest_elements;
  }
  
  public function getDate(){
    return $this->create_date;
  }
  
}

/**
 * 
 */
class QuestList {
    
    
  private $master;  
  private $char;
  private $quest_list=array();
  
  public function __construct($master=null,$char=null) {
    if (isset($master)){
      $this->master=$master;  
    }
    if (isset($char)){
      $this->char=$char;
    }
  }
  
  public function readList(){
      
    
    if (isset($this->master)){
      $query="SELECT * FROM quests WHERE created_by='{$this->master}'";
    }elseif(isset($this->char)){
      $query="SELECT q.*,
              qe.id AS QE_id,
              qe.id_quest AS QE_id_quest,
              qe.element_id AS QE_element_id,
              qe.element_px AS QE_element_px,
              qe.element_note AS QE_element_note,
              qe.create_date AS QE_create_date
              FROM quests q, quest_element qe WHERE
              q.id=qe.id_quest AND
              element_id='{$this->char}'";
    }
    
    
    $result= mysql_query($query) or die(mysql_error());
    
    while($row=mysql_fetch_array($result)){
        
      $rowE=array(
                  "id"=>$row['QE_id'],
                  "id_quest"=>$row['QE_id_quest'],
                  "element_id"=>$row['QE_element_id'],
                  "element_px"=>$row['QE_element_px'],
                  "element_note"=>$row['QE_element_note'],
                  "create_date"=>$row['QE_create_date']
                  );
        
      $tmpQ= new Quest($row);
      $tmpQ->addElement($rowE);
      $this->quest_list[]=$tmpQ;
      
    }
    
  }

  public function GetList(){
    return $this->quest_list;
  }
  
  
}




?>