<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("character_lib.php");

$logs_type_array=array(
					0 => 'unused',
					1 => 'cancellazione personaggio',
					2 => 'cancellazione account',
					3 => 'trasferimento soldi',
					4 => 'ritiro stipendio',
					5 => 'modifica stats/px/soldi',
					6 => 'acquisto oggetto',
					7 => 'trasferimento oggetto',
					8 => 'modifica documento',
					9 => 'creazione gruppo',
					10=> 'modifica gruppo',
					11=> 'cancellazione gruppo',
					12=> 'aggiunta abilita',
					13=> 'rimozione abilità'
					);
					
					
//<1000 = bindati ad account
$logs_type_arrayN=array(
					0 => 'unused',
					1 => 'cancellazione personaggio',
					2 => 'cancellazione account',
					3 => 'trasferimento soldi',
					4 => 'ritiro stipendio',
					5 => 'modifica stats/px/soldi',
					6 => 'acquisto oggetto',
					7 => 'trasferimento oggetto',
					8 => 'modifica documento',
					9 => 'creazione gruppo',
					10=> 'modifica gruppo',
					11=> 'cancellazione gruppo',
					12=> 'aggiunta abilita',
					13=> 'rimozione abilità'
					);






class Log {
    
  private $id;  
  private $author_id;
  private $victim_id;
  private $datetime;
  private $text;
  private $type;
  
	
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
      
      if (isset($row['id'])) $this->id=$row['id'];
      if (isset($row['author_id'])){
        $this->author_id=$row['author_id'];
      }else{
        $this->author_id=$_SESSION['char_id'];
      }
      if (isset($row['victim_id'])) $this->victim_id=$row['victim_id'];
      if (isset($row['datetime'])){
        $this->datetime=$row['datetime'];
      }else{
        $this->datetime=date("YmdHis");
      } 
      if (isset($row['text'])) $this->text=$row['text'];
	  if (isset($row['type'])) $this->type=$row['type'];
      
    }
  }
  
  public function newLog($author_id,$victim_id,$text,$type){
    
    $row=array();
    $row['author_id']=mysql_real_escape_string($author_id);
    $row['victim_id']=mysql_real_escape_string($victim_id);
    $row['text']=mysql_real_escape_string($text);
	$row['type']=mysql_real_escape_string($type);
    
    $this->parse($row);
    return $this->writeToDb();
    
  }
  
  private function writeToDb(){
    
    if (isset($this->id)){
      $query="UPDATE logs SET
              author_id='{$this->author_id}',
              victim_id='{$this->victim_id}',
              datetime='{$this->datetime}',
              text='{$this->text}',
              `type`='{$this->type}'
              WHERE id='{$this->id}'";
    }else{
      $query="INSERT INTO logs SET
              author_id='{$this->author_id}',
              victim_id='{$this->victim_id}',
              datetime='{$this->datetime}',
              text='{$this->text}',
              `type`='{$this->type}'";
    }
    
    if (isset($query)){
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere    
  }
  
  private function readFromDb(){
    
    if (isset($this->id)){
      $query="SELECT * FROM logs WHERE id='{$this->id}'";
      $result=mysql_query($query) or die(mysql_error());
      
      if (mysql_num_rows($result)>0){
        $row=mysql_fetch_array($result);
        $this->parse($row);
      }
    }  
  }
  
  public function getId(){
    return $this->id;
  }
  public function getDate(){
    return $this->datetime;
  }
  public function getAuthorId(){
    return $this->author_id;
  }
  public function getVictimId(){
    return $this->victim_id;
  }
  public function getText(){
    return $this->text;
  }
  public function getType(){
    return $this->type;
  }
  
}


/**
 * 
 */
class LogList {
	    
	private $logs=array();
  private $logs_ip=array(); 
	
	public function __construct() {
		
	}
  
  public function readFromDb($author=null,$victim=null,$table=0,$type=null,$last=false){
      
    //$table=0 = logs ip
    //$table=1 = logs generici
    
    //$auth_sel="author_id IN (SELECT c1.id FROM  `character` c1,  `character` c2 WHERE c2.id='{$author}' AND c2.account = c1.account)";
    $auth_sel="author_id ='{$author}'";
    
    $sel="";  
    if(isset($author)){
      $sel=$auth_sel;
    }
    if(isset($victim)){
      $sel="victim_id='{$victim}'";
    }
    if(isset($author) && isset($victim)){
      $sel="$auth_sel AND victim_id='{$victim}'";
    }
    if(isset($type) && $type!=0){
      if($sel!='')	
      	$sel.=" AND type='{$type}'";
	  else
	  	$sel="type='{$type}'";
    }
	if(isset($last)){
		$sel.=" ORDER BY datetime desc LIMIT 0,1";	
	}
    
    if ($table==0){
      $query="SELECT * FROM logs_ip WHERE acc_id='{$author}'";
    }else{
      $query="SELECT * FROM logs WHERE $sel";
    }
    
    $result=mysql_query($query) or die (mysql_error());
    
    while($row=mysql_fetch_array($result)){
      
      if ($table==0){
        $tmpLog=new LogIp($row);
        $this->logs_ip[]=$tmpLog; 
      }else{
        $tmpLog=new Log($row);
        $this->logs[]=$tmpLog; 
      }
           
    }
    
  }
  
  public function getLogs(){
    return $this->logs;
  }
  
  public function getLogsIp(){
    return $this->logs_ip;
  }
  
  
}

/**
 * 
 */
class LogIp {
	
  private $id;
  private $acc_id;
  private $datetime;
  private $address;
  private $shared_with;
  private $useragent;  
	
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
      
      if (isset($row['id'])) $this->id=$row['id'];
      if (isset($row['acc_id'])){
        $this->acc_id=$row['acc_id'];
      }else{
        $this->acc_id=$_SESSION['acc_id'];
      }
      if (isset($row['datetime'])){
        $this->datetime=$row['datetime'];
      }else{
        $this->datetime=date("YmdHis");
      } 
      if (isset($row['address'])){
        $this->address=$row['address'];
      }else{
        $this->address=$_SERVER['REMOTE_ADDR'];
      }
      if (isset($row['shared_with'])){
        $this->shared_with=$row['shared_with'];
      }else{
        $this->shared_with='';
      }
      if (isset($row['useragent'])){
        $this->useragent=$row['useragent'];
      }else{
        $this->useragent=$_SERVER['HTTP_USER_AGENT'];
      } 
    }
  }
  
  public function writeToDb(){
    
    if (isset($this->id)){
      $query="UPDATE logs_ip SET
              acc_id='{$this->acc_id}',
              datetime='{$this->datetime}',
              address='{$this->address}',
              shared_with='{$this->shared_with}',
              useragent='{$this->useragent}',
              WHERE id='{$this->id}'";
    }else{
      $query="INSERT INTO logs_ip SET
              acc_id='{$this->acc_id}',
              datetime='{$this->datetime}',
              address='{$this->address}',
              shared_with='{$this->shared_with}',
              useragent='{$this->useragent}'";
    }
    
    if (isset($query)){
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere    
  }
  
  public function readFromDb(){
    
    if (isset($this->id)){
      $query="SELECT * FROM logs_ip WHERE id='{$this->id}'";
      $result=mysql_query($query) or die(mysql_error());
      
      if (mysql_num_rows($result)>0){
        $row=mysql_fetch_array($result);
        $this->parse($row);
      }
    }  
  }

  
  public function getId(){
    return $this->id;
  }
  public function getDate(){
    return $this->datetime;
  }
  public function getIp(){
    return $this->address;
  }
  public function getUserAgent(){
    return $this->useragent;
  }
  public function getAccId(){
    return $this->acc_id;
  }
  
}



?>