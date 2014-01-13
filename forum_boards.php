<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/forum_lib.php");
require_once("libs/group_lib.php");

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

$canAdmin=false;
if ($_SESSION['modlevel'] >= $acc_add_forumboards_required)
  $canAdmin=true;


if ($canAdmin && isset($_REQUEST['boardsorder']) ){
  
	
  foreach($_REQUEST['boardsorder'] as $k=>$v){
	
	$dec=explode(',',$v);
	  
	$newOrder=new ForumBoard(null,$dec[0]);
	$newOrder->setOrder($dec[1],$dec[2]);
	
  }	
	
  exit();
}
  
if( $canAdmin && isset($_REQUEST['nB_type']) && isset($_REQUEST['nB_name']) && $_REQUEST['nB_name']!='' && isset($_REQUEST['nB_lvl']) && isset($_REQUEST['nB_grp']) ){
	
  $row['board_type']=$_REQUEST['nB_type'];
  $row['board_name']=$_REQUEST['nB_name'];
  $row['board_description']=$_REQUEST['nB_descr'];
  $row['modlevel_required']=$_REQUEST['nB_lvl'];
  $row['group_reserved']=$_REQUEST['nB_grp'];
	
  $newGrp=new ForumBoard($row);
  $newGrp->writeToDb();
	
}

if ( $canAdmin && isset($_REQUEST['del']) ){
  $delGrp=new ForumBoard(null,$_REQUEST['del']);
  $delGrp->DeleteFromDb();
}

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<title>Forum</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">

function saveOrder(){

  var jsonObj = new Array(); //declare array

  $(".orderedboard").each(function(index) {
	  
	  b_id=$(this).attr("boardid");
	  b_index=index;
	  b_nty=$(this).find(".b_ntype").val();
	  
	  jElem=new Array(b_id,b_index,b_nty);      
	  jsonObj.push(jElem);
	  
  });
  
  $.post("forum_boards.php", {'boardsorder[]': jsonObj});

}

$(document).ready(function(){

  $("#legendPanel").gearPanel();
  
  $("a.popUp").click(function(){
	  parent.showAvatar(this.href);
	  return false;
   });
  
  $(".cancBoard").click(function(){
	 if(!confirm('Stai per cancellare una categoria e tutti i Topic in essa contenuti.\nNon potrai annullare questa operazione.\nSei sicuro di voler cancellare?')){
	  return false;
	 }
  });
   
  $(".board_edit button").on("click", function(event) {
	event.stopPropagation();
	event.preventDefault();
	return false;
  });

  $(".board_edit").on("click",function(){
  	if( $(this).find("textarea") || $(this).prop("tagName").toLowerCase()=="textarea" )
  		return false;
  });
  
  $('.board_edit').editable('forum_do.php', { 
						 id        : 'type_id',
						 name      : 'new_val',
						 type      : 'textarea',
						 cancel    : 'Annulla',
						 submit    : 'Salva',
						 indicator : '<img src="images/icons/loadinfo.net.gif">',
						 tooltip   : 'Click per modificare...',
						 onblur    : 'ignore',
						 event     : 'dblclick',
						 style	   : 'inherit',
						 data      : function(value, settings) {
							// Convert <br> to newline. 
							var retval = value.replace(/<br[\s\/]?>/gi, '\n').replace(/^\s\s*/, '').replace(/\s\s*$/, '');
							return retval;
							}
						 }
  );
  
  $("a.edit_link").click(function(){
				$('#'+this.rel).trigger('dblclick');
				return false;
		});
		
  
  $( "#sortable" ).sortable({
   update: function(){saveOrder();}  
  });
  $( "#sortable" ).disableSelection();


});

</script>
</head>

<body class="forum">
<div class="centertxt forum_spacer"><h2><?=$forum?></h2></div>

<table class="forum_table clearborder panel_bg" >
	<tr class="dark_bg clearborder">
		<th width="360">Titolo</td>
		<th width="170">Post Recenti </td>
		<th width="270">Ultimo Post </td>
	</tr>
	<?php

	$boardlist= new BoardList($MyChar_obj);

	$viewB=-1;
	$boardTypeNum=$boardlist->getBoardsTypesNum();
	$i=0;
	foreach ($boardlist->getBoards() as $key => $value) {

		$class= ($i/2==0)? 'clearborder':'clearborder';
		$i++;

		if ($viewB!=$value->getType() && 	$boardTypeNum[$value->getType()]>0){
			echo "<tr class=\"$class\"><td colspan=\"3\">$forum {$value->getTypeName()}</td></tr>";
			$viewB=$value->getType();
		}

		$value->countAllPosts();
		$value->countRecentsPosts();
		$value->countTopics();
		$value->readTopics($MyChar_obj,null,false,true);

		$topic=null;
		foreach($value->getLastPost() as $k=>$v)
		$topic=&$v;

		//variabili che contano il numero
		$all_post=$value->getNumPosts();
		$recent_post=$value->getRecentPosts();
		$topic_post=$value->getNumTopics();

		$recentImg="";
		if(intval($recent_post)>0){
			$recentImg="<span class=\"pm_counterW floatleft\"><div class=\"pm_counter\">!</div></span>";
		}

		if(isset($topic)){

			$tmpChar=new Character(null,$topic->getAuthorName());
			$last_insert_username=$tmpChar->getCharNameLink();
			$last_insert_date=itaTime($topic->getDate());
			$last_insert_title=$topic->getTitle();
			$last_complete="Ultimo post in $last_insert_title by $last_insert_username<br />il $last_insert_date";
		}else{
			$last_complete="Nessun Messaggio";
		}

		$del="";
		if($canAdmin){
			$del=" <a class=\"cancBoard\" href=\"forum_boards.php?del={$value->getId()}\"><img src=\"images/icons/delete.png\" border=\"0\" title=\"Cancella\" alt=\"Cancella\" /></a>";
			$edit_class="board_edit";
			$editTitle="<a href=\"#\" class=\"edit_link\" rel=\"title_{$value->getId()}\" style=\"clear:both\"><img src=\"images/icons/pencil_16.png\" border=\"0\" /></a>";
			$editDesc="<a href=\"#\" class=\"edit_link\" rel=\"desc_{$value->getId()}\"><img src=\"images/icons/pencil_16.png\" border=\"0\" /></a>";
		}

		$class= ($i/2==0)? 'clearborder':'clearborder';
		$i++;

		//stampo
		echo "<tr class=\"$class\">
			  <td>
			  	<div>$recentImg $editTitle
			  		<a href=\"forum_topics.php?b={$value->getId()}\" id=\"title_{$value->getId()}\" class=\"board_edit\" style=\"float:left;width:350px;\">{$value->getName()}</a>
			  		$del
			  	</div>
			  	<div> $editDesc <span class=\"forum_lastedit {$edit_class}\" id=\"desc_{$value->getId()}\">{$value->getDescription()}</span></div>
			  </td>
			  <td>$all_post post in $topic_post topic,<br />di cui $recent_post recenti</td>
			  <td>$last_complete</td>
			</tr>";
	}

	?>
</table>
<?php
  //se l'utente ha i permessi necessari gli do la gestione
  
  if ($canAdmin){
	  
	foreach($forum_types_array as $k=>$v)
		$type_opt.="<option value=\"$k\">$v</option>\n";
	  
	foreach($acc_level_array as $k=>$v)
		$lvl_opt.="<option value=\"$k\">$v</option>\n";
	
	$groupList=new GroupList();
	$group_opt.="<option value=\"0\">Nessuno</option>";
	
	foreach($master_level_array as $k=>$v){
		if ($k>0)
			$group_opt.="<option value=\"-{$k}\">{$v}</option>";	
	}
	
	
	foreach($groupList->GetList() as $k=>$v)
		$group_opt.="<option value=\"{$v->getId()}\">{$v->getName()}</option>\n"; 
	
	echo "<div id=\"legendPanel\">
			<form id=\"add_board\" name=\"add_board\" action=\"forum_boards.php\" >
			  <div class=\"floatleft\">Aggiungi Nuova
				<div>Tipologia: <select name=\"nB_type\">$type_opt</select></div>
				<div>Nome: <input type=\"text\" name=\"nB_name\" /></div>
				<div>Descrizione: <input type=\"text\" name=\"nB_descr\" /></div>
				<div>Possono accedere: <select name=\"nB_lvl\">$lvl_opt</select></div>
				<div>Riservata al gruppo: <select name=\"nB_grp\">$group_opt</select></div>
				<div><input type=\"submit\" value=\"Salva\"/></div>
			  </div>
			  <div class=\"floatleft\">Modifica Esistenti
				<ul id=\"sortable\">";
				  foreach ($boardlist->getBoards() as $key => $value) {
					
					$type_optS="<select class=\"b_ntype\" onchange=\"saveOrder()\">";
					
					foreach($forum_types_array as $k=>$v){
						
						$sel_opt="";
						if($value->getType()==$k)
							$sel_opt="selected=\"selected\"";
							
						$type_optS.="<option value=\"$k\" $sel_opt>$v</option>\n";
							
					}
					$type_optS.="</select>";
					
					echo "<li class=\"ui-state-default orderedboard\" boardid=\"{$value->getId()}\" ><span class=\"ui-icon ui-icon-arrowthick-2-n-s\"></span>{$value->getName()} $type_optS</li>";
				  }
				  
				echo "</ul>
			  </div>
			  <div class=\"clearboth\"></div>
			</form>
		  </div>";
  }

?>
</body>
</html>
