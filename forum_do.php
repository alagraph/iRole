<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/forum_lib.php");
require_once("libs/character_lib.php");

if(!isset($_SESSION))
{
session_start();
} 

logged();

$MyChar_obj=new Character($_SESSION['char_id']);
$MyChar_obj->parseFromDb();

if(!($MyChar_obj->exists())){
  echo "Personaggio inesistente.";
  exit();  
}

$_SESSION['modlevel']=$MyChar_obj->Account()->getModLevel();

$post_id=$_REQUEST['post_id'];
$action=$_REQUEST['action'];


$canAdmin=false;
if ($_SESSION['modlevel'] >= $acc_admin_forumtopics_required)
  $canAdmin=true;

if (!$canAdmin)
  exit();
  
  
if (isset($_REQUEST['new_val']) && isset($_REQUEST['type_id'])){
	
	$arr_t_id=explode('_',$_REQUEST['type_id']);
	
	if(count($arr_t_id)==2){
		
		$boardObj=new ForumBoard(null,$arr_t_id[1]);
		
		switch($arr_t_id[0]){
			
			case 'title':
				$boardObj->setName($_REQUEST['new_val']);
				echo $boardObj->getName();
				break;
			
			case 'desc':
				$boardObj->setDescription($_REQUEST['new_val']);
				echo $boardObj->getDescription();
				break;
			
		}
	}
}


//routine per lockare il topic

if (isset($post_id) && $post_id>0){
  
  $postObj=new ForumPost(null,$post_id);
  $postObj->readFromDb();
  
  switch ($action) {
      case 'lock':
        
        if($canAdmin){
          $postObj->setLock();
          echo '<span id="symbol_lock"><img src="images/icons/lock-closed.png" border="0" /></span>Azione Eseguita Correttamente';
        }
        
        break;
        
      case 'unlock':
        
        if($canAdmin){
          $postObj->setUnlock();
          echo '<span id="symbol_lock"></span>Azione Eseguita Correttamente';
        }
        
        break;  
        
      case 'stick':
        
        if($canAdmin){
          $postObj->setStick();
          echo '<span id="symbol_stick"><img src="images/icons/megaphone.png" border="0" /></span>Azione Eseguita Correttamente';
        }
        
        break;
        
      case 'unstick':
        
        if($canAdmin){
          $postObj->setUnstick();
          echo '<span id="symbol_stick"></span>Azione Eseguita Correttamente';
        }
        
        break;
        
      case 'edit':
        
        if( $_REQUEST['message'] && ($_SESSION['char_id']==$postObj->getAuthorId() || $canAdmin)){
          $postObj->setMessage($message, $_SESSION['char_id']);
          echo $postObj->getMessage();
        }
        
        break;
 
      default:
        
        break;
  }
  
  
}


?>