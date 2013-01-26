<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/pm_lib.php");

if(!isset($_SESSION))
{
session_start();
} 

logged();

$start_pag=0;
if (isset($_REQUEST['p']) && $_REQUEST['p']>0){
	$start_pag=$_REQUEST['p']*($pm_per_pag);
}

$ref_id=$_SESSION['id'];
if ($bind_pm_char)
	$ref_id=$_SESSION['char_id'];

//0=messaggi in arrivo 1=cestino -1=inviati

if (isset($_REQUEST['f']) && $_REQUEST['f']=='1'){ //trash
  	$pm_list=new PrivateMessageList($ref_id,1,$ref_id);
	$page_q=$pm_list->count();
}elseif (isset($_REQUEST['f']) && $_REQUEST['f']=='-1'){ //outbox ->mostro destinatario
  	$pm_list=new PrivateMessageList($ref_id,0,$ref_id);
	$page_q=$pm_list->count();
}elseif(!empty($_REQUEST['s'])){
		
	//nel search posso cercare solo i messaggi che posso vedere	
  	
  	$searchV=trim($_REQUEST['s']); 
  	$pm_list=new PrivateMessageList(null,0,$ref_id,$searchV);
	$page_q=$pm_list->count();
		
}else{ //inbox
	$pm_list=new PrivateMessageList($ref_id,0,$ref_id);
	$page_q=$pm_list->count();
}

	
	if (count($pm_list->PMList())>0){
	
	echo "<div id=\"pm_pag_c\">";
		
	$i=0;
	  
    foreach ($pm_list->PMList() as $key => $value) {
      $subject=$value->getSubject();
      if ($subject=="")
        $subject="Nessun Oggetto";
	  
	  if($value->getReplyCount()>1)
	  	$subject.= " &mdash; {$value->getReplyCount()} messaggi";
      
      $unread="";
        
      if ($value->getRecipient()==$ref_id &&  $value->getViewed()==0 && $deleter!="0")
        $unread=" boldy";
	  
	  if($value->getRecipient()==$value->getSender()){
	  	$prefix="Annotazione";
	  }
	  elseif($value->getRecipient()!=$ref_id){
	  	$prefix="A: {$value->getRecipientNameLink()}";
	  }else{
	  	$prefix="Da: {$value->getSenderNameLink()}";
	  }
      
	  $class= ((($i++)%2)==0)? 'even':'odd';
		
      echo "
          <div class=\"personal_message $class\" id=\"".$value->getId()."\">
            <div class=\"pm_sender\">".$prefix."</div>
            <div class=\"pm_subject$unread\"><a title=\"$subject\" href=\"pm_newUW.php?rply=$deleter".$value->getId()."\" target=\"imessage\">".$subject."</a></div>
            <div class=\"pm_sent\">".itaTime($value->getSentDate())."</div>
            <div class=\"pm_delete\"><a title=\"Cancella\" href=\"pm_newUW.php?del=".$value->getId()."\" class=\"deletePm\"><img src=\"images/icons/delete.png\" border=\"0\" /></a></div>
          </div>
          ";
          
    }

	echo "</div><span style=\"display:none\" id=\"pm_countH\">(".pm_countNew($ref_id).")</span>";
	

  }else{
  	echo "Nessun {$GLOBALS['missive']}.";
  }
?>
<script type="text/javascript">

$(function() {
	
	
	$("#pm_pag_c").quickPager({pageSize:"<? echo $pm_per_pag; ?>"});
	
	
	
});	
</script>
