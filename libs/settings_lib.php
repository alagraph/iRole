<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */




class ConfigCharX{
	
  private $id;
  private $name;
  private $selfedit;
  private $selfview;
  private $view_minlv;
  private $edit_minlv;
  private $view_minmst;
  private $edit_minmst;
  
  public function __construct($row=null,$id=null) {
      
    $this->selfedit=null;
	$this->selfview=null;
	
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
	  if (isset($row['name']) && $row['name']!=""){
	  	$this->name=$row['name'];
	  }
	  if(isset($row['selfedit'])){
	  	if(intval($row['selfedit'])==1){
			$this->selfedit=1;
		}else{
			$this->selfedit=0;
		}
	  }elseif($this->selfedit==null){
	  	$this->selfedit=0;
	  }
	  if(isset($row['selfview'])){
	  	if(intval($row['selfview'])==1){
			$this->selfview=1;
		}else{
			$this->selfview=0;
		}
	  }elseif($this->selfview==null){
	  	$this->selfview=0;
	  }
	  if (isset($row['view_minlv']) && $row['view_minlv']!=""){
	  	$this->view_minlv=$this->chkRangeLv($row['view_minlv'],0);
	  }
	  if (isset($row['edit_minlv']) && $row['edit_minlv']!=""){
	  	$this->edit_minlv=$this->chkRangeLv($row['edit_minlv'],0);
	  }
	  if (isset($row['view_minmst']) && $row['view_minmst']!=""){
	  	$this->view_minmst=$this->chkRangeLv($row['view_minmst'],1);
	  }
	  if (isset($row['edit_minmst']) && $row['edit_minmst']!=""){
	  	$this->edit_minmst=$this->chkRangeLv($row['edit_minmst'],1);
	  }

    }
  }
  
  public function writeToDb(){
    
    if (isset($this->id)){ //l'id è settato, faccio l'update
      $query="UPDATE config_charx SET
              name='{$this->name}',
              selfedit='{$this->selfedit}',
              selfview='{$this->selfview}',
              view_minlv='{$this->view_minlv}',
              edit_minlv='{$this->edit_minlv}',
              view_minmst='{$this->view_minmst}',
              edit_minmst='{$this->edit_minmst}'
              WHERE id='{$this->id}'";      
    }else{
      $query="INSERT INTO config_charx SET
              name='{$this->name}',
              selfedit='{$this->selfedit}',
              selfview='{$this->selfview}',
              view_minlv='{$this->view_minlv}',
              edit_minlv='{$this->edit_minlv}',
              view_minmst='{$this->view_minmst}',
              edit_minmst='{$this->edit_minmst}'"; 
    }
    
    if (isset($query)){
          
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere
    
  }

  private function chkRangeLv($value,$type){
  	
	$ref_arr=$GLOBALS['types_levels'][$type];
		
	intval($value);
	if($value<0)
		$value=0;
	if($value>count($ref_arr)-1)
		$value=count($ref_arr)-1;
			
	return $value;
  }
  
  public function getId(){
  	return $this->id;
  }
  public function getName(){
  	return $this->name;
  }
  public function getSelfedit(){
  	return $this->selfedit;
  }
  public function setSelfedit($v){
  	$row=array();	
  	$row['selfedit']=$v;
  	$this->parse($row);
  }
  public function getSelfview(){
  	return $this->selfview;
  }
  public function setSelfview($v){
  	$row=array();	
  	$row['selfview']=$v;
  	$this->parse($row);
  }
  public function getViewMinLevel(){
  	return $this->view_minlv;
  }
  public function setViewMinLevel($v){
  	$row=array();	
  	$row['view_minlv']=$v;
  	$this->parse($row);	
  }
  public function getEditMinLevel(){
  	return $this->edit_minlv;
  }
  public function setEditMinLevel($v){
  	$row=array();	
  	$row['edit_minlv']=$v;
  	$this->parse($row);	
  }
  public function getViewMinMaster(){
  	return $this->view_minmst;
  }
  public function setViewMinMaster($v){
  	$row=array();	
  	$row['view_minmst']=$v;
  	$this->parse($row);		
  }
  public function getEditMinMaster(){
  	return $this->edit_minmst;
  }
  public function setEditMinMaster($v){
  	$row=array();	
  	$row['edit_minmst']=$v;
  	$this->parse($row);		
  }
	
}



class ConfigCharXList {
  
  private $CharXList=array();
  
  
  public function __construct($load=true) {
    
	if($load){
		$this->loadCharX();
	}
  }
  
  public function loadCharX(){
  		
  	$config_load_query="SELECT * FROM config_charx";
	  
	$config_load_result=mysql_query($config_load_query) or die(mysql_error());
	  
	while($config_row=mysql_fetch_array($config_load_result)){
		$tmpCharX=new ConfigCharX($config_row);
		$this->CharXList[$tmpCharX->getId()]=$tmpCharX;
	}
	
  }
  
  public function getCharXList(){
  	
	if(empty($this->CharXList))
		$this->loadCharX();
		
	return $this->CharXList;
	
  }
  
}


/**
 * 
 */
class UserRight {
  	
  private $id;
  private $type;
  private $var_name;
  private $value;
  private $description;	
  
  public function __construct($row=null,$id=null) {
      
    if (isset($id)){
      $this->id=$id;
    }
    if (isset($row)){
      try{
      	$this->parse($row);
	  }catch(Exception $e){
	  	die($e->getMessage());
	  }
    }   
  }
  
  private function chkRangeLv($value){
  	
	if(isset($this->type)){	
	  	$ref_arr=$GLOBALS['types_levels'][$this->type];
		
		intval($value);
		if($value<0)
			$value=0;
		if($value>count($ref_arr)-1)
			$value=count($ref_arr)-1;
			
		return $value;
	}
	return 1000;
  }
  
  private function parse($row){
      
    if(isset($row)){
      
      if (isset($row['id']) && $row['id']!="") $this->id=$row['id'];
	  if (isset($row['type']) && $row['type']!=""){
	  	$this->type=intval($row['type']);
		if($this->type<0)
			$this->type=0;
		if($this->type>count($GLOBALS['types_levels'])-1)
			$this->type=count($GLOBALS['types_levels'])-1;
			
	  }
	  if (isset($row['var_name']) && $row['var_name']!=""){
	  	$this->var_name=$row['var_name'];
	  }else{
	  	//throw new Exception("Errore, nome variabile nullo.");	
	  }
	  if (isset($row['value']) && $row['value']!=""){
	  	$this->value=$this->chkRangeLv($row['value']);
	  	
	  }else{
	  	//throw new Exception("Errore, valore non accettato.");
	  }

      if (isset($row['description'])) $this->description=$row['description'];
      
    }
  }
  
  public function setValue($value){
  	
	if (isset($this->id)){
		$this->value=$this->chkRangeLv($value);
		
		$query="UPDATE config_userrights SET value='{$this->value}' WHERE id='{$this->id}'";
		$result = mysql_query($query) or die(mysql_error());
		
		return true;  
	}
	
	return false;
  }
  
  public function writeToDb(){
    
    if (isset($this->id)){ //l'id è settato, faccio l'update
      $query="UPDATE config_userrights SET
              type='{$this->type}',
              var_name='{$this->var_name}',
              value='{$this->value}',
              description='{$this->description}'
              WHERE id='{$this->id}'";      
    }else{
      $query="INSERT INTO config_userrights SET
              type='{$this->type}',
              var_name='{$this->var_name}',
              value='{$this->value}',
              description='{$this->description}'"; 
    }
    
    if (isset($query)){
          
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere
    
  }
  
  public function getId(){
  	return $this->id;
  }
  public function getType(){
  	return $this->type;
  }
  public function getVarName(){
  	return $this->var_name;
  }
  public function getValue(){
  	return $this->value;
  }
  public function getDescription(){
  	return $this->description;
  }
  
}


/**
 * 
 */
class UserRightsList {
  
  private $RightsList=array();
  
  
  public function __construct($load=true) {
    
	if($load){
		$this->loadRights();
	}
  }
  
  public function loadRights(){
  		
  	$config_load_query="SELECT * FROM config_userrights ORDER BY type";
	  
	$config_load_result=mysql_query($config_load_query) or die(mysql_error());
	  
	while($config_row=mysql_fetch_array($config_load_result)){
		$tmpRight=new UserRight($config_row);
		$this->RightsList[$tmpRight->getId()]=$tmpRight;
	}
	
  }
  
  public function getRights(){
  	
	if(empty($this->RightsList))
		$this->loadRights();
		
	return $this->RightsList;
	
  }
  
}





?>