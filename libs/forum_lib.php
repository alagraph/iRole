<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("common.php");
require_once("character_lib.php");
require_once("bbcode_parser.php");

if(!isset($_SESSION))
{
	session_start();
}

/**
 * 
 */
class ForumPost {
	
	private $id;
	private $forum_board; 
	private $author; 
	private $title; 
	private $message; 
	private $post_father;
	private $post_date;
	private $last_reply; 
	private $sticky; 
	private $locked;
	private $last_edit;
	private $last_editby;
	
	private $author_name;
	
	private $children=array();
	private $lastchild=array();
	private $numchild;
	
	
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
			if (isset($row['forum_board'])) $this->forum_board=$row['forum_board'];
			if (isset($row['author'])) $this->author=$row['author'];
			if (isset($row['title'])) $this->title=$row['title'];
			if (isset($row['message'])) $this->message=$row['message'];
			
			if (isset($row['post_father'])){
				$this->post_father=$row['post_father'];
			}else{
				$this->post_father=0;
			}
			if (isset($row['post_date'])){
				$this->post_date=$row['post_date'];
			}else{
				$this->post_date=date("YmdHis");
			}
			if (isset($row['last_reply'])){
				$this->last_reply=$row['last_reply'];
			}else{
				$this->last_reply=date("YmdHis");
			}
			if (isset($row['last_edit'])){
				$this->last_edit=$row['last_edit'];
			}else{
				$this->last_edit=date("YmdHis");
			}
			if (isset($row['last_editby'])){
				$this->last_editby=$row['last_editby'];
			}else{
				$this->last_editby=0;
			}
			if (isset($row['sticky'])) $this->sticky=$row['sticky'];
			if (isset($row['locked'])) $this->locked=$row['locked'];
			
			if (isset($row['author_name'])) $this->author_name=$row['author_name'];
			
		}
	}
	
	public function writeToDb(){
		
    if (isset($this->id)){ //l'id è settato, faccio l'update
    $query="UPDATE forum_post SET
    forum_board='{$this->forum_board}',
    author='{$this->author}',
    title='".mysql_real_escape_string($this->title)."',
    message='".mysql_real_escape_string($this->message)."',
    post_father='{$this->post_father}',
    post_date='{$this->post_date}',
    last_reply='{$this->last_reply}',
    last_edit='{$this->last_edit}',
    last_editby='{$this->last_editby}',
    sticky='{$this->sticky}',
    locked='{$this->locked}'
    WHERE id='{$this->id}'";      
  }else{
  	$query="INSERT INTO forum_post SET
  	forum_board='{$this->forum_board}',
  	author='{$this->author}',
  	title='".mysql_real_escape_string($this->title)."',
  	message='".mysql_real_escape_string($this->message)."',
  	post_father='{$this->post_father}',
  	post_date='{$this->post_date}',
  	last_reply='{$this->last_reply}',
  	last_edit='{$this->last_edit}',
  	last_editby='{$this->last_editby}',
  	sticky='{$this->sticky}',
  	locked='{$this->locked}'"; 
  }
  
  
  
  if (isset($query)){
  	$result = mysql_query($query) or die(mysql_error());
  	if (!isset($this->id))
  		$this->id=mysql_insert_id();
  	
  	if ($this->post_father!=0)
  		mysql_query("UPDATE forum_post SET last_reply='{$this->last_reply}' WHERE id='{$this->post_father}'") or die(mysql_error());  
  	
  	return 0;
  }
  
    return -1; //ritorno -1 quando non son riuscito a scrivere
  }

  public function readFromDb(){
  	if(isset($this->id)){
  		
  		$query="SELECT c.name AS author_name, f.*
  		FROM forum_post f, `character` c
  		WHERE f.author=c.id
  		AND f.id='{$this->id}'";
  		
  		$result = mysql_query($query) or die(mysql_error());
  		
  		if (mysql_num_rows($result)>0){
  			$this->parse(mysql_fetch_array($result));
  		}
  	}
  }
  
  public function setLastreply($lastreply){
  	if (isset($this->id)){
  		$this->last_reply=mysql_real_escape_string($lastreply);
  		mysql_query("UPDATE forum_post SET last_reply='{$this->last_reply}' WHERE id='{$this->id}'") or die(mysql_error());
  	}
  }
  
  public function delete(){
  	
  	if(isset($this->id)){
  		
  		
  		$query="DELETE FROM forum_post WHERE id='{$this->id}' OR post_father='{$this->id}'";
  		$result = mysql_query($query) or die(mysql_error());
  		
	  //dopo aver cancellato
  		$fatherObj=new ForumPost(null,$this->post_father);
  		$fatherObj->loadChildren();
  		
  	}
  	
  }

  public function setStick(){
  	if (isset($this->id)){
  		$this->sticky=1;
  		mysql_query("UPDATE forum_post SET sticky='{$this->sticky}' WHERE id='{$this->id}'") or die(mysql_error());
  	}
  }
  public function setUnstick(){
  	if (isset($this->id)){
  		$this->sticky=0;
  		mysql_query("UPDATE forum_post SET sticky='{$this->sticky}' WHERE id='{$this->id}'") or die(mysql_error());
  	}
  }
  public function setLock(){
  	if (isset($this->id)){
  		$this->locked=1;
  		mysql_query("UPDATE forum_post SET locked='{$this->locked}' WHERE id='{$this->id}'") or die(mysql_error());
  	}
  }
  public function setUnlock(){
  	if (isset($this->id)){
  		$this->locked=0;
  		mysql_query("UPDATE forum_post SET locked='{$this->locked}' WHERE id='{$this->id}'") or die(mysql_error());
  	}
  }
  
  public function isRecent(){
  	if(isset($this->id)){
  		
  		$mynewdate = new DateTime();
  		$mynewdate->modify('-'.$GLOBALS['forum_post_recent_time'].' hour');
  		$replydate= new DateTime($this->last_reply);
  		
  		if( ($mynewdate) <= ($replydate) )
  			return true;
  		
  		return false;
  	}
  }
  
  public function setMessage($message,$author){
  	if (isset($this->id)){
  		$this->message=($message);
  		$this->last_editby=$author;
  		$this->last_edit=date("YmdHis");
  		$this->writeToDb();
  	}
  }

  public function setTitle($title,$author){
  	if (isset($this->id)){
  		$this->title=($title);
  		$this->last_editby=$author;
  		$this->last_edit=date("YmdHis");
  		$this->writeToDb();
  	}
  }

  public function countChildren(){
  	
  	if (isset($this->id)){
  		$query="SELECT Count(*) AS tot FROM forum_post WHERE post_father='{$this->id}'";
  		$result=mysql_query($query) or die(mysql_error());
  		
  		$row=mysql_fetch_array($result);
  		$this->numchild=$row['tot'];
  	} 
  }

  public function loadChildren($onlyLast=false,$startId=null,$numRecs=null){
  	
  	if(isset($this->id)){
  		
  		$sel="";
  		$dst=& $this->children;
  		
  		if($onlyLast){
  			$sel.=" ORDER BY f.post_date desc";
  			$startId=0;
  			$numRecs=1;
  			$dst=& $this->lastchild;
  		}
  		
  		if(isset($startId) && isset($numRecs))
  			$sel.=" LIMIT $startId, $numRecs";
  		
  		$query="SELECT c.name as author_name, f.*
  		FROM forum_post f, `character` c
  		WHERE c.id=f.author
  		AND f.post_father='{$this->id}'".$sel;
  		
  		$result=mysql_query($query) or die(mysql_error());
  		
  		$maxId=0;
  		$newLastReply=$this->post_date;
  		while($row=mysql_fetch_array($result)){
  			
  			$newChild=new ForumPost($row);
  			
  			if($newChild->getId()>$maxId){
  				$maxId=$newChild->getId();
  				$newLastReply=$newChild->getDate();
  			}
  			
  			$dst[$newChild->getId()]=$newChild;
  			
  			$this->numchild++;
  			
  		}
  		$this->setLastreply($newLastReply);
  		
  	}
  }

  public function getId(){
  	return $this->id;
  }
  public function getBoard(){
  	return $this->forum_board;
  }
  public function getAuthorId(){
  	return $this->author;
  }
  public function getTitle($escape=true){
  	
  	if(!$escape)
  		return $this->title;
  	
  	return htmlspecialchars($this->title);
  }
  public function getMessage($bbcode=false){
  	
  	if($bbcode)
  		$outMessage=BBCode2Html(htmlspecialchars($this->message));
  	else
  		$outMessage=htmlspecialchars($this->message);
  	
  	return $outMessage;
  }
  public function getFatherId(){
  	return $this->post_father;
  }
  public function getDate(){
  	return $this->post_date;
  }
  public function getLastReply(){
  	return $this->last_reply;
  }
  public function getSticky(){
  	return $this->sticky;
  }
  public function getLocked(){
  	return $this->locked;
  }
  public function getAuthorName(){
  	return $this->author_name;
  }
  public function getChildren(){
  	return $this->children;
  }
  public function getLastChild(){
  	return $this->lastchild;
  }
  public function getNumChild(){
  	return $this->numchild;
  }
  public function getLastEdit(){
  	return $this->last_edit;
  }
  public function getLastEditBy(){
  	return $this->last_editby;
  }
}


/**
 * 
 */
class ForumBoard {
	
	private $id;    
	private $board_type;
	private $board_name;
	private $board_description;
	private $modlevel_required;
	private $group_reserved;
	private $board_order;
	
	private $topics=array();
	private $posts=array();
	private $lastpost=array();
	
	private $numrecentposts;
	private $numtopics;
	private $numposts;
	
	private $exist;
	private $board_typename;
	
	public function __construct($row=null,$id=null) {
		
		if (isset($id)){
			$this->id=$id;
			$this->exist=false;
		}  
		if (isset($row)){
			$this->parse($row);
		}
	}
	
	private function parse($row){
		
		if(isset($row)){
			
			if (isset($row['id'])) $this->id=$row['id'];
			if (isset($row['board_type'])){
				$this->board_type=$row['board_type'];
				$this->board_typename=$GLOBALS['forum_types_array'][$this->board_type];
			} 
			if (isset($row['board_name'])) $this->board_name=$row['board_name'];
			if (isset($row['board_description'])) $this->board_description=$row['board_description'];
			if (isset($row['modlevel_required'])){
				$this->modlevel_required=$row['modlevel_required'];
			}else{
				$this->modlevel_required=0;
			}
			if (isset($row['group_reserved'])){
				$this->group_reserved=$row['group_reserved'];
			}else{
				$this->group_reserved=0;
			}
			
		}
	}
	
  /**
   * Legge da db il record relativo alla board e setta a true l'exist
   */
  public function checkExists(){
  	
  	if(isset($this->id) && !$this->exist){
  		$result=mysql_query("SELECT * FROM forum_board WHERE id='{$this->id}'") or die(mysql_error());
  		if (mysql_num_rows($result)==1)
  			$this->parse(mysql_fetch_array($result));
  		$this->exist=true;
  	}
  	return $this->exist;
  }
  
  public function DeleteFromDb(){
  	
  	if(isset($this->id)){
  		$resul=mysql_query("DELETE FROM forum_board WHERE id='$this->id'") or die(mysql_error());
  		$resul=mysql_query("DELETE FROM forum_post WHERE forum_board='$this->id'") or die(mysql_error());
  	}
  }
  
  public function setOrder($order,$type){
  	
  	if(isset($this->id)){
  		$resul=mysql_query("UPDATE forum_board SET board_order='$order', board_type='$type' WHERE id='$this->id'") or die(mysql_error());
  	}
  }
  
  public function countAllPosts($charid=null){
  	
  	
  	if (isset($this->id)){
  		
  		$sel=" WHERE forum_board='{$this->id}'";
  		
  		if(isset($charid))
  			$sel="  WHERE author='{$charid}'";
  		
  		$query="SELECT Count(*) AS tot FROM forum_post".$sel ;
  		$result=mysql_query($query) or die(mysql_error());
  		
  		$row=mysql_fetch_array($result);
  		$this->numposts=$row['tot'];
  	}  
  }
  
  public function countTopics(){
  	
  	if (isset($this->id)){
  		$query="SELECT Count(*) AS tot FROM forum_post WHERE post_father='0' AND forum_board=".$this->id;
  		$result=mysql_query($query) or die(mysql_error());
  		
  		$row=mysql_fetch_array($result);
  		$this->numtopics=$row['tot'];
  	}  
  }
  
  public function countRecentsPosts(){
  	
  	if (isset($this->id)){
  		
  		$mynewdate = new DateTime();
  		$mynewdate->modify('-'.$GLOBALS['forum_post_recent_time'].' hour');
  		$newD=$mynewdate->format("YmdHis");
  		
  		$query="SELECT Count(*) AS tot 
  		FROM forum_post
  		WHERE '{$newD}' <= post_date
  		AND forum_board=".$this->id;
  		
  		$result=mysql_query($query) or die(mysql_error());
  		
  		$row=mysql_fetch_array($result);
  		$this->numrecentposts=$row['tot'];
  	}  
  }
  
  public function canAccess($charObj,$sonOf=null){
  	
  	$canAccessG=false;
  	$canAccessA=false;
  	$canAccessT=false;
  	$canAccessM=false;
  	
    //controllo che il pg abbia l'accesso
      //s$charObj=new Character();
  	if($this->group_reserved>0){
      //controllo il gruppo dell'utente
  		if($charObj->inGroup($this->group_reserved) || $charObj->Account()->getModLevel()>$GLOBALS['acc_add_forumboards_required'])
  			$canAccessG=true;
  	}else{$canAccessG=true;}
  	
  	if($this->group_reserved<0){
      //controllo il livello di master dell'utente
  		if($charObj->getMasterLevel()>=abs($this->group_reserved) || $charObj->Account()->getModLevel()>$GLOBALS['acc_add_forumboards_required'])
  			$canAccessM=true;
  	}else{$canAccessM=true;}
  	
  	if($this->modlevel_required!=0){
      //controllo il livello dell'utente
  		if($charObj->Account()->getModLevel()>=$this->modlevel_required)
  			$canAccessA=true;
  	}else{$canAccessA=true;}
  	
  	if(isset($sonOf) && $sonOf!=0){
  		
  		if(empty($this->topics) || !isset($this->topics[$sonOf]))
  			$this->readTopics($charObj,$sonOf); 
      //se esiste il padre, posso accedere in lettura o scrittura sui figli  
  		if(isset($this->topics[$sonOf])){
  			
  			if ($this->topics[$sonOf]->getLocked()==0){
  				$canAccessT=true;
  			}else{
  				echo "Topic chiuso";
  			}
  		}else{
  			echo "Errore: Topic non presente.";
  		}
  	}else{$canAccessT=true;}
  	
  	if($canAccessG && $canAccessA && $canAccessT && $canAccessM)
  		return true;
  	
  	return false;
  	
  }

  public function readTopics($charObj,$topicId=null,$onlyTopics=false,$onlyLast=false,$startId=null,$numRecs=null){
  	
  	if(isset($this->id)){
  		
  		if(!$this->canAccess($charObj)){
  			echo "accesso negato";  
  			return false;
  		}
  		
  		$sel="";
  		
  		$dst=&$this->posts;
  		if(isset($topicId)){
  			$sel.=" AND f.id='$topicId'";
  			$dst=&$this->topics;
  		}
  		
  		if($onlyTopics){
  			$sel.=" AND f.post_father='0' ORDER BY f.sticky desc, f.last_reply desc";
  			$dst=&$this->topics; 
  		}
  		
  		if($onlyLast){
  			$sel.=" ORDER BY f.post_date desc";
  			$startId=0;
  			$numRecs=1;
  			$dst=& $this->lastpost;
  		}
  		
  		if(isset($startId) && isset($numRecs))
  			$sel.=" LIMIT $startId, $numRecs";
  		
  		$query="SELECT c.name AS author_name, f.*
  		FROM forum_post f, `character` c
  		WHERE f.author=c.id
  		AND f.forum_board='{$this->id}'".$sel;
  		
  		
  		$result = mysql_query($query) or die(mysql_error());
  		
  		while($row=mysql_fetch_array($result)){
  			
  			$tmp_topic=new ForumPost($row);
  			
  			$dst[$tmp_topic->getId()]=$tmp_topic; 
  		}
  		return true;
  	}
  	return false;
  }

  public function postTopic($row,$charObj,$sonOf=null){
  	
  	if(isset($this->id)){
  		
  		if(!isset($sonOf))
  			$sonOf=0;
  		
  		if($this->exist && $this->canAccess($charObj,$sonOf)){
  			
  			
  			$row['forum_board']=$this->id;
  			if (!isset($row['author']))
  				$row['author']=$charObj->getCharId(); 
  			$row['post_father']=$sonOf;
  			
  			if($sonOf!=0)
  				$row['title']=$this->topics[$sonOf]->getTitle(false);
  			
  			$newTopic=new ForumPost($row);
  			$newTopic->writeToDb();
  			
  			$this->topics[]=$newTopic; 
  			$this->numtopics=$this->numtopics + 1;
  			return $newTopic;  
  		}
  		
  	}
  	return false;
  }

  public function writeToDb(){
  	
    if (isset($this->id)){ //l'id è settato, faccio l'update
    $query="UPDATE forum_board SET
    board_type='{$this->board_type}',
    board_name='{$this->board_name}',
    board_description='{$this->board_description}',
    modlevel_required='{$this->modlevel_required}',
    group_reserved='{$this->group_reserved}'
    WHERE id='{$this->id}'";      
  }else{
  	$query="INSERT INTO forum_board SET
  	board_type='{$this->board_type}',
  	board_name='{$this->board_name}',
  	board_description='{$this->board_description}',
  	modlevel_required='{$this->modlevel_required}',
  	group_reserved='{$this->group_reserved}'";
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
  
  public function getName(){
  	return $this->board_name;
  }
  
  public function setName($arg){
  	if (isset($this->id)){
  		$this->board_name=$arg;
  		$query="UPDATE forum_board SET board_name='{$this->board_name}' WHERE id='{$this->id}'";
  		$result = mysql_query($query) or die(mysql_error());
  	}
  }
  
  public function getDescription(){
  	return $this->board_description;
  }
  
  public function setDescription($arg){
  	if (isset($this->id)){
  		$this->board_description=$arg;
  		$query="UPDATE forum_board SET board_description='{$this->board_description}' WHERE id='{$this->id}'";
  		$result = mysql_query($query) or die(mysql_error());
  	}
  }
  
  public function getType(){
  	return $this->board_type;
  }
  
  public function getTypeName(){
  	return $this->board_typename;
  }
  
  public function getNumPosts(){
  	return $this->numposts;
  }
  
  public function getNumTopics(){
  	return $this->numtopics;
  }
  
  public function getRecentPosts(){
  	return $this->numrecentposts;
  }
  
  public function getLastPost(){
  	return $this->lastpost;
  }
  
  public function getTopics(){
  	return $this->topics;
  }
  
  public function getPosts(){
  	return $this->posts;
  }
}

/**
 * 
 */
class BoardList {
	
	private $board_list=array();
	private $board_types=array();  
	
	/**
	* se passo l'oggetto charObj mette in lista solo le board a cui posso fare accesso
	*
	* @return void
	* @author  
	*/
	public function __construct($charObj=null) {
		
		
		$query="SELECT * FROM forum_board ORDER BY board_order";
		$result=mysql_query($query) or die(mysql_error());
		
		while ($row=mysql_fetch_array($result)){
			$tmpBrd=new ForumBoard($row);
			
			if(!isset($charObj) || $tmpBrd->canAccess($charObj))
				$this->board_list[$tmpBrd->getId()]=$tmpBrd;
			
		//aggiungo anche il numero alla lista di tipi
			$this->board_types[$tmpBrd->getType()]++;
			
		}
		
	}

	public function getBoards(){
		return $this->board_list;
	}

	public function getBoardsTypesNum(){
		return $this->board_types;
	}

}

?>