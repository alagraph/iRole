<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("common.php");
require_once("group_lib.php");

if(!isset($_SESSION))
{
session_start(); 
}

/**
 * 
 */
class Ban {
	  
  private $id;
  private $author_accid;
  private $victim_accid;
  private $banned_date;
  private $banned_until;
  private $reason;
  
  private $status; //creazione, aumento, diminuzione(comprende annullamento)
  private $authorname;
	
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
      if (isset($row['author_accid'])){
        $this->author_accid=$row['author_accid'];
      }else{
        $this->author_accid=$_SESSION['id'];
      }
      if (isset($row['victim_accid'])) $this->victim_accid=$row['victim_accid'];
      if (isset($row['banned_date'])){
        $this->banned_date=$row['banned_date'];
      }else{
        $this->banned_date=date("YmdHis");        
      }
      if (isset($row['banned_until'])){
        $this->banned_until=$row['banned_until'];
      }else{
        $this->banned_until=date("YmdHis");        
      }
      if (isset($row['reason'])) $this->reason=$row['reason'];
      
      if (isset($row['authorname'])) $this->authorname=$row['authorname'];
      
    }
  }
  
  public function createBan($authorAccId,
                            $victimAccId,
                            $banUntil,
                            $banReason){
    $row=array();  
    $row['author_accid']=$authorAccId;
    $row['victim_accid']=$victimAccId;
    $row['banned_until']=$banUntil;
    $row['reason']=$banReason;
    
    $this->parse($row);
    
    return $this->writeToDb();
                              
  }
  
  public function writeToDb(){
    
    if (isset($this->id)){ //l'id è settato, faccio l'update
      $query="UPDATE bans SET
              author_accid='{$this->author_accid}',
              victim_accid='{$this->victim_accid}',
              banned_date='{$this->banned_date}',
              banned_until='{$this->banned_until}',
              reason='{$this->reason}'
              WHERE id='{$this->id}'";      
    }else{
      $query="INSERT INTO bans SET
              author_accid='{$this->author_accid}',
              victim_accid='{$this->victim_accid}',
              banned_date='{$this->banned_date}',
              banned_until='{$this->banned_until}',
              reason='{$this->reason}'"; 
    }
    
    if (isset($query)){
          
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere
    
  }
  
  public function setStatus($status){
    $this->status=$status;    
  }
  
  public function getStatus(){
    return $this->status;
  }
  
  public function getStatusString(){
    switch ($this->status) {
          case 1: //creazione
              $retStr="Creazione Ban";
            break;
          case 2:
              $retStr="Prolungamento Ban esistente";
            break;
          case 3:
              $retStr="Riduzione Ban esistente";
            break;
          case 4:
              $retStr="Annullamento Ban esistente";
            break;   
          default:
              $retStr="Azione sconosciuta";
            break;
    }
    return $retStr;
  }
  public function getId(){
    return $this->id;
  }
  public function getAuthorId(){
    return $this->author_accid;
  }
  public function getVictimId(){
    return $this->victim_accid;
  }
  public function getBanDate(){
    return $this->banned_date;
  }
  public function getBanUntil(){
    return $this->banned_until;
  }
  public function getBanReason(){
    return $this->reason;
  }
  public function getAuthorAccName(){
    return $this->authorname;
  }
  public function isActive(){
      
    if( date("YmdHis",strtotime($this->banned_until)) > date("YmdHis"))  
      return true;
    return false;
  }
  
}

/**
 * 
 */
class BanList {
	
  protected $list=array();
  protected $listsize;
  
  protected $lastBan;
  
  public function __construct() {
    $this->init();
    $this->lastBan=null;
  }
  
  private function init(){
    $this->listsize=0;
  }
  
  public function addBan($tmpBan){
    
    $curdate=date("YmdHis");
    if(isset($this->lastBan)){
      $lastActiveBan=date("YmdHis",strtotime($this->lastBan->getBanUntil()));
    }else{
      $lastActiveBan=date("YmdHis",strtotime(0));
    }
    $currentBan=date("YmdHis",strtotime($tmpBan->getBanUntil()));
    
    if($currentBan > $lastActiveBan &&  $lastActiveBan<=$curdate){
        // è una creazione della pena
        $tmpBan->setStatus(1);
    }elseif($currentBan > $lastActiveBan &&  $lastActiveBan>$curdate){
        // è una maggiorazione della pena
        $tmpBan->setStatus(2);
    }elseif($currentBan < $lastActiveBan &&  $currentBan>$curdate){
        // è una diminuzione della pena
        $tmpBan->setStatus(3);
    }elseif($currentBan < $curdate &&  $lastActiveBan>$curdate){
        // è un annullamento della pena
        $tmpBan->setStatus(4);
    }else{
        //mossa stupida. Ha annullato una cosa già annullata.  
        $tmpBan->setStatus(5);
    }
      
    $lastActiveBan=$tmpBan->getBanUntil(); //il nuovo until
      
    $this->list[]=& $tmpBan;
    $this->lastBan=& $tmpBan;
    $this->listsize++;
      
  }
  
  public function populateList($victim=null){
    
    
    if(isset($victim) && $victim>0){
      $sel=" WHERE b.victim_accid='{$victim}'";
    }
    
    $query="SELECT b.*,a.username as authorname
            FROM bans b
            INNER JOIN `account` a
            ON a.id=b.author_accid
            ".$sel;
    
    $result = mysql_query($query) or die(mysql_error());
      
    
    while ($row=mysql_fetch_array($result)){
      
      $tmpBan=new Ban($row);
      $this->addBan($tmpBan);
    }
    
  }
  
  public function getList(){
    return $this->list;
  }
  public function getListSize(){
    return $this->listsize;
  }
  public function getLastBan(){
    return $this->lastBan;
  }
  
}
