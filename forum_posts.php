<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/forum_lib.php");
require_once("libs/character_lib.php");
require_once("libs/common.php");

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

$can_edit_topics=false;
if ($_SESSION['modlevel'] >= $acc_admin_forumtopics_required)
  $can_edit_topics=true;


?>

<script type="text/javascript">

function jeditDo() {
	$('.edit_post').editable('forum_newpostUW.php', { 
                         id        : 'modify_field',
                         name      : 'new_value',
                         type      : 'markitup',
                         cancel    : 'Annulla',
                         submit    : 'Salva',
                         indicator : '<img src="images/icons/loadinfo.net.gif">',
                         tooltip   : 'Click per modificare...',
                         onblur    : 'ignore',
                         event     : "dblclick",
                         markitup  : mySettings,
                         data      : function(value, settings) {
                            // Convert <br> to newline. 
                            var nVal = $("#original_"+$(this).attr('id')).html();
                            
                            var retval = nVal.replace(/<br[\s\/]?>/gi, '\n').replace(/^\s\s*/, '').replace(/\s\s*$/, '');
                            return retval;
                         },
                         callback  : function(value, settings){
                         
                         	var original=$(value).filter("#original").html();
                         	$("#original_"+$(this).attr('id')).html(original);
                         	var parsed=$(value).filter("#parsed").html();
                         	$(this).html(parsed);
                         	
                         }
        });
	
	
}


$(document).ready(function(){
   
   jeditDo();
   
   $("#forum_post_list").on('click', "a.post_del", function(){
    url=$(this).attr('href');
    if(confirm('Sei Sicuro di voler cancellare?')){
    	$.get(url,function(data){
    		//alert($(data).filter('span#deletedpost').html());
    		    		
    		$(data).filter('span#deletedpost').each(function(){
    			$('#'+$(this).html()).fadeOut().remove();
    		});
    	});
    }
    return false;
   });
   
   
   $("#reply_box").on('submit', "#form_postForum", function(){
		rowArr = $(this).serializeArray(); 
     	$.post($(this).attr("action"),rowArr,function(data2){
    		$('#forum_post_list').append(data2);
    		$('#message').val("");
    		$("#reply_box").fadeOut();
    		jeditDo();
	  	});
		
		return false;    	
    });
    
    
   
   $("#forum_post_list").on('click', "a.doedit", function(){
	    ref=$(this).attr('ref');
	    $('#'+ref).trigger('dblclick');
	    return false;
   });
   
   
   $("#reply_box").load("forum_newpostUW.php?t=<?=$_REQUEST['f']?>&b=<?=$_REQUEST['b']?>");
   
   $(".post_reply").click(function(){
   	
   	
   	 $("#reply_box").fadeToggle('slow',function(){
   	 	$('#message').focus();
   	 });
   	 $('#content').animate({scrollTop: $("#reply_box").offset().top},'slow');
   	
   	return false;
   });
   
   /*$("a.popUp").click(function(){
      parent.showAvatar(this.href);
      return false;
   });*/
   
});

</script>

<div class="centertxt forum_spacer"><h2><a href="forum_boards.php"><?=$forum?></a></h2>
<?php

if (!isset($_REQUEST['f']) || $_REQUEST['f']=='' ){
	echo "<br />Devi specificare il padre";
	exit();	
}
if (!isset($_REQUEST['b']) || $_REQUEST['b']=='' ){
  echo "<br />Devi specificare la board";
  exit(); 
}

$start_pag=0;

if ($_REQUEST['p']!='' && $_REQUEST['p']>0){
	$start_pag=$_REQUEST['p']*($posts_per_pag);
}


$ForumBoard=new ForumBoard(null,$_REQUEST['b']);
$ForumBoard->checkExists();


if (!$ForumBoard->readTopics($MyChar_obj,$_REQUEST['f']))
  echo "Accesso Negato";


//scrivo il path dove sono
foreach($ForumBoard->getTopics() as $key=>$value){
      
  $topic_locked=$value->getLocked();  
  
  $pathT="<a href=\"forum_boards.php\">$forum</a> / <a href=\"forum_topics.php?b={$ForumBoard->getId()}\" id=\"goBackTopics\" >{$ForumBoard->getName()}</a> / {$value->getTitle()}";
  break;
}


?>
</div>

  <div class="forum_postBoxW">
    <?php echo $pathT; ?>
  </div>
  <?php
    if(!$topic_locked){
  ?>    
  <div class="forum_postBoxW">
    <a href="#reply_box" class="post_reply"><img src="images/icons/document.png" border="0" />Nuova Risposta</a>
  </div>
  <div id="forum_post_list">
  <?php    
    }
  
  foreach($ForumBoard->getTopics() as $key=>$value){
    
    //$value=new ForumPost();
    
      
    $value->loadChildren(false,$start_pag,$posts_per_pag);
    
    $charAuth=new Character(null,$value->getAuthorName());
    
    $editMsg="";
    if($value->getLastEditBy()>0){
      
      $charEdit=new Character($value->getLastEditBy());
      $charEdit->checkExistance();
      
      $editMsg="<div class=\"forum_lastedit\">Ultima modifca: {$charEdit->getCharNameLink()} il ".itaTime($value->getLastEdit())."</div>";
      
    }
    
    $ForumBoard->countAllPosts($value->getAuthorId());
	
	if($can_edit_topics || $value->getAuthorId()==$_SESSION['char_id']){
		$editDoC=" edit_post";
		$editors="<span><a href=\"#\" class=\"doedit\" ref=\"M{$value->getId()}\"><img border=\"0\" src=\"images/icons/pencil.png\" title=\"Modifica\" alt=\"Modifica\" /></a></span>";
	}else{
		$editDoC="";
		$editors="";
	}
	  
    echo "<div id=\"forumPID_{$value->getId()}\" class=\"forum_postBox roundcorner clearborder panel_bg\">
            <div class=\"forum_postAuthor\">
              <div>{$charAuth->getCharNameLink()}</div>
              <div>Posts:{$ForumBoard->getNumPosts()}</div>
            </div>
            <div class=\"forum_postContent clearborder\">
              <div class=\"floatright\">Postato il ".itaTime($value->getDate())."</div>
              <div class=\"forum_postTitle clearborder{$editDoC}\" id=\"T{$value->getId()}\" >{$value->getTitle()}</div>
              <div id=\"original_T{$value->getId()}\" style=\"display:none\">{$value->getTitle()}</div>
              <div class=\"forum_postMessage{$editDoC}\" id=\"M{$value->getId()}\">".acapo($value->getMessage(true))."</div>
              <div id=\"original_M{$value->getId()}\" style=\"display:none\">".acapo($value->getMessage(false))."</div>
              {$editMsg}
              {$editors}
            </div>
          </div>";
    
    foreach($value->getChildren() as $k=>$v){
        
      $charAuth=new Character(null,$v->getAuthorName());  
      
      $editMsg="";
      if($v->getLastEditBy()>0){
        
        $charEdit=new Character($v->getLastEditBy());
        $charEdit->checkExistance();
        
        $editMsg="<div class=\"forum_lastedit\">Ultima modifca: {$charEdit->getCharNameLink()} il ".itaTime($v->getLastEdit())."</div>";
        
      }
      
      $ForumBoard->countAllPosts($v->getAuthorId());
      if($can_edit_topics || $v->getAuthorId()==$_SESSION['char_id']){
		$editDoC=" edit_post";
		$editors="<span><a href=\"#\" class=\"doedit\" ref=\"M{$v->getId()}\"><img border=\"0\" src=\"images/icons/pencil.png\" title=\"Modifica\" alt=\"Modifica\" /></a></span>
                  <span><a class=\"post_del\" href=\"forum_newpostUW.php?del={$v->getId()}&b={$_REQUEST['b']}&t={$_REQUEST['f']}\"><img border=\"0\" src=\"images/icons/delete.png\" title=\"Cancella\" alt=\"Cancella\" /></a></span>";
		
	  }else{
		$editDoC="";	
		$editors="";
	  }
      echo "<div id=\"forumPID_{$v->getId()}\" class=\"forum_postBox roundcorner clearborder panel_bg\">
              <div class=\"forum_postAuthor\">
                <div>{$charAuth->getCharNameLink()}</div>
                <div>Posts:{$ForumBoard->getNumPosts()}</div>
              </div>
              <div class=\"forum_postContent clearborder\">
                <div class=\"floatright\">Postato il ".itaTime($v->getDate())."</div>
                <div class=\"forum_postTitle clearborder\">Re: {$value->getTitle()}</div>
                <div class=\"forum_postMessage{$editDoC}\" id=\"M{$v->getId()}\">".acapo($v->getMessage(true))."</div>
                <div id=\"original_M{$v->getId()}\" style=\"display:none\">".acapo($v->getMessage(false))."</div>
                {$editMsg}
                {$editors}
              </div>
            </div>";
      
    }
    
  }
  

    if(!$topic_locked){
  ?>  
  </div>  
  <div class="forum_postBoxW">
    <a href="#" class="post_reply"><img src="images/icons/document.png" border="0" />Nuova Risposta</a>
  </div>
  <div id="reply_box" style="display:none;width:85%;margin:0 auto;"></div>
  <?php    
    }
  ?>
<table width="600" class="center">
  <tr>
    <td>
	<?php
  $value->countChildren();
  
  $count_pag=($value->getNumChild())/$topics_per_pag;
  
  $n_pages=ceil($count_pag);
  
  if ($n_pages>0)
    echo "Page: ";
  
  for ($i=0;$i<$n_pages;$i++){
    $curpage=$i+1;  
    if ($i==$start_pag/$posts_per_pag){
      echo "[$curpage]";
    }else{
      echo "<a href=\"".$_SERVER['PHP_SELF']."?p=$i&f={$_REQUEST['f']}&b={$_REQUEST['b']}\">$curpage</a> ";
    }
  }
  
  ?></td>
  </tr>
</table>

