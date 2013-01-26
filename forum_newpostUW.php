<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
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


$ForumBoard=new ForumBoard(null,$_REQUEST['b']);
$ForumBoard->checkExists();


$delete_id=$_REQUEST['del'];
$modify_id=$_POST['modify_field'];
$modify_val=$_POST['new_value'];

/* MODIFICA */
if (isset($modify_id) && $modify_id!="" && isset($modify_val) && $modify_val!=""){
  	
  //controlliamo se sono arrivati i campi
  $modField=substr($modify_id,0,1);
  $modId=intval(substr($modify_id,1));
	
  $theP=new ForumPost(null,$modId);
  $theP->readFromDb();
  
  if($theP->getAuthorId()!=$MyChar_obj->getCharId() && $_SESSION['modlevel']<$acc_admin_forumtopics_required)
  	exit("Permessi Insufficienti.");
  
  
  
  if($modField=='M'){
  	$theP->setMessage($modify_val, $MyChar_obj->getCharId());
  	echo "<span id=\"original\">".acapo($theP->getMessage(false))."</span>
  		  <span id=\"parsed\">".acapo($theP->getMessage(true))."</span>";
  	
  }elseif($modField=='T'){
  	$theP->setTitle($modify_val, $MyChar_obj->getCharId());
	echo "<span id=\"original\">".($theP->getTitle())."</span>
  		  <span id=\"parsed\">".($theP->getTitle())."</span>";
  }else{
  	exit("Parametri Errati.");
  }
  
  
  exit();
  
}



$father_id=0;
if ($_REQUEST['t']!='' && $_REQUEST['t']>0)
	$father_id=$_REQUEST['t'];
 
 
/* CANCELLAZIONE */  
if (isset($delete_id) && $delete_id!="" && $delete_id!="0" && ($MyChar_obj->getCharId()==$oldAuthorId || $_SESSION['modlevel']>=$acc_admin_forumtopics_required)){
  $row['id']=$delete_id;
  $del_p=new ForumPost(null,$delete_id);
  $del_p->delete();
  
  //ora che ho rimosso il messaggio devo rimuoverne l'elemento corrispondente dalla pagina
  
  if($father_id==0){
  	echo "<span id=\"deletedtopic\">forumTID_{$delete_id}</span>";
  }else{
	echo "<span id=\"deletedpost\">forumPID_{$delete_id}</span>";
  }
  
  
  exit();
}  


/* SALVATAGGIO */
if ($_REQUEST['s']==1) {
    
  $row['author']=$_SESSION['char_id'];
  
  $row['title']=$_POST['title'];
  $row['post_father']=$father_id;
  $row['message']=$_POST['message'];
  
  $flag=true;
  
  if($father_id==0 && empty($row['title'])){
  	echo "Devi specificare un titolo.";
	$flag=false;
  }
  //echo print_r($row);
  
  if($flag){
	$newT=$ForumBoard->postTopic($row,$MyChar_obj,$father_id);
	
	
	if($father_id!=0){
		
		$ForumBoard->countAllPosts($newT->getAuthorId());
	   
			
		$editors="<span><a href=\"#\" class=\"doedit\" ref=\"M{$newT->getId()}\"><img border=\"0\" src=\"images/icons/pencil.png\" title=\"Modifica\" alt=\"Modifica\" /></a></span>
		          <span><a class=\"post_del\" href=\"forum_newpostUW.php?del={$newT->getId()}&b={$_REQUEST['b']}&t={$father_id}\"><img border=\"0\" src=\"images/icons/delete.png\" title=\"Cancella\" alt=\"Cancella\" /></a></span>";
		
	
	  	echo "<div id=\"forumPID_{$newT->getId()}\" class=\"forum_postBox roundcorner clearborder panel_bg\">
			      <div class=\"forum_postAuthor\">
			        <div>{$MyChar_obj->getCharNameLink()}</div>
			        <div>Posts:{$ForumBoard->getNumPosts()}</div>
			      </div>
			      <div class=\"forum_postContent clearborder\">
			        <div class=\"floatright\">Postato il ".itaTime($newT->getDate())."</div>
			        <div class=\"forum_postTitle clearborder\">Re: {$newT->getTitle()}</div>
			        <div class=\"forum_postMessage edit_post\" id=\"M{$newT->getId()}\">".acapo($newT->getMessage(true))."</div>
                	<div id=\"original_M{$newT->getId()}\" style=\"display:none\">".acapo($newT->getMessage(false))."</div>
			        {$editors}
			      </div>
		    </div>";
		
	}else{
		
		$admOpt="";
		if ($_SESSION['modlevel'] >= $acc_admin_forumtopics_required)
  			$admOpt="<span class=\"admin_gear\" pid=\"{$newT->getId()}\"><img src=\"images/icons/gear-gold.png\" border=\"0\" /></span>";
		
		echo "<tr id=\"forumTID_{$newT->getId()}\">
            <td>$admOpt <span id=\"l_{$newT->getId()}\" class=\"symbol_lock\"></span> <span id=\"s_{$newT->getId()}\" class=\"symbol_stick\"></span> <a href=\"forum_posts.php?f={$newT->getId()}&b={$newT->getBoard()}\">{$newT->getTitle()}</a></td>
            <td>{$MyChar_obj->getCharNameLink()}<br />il ".itaTime($newT->getDate())."</td>
            <td>0</td>
            <td>-</td>
          </tr>";
	}
	}
	
	
  
	exit();
}
?>

<script>
$(document).ready(function()	{
    $('.markitup').markItUp(mySettings);
    $( ".buttonify" ).button();
    
});
</script>

<!-- INSERIMENTO NUOVO POST -->
<form id="form_postForum" name="form_postForum" method="post" action="forum_newpostUW.php?s=1">
<table width="100%" border="0" class="center" cellpadding="0" cellspacing="0" style="margin-top:10px;">
	<?
	if ($_REQUEST['t']!=0){
		echo '<input name="t" type="hidden" value="'.$_REQUEST['t'].'" />';
	}else{
	  echo '<tr><td><input type="text" name="title" id="newTopicTitle" placeholder="Titolo del Topic" style="width:100%;padding:5px;" /></td></tr>';
	}
	
	echo '<input name="b" type="hidden" value="'.$_REQUEST['b'].'" />
	      <input name="modify" type="hidden" value="'.$modify_id.'" />';
	
	?>
  <tr>
    <td>
      <textarea name="message" cols="60" rows="20" id="message" class="markitup" placeholder="Testo del Messaggio..."><?php echo $oldMessage; ?></textarea>
    </td>
  </tr>
  <tr>
    <td>
    	<div class="centertxt">
      		<input type="submit" name="Submit" value="Invia" class="buttonify"/>
    	</div>
    </td>
  </tr>
</table>
</form>
