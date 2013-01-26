<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
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
class PrivateMessage {
	
  private $id;
  private $sender;
  private $senderName;
  private $recipient;
  private $recipientName;
  private $subject;
  private $message;
  private $reply_to;
  private $viewed;
  private $deleted_r;
  private $deleted_s;
  private $sent;
  
  private $exists;
  private $can_access;
  
  private $senderObj;
  private $recipientObj;
  
	function __construct($row=null,$id=null) {
		
    $this->exists=false;
    $this->can_access=false;
	
	$this->senderObj=null;
	$this->recipientObj=null;
    
    if (isset($row)){
      $this->populateClass($row);
    }elseif(isset($id)){
      $this->id=$id;
    }
	}
  
  private function populateClass($row){
        
    if(isset($row['id'])) $this->id = $row['id'];
    if(isset($row['sender'])) $this->sender = $row['sender'];
    if(isset($row['recipient'])) $this->recipient = $row['recipient'];
    if(isset($row['subject'])) $this->subject = $row['subject'];
    if(isset($row['message'])) $this->message = $row['message'];
    if(isset($row['reply_to'])) $this->reply_to = $row['reply_to'];
    if(isset($row['viewed'])) $this->viewed = $row['viewed'];
    if(isset($row['deleted_r'])) $this->deleted_r = $row['deleted_r'];
    if(isset($row['deleted_s'])) $this->deleted_s = $row['deleted_s'];
    if(isset($row['sent'])) $this->sent = $row['sent'];
	
	
	if(!empty($this->sender)){
		$this->senderObj=new Character($this->sender);
		$this->senderObj->checkExistance();
	}
	
	if(!empty($this->recipient)){
		$this->recipientObj=new Character($this->recipient);
		$this->recipientObj->checkExistance();
	}
      
  }
  
  private function writeToDb(){
    
    $query="INSERT INTO private_message
            (sender,recipient,subject,message,reply_to,sent) VALUES
            ('".mysql_real_escape_string(stripcslashes($this->sender))."',
            '".mysql_real_escape_string(stripcslashes($this->recipient))."',
            '".mysql_real_escape_string(stripcslashes($this->subject))."',
            '".mysql_real_escape_string(stripcslashes($this->message))."',
            '".mysql_real_escape_string(stripcslashes($this->reply_to))."','".date("YmdHis")."')";
            
    mysql_query($query) or die(mysql_error());
    
    $this->id=mysql_insert_id();
    
  }
  

  /**
   * se $ref_id è diverso da null allora imposta il flag can_access
   */
  public function pm_superread($ref_id=null){
      
    if(isset($this->id)){
        
      if ($GLOBALS['bind_pm_char']){
        $jointo="character";
        $valname="name";
      }else{
        $jointo="account";
        $valname="username";
      }
      
      $query="SELECT pm.* , c1.$valname AS sendername, c2.$valname AS recipientname
              FROM private_message pm
              LEFT OUTER JOIN `$jointo` c1 ON c1.id = pm.sender
              LEFT OUTER JOIN `$jointo` c2 ON c2.id = pm.recipient
              WHERE pm.id = '{$this->id}'";
    
      $result=mysql_query($query) or die(mysql_error());
      
      if (mysql_num_rows($result)>0){
        $row=mysql_fetch_array($result);  
        $this->populateClass($row);
        $this->exists=true;
        if(isset($ref_id)){
          if($row['sender']==$ref_id || $row['recipient']==$ref_id)
            $this->can_access=true;
        }else{
          $this->can_access=true;
        }
      }  
    }
  }
  
  public function setViewed(){
    if(isset($this->id)){
      $query="UPDATE private_message SET viewed=1
              WHERE id='{$this->id}'";
              
      mysql_query($query) or die(mysql_error());
      
    } 
  }
  
  public function sendNew($senderId,$recipientName,$subject,$message,$reply_to){
    		
    $sender=$senderId;
    $recipient=$recipientName;	
      
    if ($GLOBALS['bind_pm_char']){
      $this->senderObj=new Character($sender);
      $this->senderObj->checkExistance();  
      $this->recipientObj=new Character(null,$recipient);
      $this->recipientObj->checkExistance(); 
      
      if (!$this->senderObj->exists())
        return "Mittente inesistente.";
    
      $recipient=$this->recipientObj->getCharId();
      
      if (!$this->recipientObj->exists())
        return "Destinatario inesistente.";
    
    }else{
      $charSenderObj=new Account(null,$sender);
      $charSenderObj->parseFromDb();   
      $charRecipienObj=new Account(null,null,$recipient);
      $charRecipienObj->parseFromDb();   
      
      if (!$charSenderObj->exists())
        return "Mittente inesistente.";
    
      $recipient=$charRecipienObj->getId();
      
      if (!$charRecipienObj->exists())
        return "Destinatario inesistente.";
    }
    
    if ($message=='')
      return "{$GLOBALS['missive']} troppo breve.";
  
    if ($reply_to!='' && $reply_to!='0'){
        
      $tmpMsg=new PrivateMessage(null,$reply_to);
      $tmpMsg->pm_superread($sender);
     
      if (!$tmpMsg->Exists() || !$tmpMsg->canAccess()){
        return "Si è verificato un errore nel rispondere al {$GLOBALS['missive']}.";
      }else{
        
        $subject=$tmpMsg->getSubject();
        $replies=explode(',',$tmpMsg->getReplyTo());
        
        for($i=0;$i<count($replies);$i++){
          $tmpMsg2=new PrivateMessage(null,$replies[$i]);
          $tmpMsg2->pm_superread($sender);
          if (!$tmpMsg2->Exists() || !$tmpMsg2->canAccess())
            unset($replies[$i]);
        }
        
        array_push($replies,$reply_to);
        $reply_string=implode(',',$replies);
      }
      
    }else{
      $reply_to='0'; 
    }
    
    $this->sender=$sender;
    $this->recipient=$recipient;
    $this->subject=$subject;
    $this->message=$message;
    $this->reply_to=$reply_string;
    
    $this->writeToDb();
  
    return "{$GLOBALS['missive']} Inviato.";  
  
  }

  public function delete($deleterID,$recursive=true){
      
    $flag1=false;
    $flag2=false;
	
	
	if($deleterID<=0) exit("Errore");
    
    if($deleterID==$this->recipient){
      $query="UPDATE private_message SET deleted_r = IF(deleted_r < 2, deleted_r+1, 2)
              WHERE id='{$this->id}'";
      mysql_query($query) or die(mysql_error());
      if (mysql_affected_rows()>0)
        $flag1=true;
    }
    if($deleterID==$this->sender){
      $query="UPDATE private_message SET deleted_s = IF(deleted_s < 2, deleted_s+1, 2)
              WHERE id='{$this->id}'";
      mysql_query($query) or die(mysql_error());
      if (mysql_affected_rows()>0)
        $flag2=true;
    }
	
	if($recursive){
		$recur=explode(",",$this->reply_to);
		
		foreach ($recur as $key => $delId) {
			if(intval($delId)<=0) continue; //se non è un valore valido lo skippo
			
			$delMsg=new PrivateMessage(null,$delId);
			$delMsg->pm_superread();
			
			$delMsg->delete($deleterID,false);
			
			
		}
		
	}
    
    if ($flag1==$receiver && $flag2==$sender)
      return true;
  
    return false;
  }
  
  public function getId(){
    return $this->id;
  }
  public function getSender(){
    return $this->sender;
  }
  public function getSenderName(){
    if (empty($this->senderObj)) {
      $this->senderObj=new Character($this->sender);
      $this->senderObj->checkExistance();
    } 
      
    return $this->senderObj->getCharName();
  }
  
  public function getSenderNameLink(){
    
    if (empty($this->senderObj)) {
      $this->senderObj=new Character($this->sender);
      $this->senderObj->checkExistance();
    } 
      
    return $this->senderObj->getCharNameLink();
  }
  
  public function getRecipient(){
    return $this->recipient;
  }
  
  public function getRecipientName(){
    if (empty($this->recipientObj)) {
      $this->recipientObj=new Character($this->recipient);
      $this->recipientObj->checkExistance();
    } 
      
    return $this->recipientObj->getCharName();
  }
  public function getRecipientNameLink(){
    
    if (empty($this->recipientObj)) {
      $this->recipientObj=new Character($this->recipient);
      $this->recipientObj->checkExistance();
    } 
      
    return $this->recipientObj->getCharNameLink();
  }
  
  public function getSubject(){
    return $this->subject;
  }
  public function getMessage(){
    return $this->message;
  }
  public function getReplyTo(){
    return $this->reply_to;
  }
  public function getReplyCount(){
  	$c=0;	
  	$n=explode(",",$this->reply_to);
	
	foreach($n as $val)
		if(intval($val)>0) $c++;;
	
	return (1+$c);
	
  }
  public function getViewed(){
    return $this->viewed;
  }
  public function getDeletedByRecipient(){
    return $this->deleted_r;
  }
  public function getDeletedBySender(){
    return $this->deleted_s;
  }
  public function getSentDate(){
    return $this->sent;
  }
  public function Exists(){
    return $this->exists;
  }
  public function canAccess(){
    return $this->can_access;
  }
  
}

/**
 * 
 */
class PrivateMessageList implements Countable{
	  
  private $recipient;  
	private $start_pag;
  private $pm_per_pag;
  private $deleted;
  private $sender;
  
  private $pm_array=array();
  private $pm_arraySize;
  
	function __construct($recipient=null,$deleted=null,$sender=null,$searchV=null) {
		
    $this->pm_arraySize=0;
     
		if ($GLOBALS['bind_pm_char']){
      $jointo="character";
      $valname="name";
    }else{
      $jointo="account";
      $valname="username";
    }
    
    if (isset($deleted)){
      $del_s="AND pm.deleted_s='$deleted'";
      $del_r="AND pm.deleted_r='$deleted'";
    }else{
      $del_s="";
      $del_r="";
    }
               
    
    if($searchV!=null && $sender!=null){
      $query="SELECT pm.*
			FROM private_message pm
			LEFT OUTER JOIN  `character` c1 ON pm.recipient = c1.id
			LEFT OUTER JOIN  `character` c2 ON pm.sender = c2.id
  			WHERE (c1.name='{$searchV}' OR c2.name='{$searchV}'
  			OR pm.message LIKE '%{$searchV}%'
  			OR pm.subject LIKE '%{$searchV}%')
  			AND 
  			((pm.sender=".$sender." AND pm.deleted_s<2)
  			OR (pm.recipient=".$sender." AND pm.deleted_r<2))
  			ORDER BY pm.id desc";
			
    }elseif ($sender!=null && $recipient!=null){
      $query="SELECT pm.*
              FROM private_message pm
              LEFT OUTER JOIN `$jointo` c
              ON pm.recipient=c.id
              WHERE ((pm.sender='$sender' $del_s )
              OR (pm.recipient='$recipient' $del_r ))
              ORDER BY pm.id desc";
    }elseif ($sender!=null){
      $query="SELECT pm.*
              FROM private_message pm
              LEFT OUTER JOIN `$jointo` c
              ON pm.recipient=c.id
              WHERE pm.sender='$sender' OR pm.recipient='$sender'
              $del_s
              ORDER BY pm.id desc";
    }else{
      $query="SELECT pm.*
              FROM private_message pm
              LEFT OUTER JOIN `$jointo` c
              ON pm.sender=c.id
              WHERE pm.recipient='$recipient' OR pm.sender='$recipient'
              $del_r
              ORDER BY pm.id desc";
    }
    
    $result=mysql_query($query) or die(mysql_error());
    
	$skipList=array();
	
    while ($row=mysql_fetch_array($result)){
      	
      $tmpPM=new PrivateMessage($row);
      
      if(in_array($row["id"], $skipList)){
      	//aggiungo alla skip list il padre
      	
      	
      	
      	$skipList=array_merge($skipList,explode(',',$tmpPM->getReplyTo()));
      	continue;
      } 		
      	
      
      //aggiungo alla skip list il padre
      $skipList=array_merge($skipList,explode(',',$tmpPM->getReplyTo()));
      
      
      $this->pm_array[]=$tmpPM;
      $this->pm_arraySize=$this->pm_arraySize + 1;    
    }

	}
	
	public function PMList(){
	  return $this->pm_array;
	}
	
	public function count(){
    return $this->pm_arraySize;
  }

}

function pm_countNew($receiver,$array=false){
	$result=mysql_query("SELECT id FROM private_message
						WHERE recipient='$receiver' AND viewed=0 AND deleted_r=0 order by id desc") or die(mysql_error());
	
	if($array){
		$arr=array(0,0);
		if(mysql_num_rows($result)>0){
			$arr[1]=mysql_num_rows($result);
			$row=mysql_fetch_array($result);
			$arr[0]=$row['id'];
		}
		
		return $arr;
	}
	
	
	return mysql_num_rows($result);
}


?>
