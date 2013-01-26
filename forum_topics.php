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

$_SESSION['modlevel']=$MyChar_obj->Account()->getModLevel();

$canAdmin=false;
if ($_SESSION['modlevel'] >= $acc_admin_forumtopics_required)
  $canAdmin=true;

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Forum</title>

<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">
$(document).ready(function(){
   
   $(".forum_table").on('click', "span.admin_gear", function(){
    
    $(".dialog_f").dialog( "close" )
    $(".dialog_f").dialog( "destroy" );
    
    var pid=$(this).attr("pid");
    
    var newDialog=$('<div class="dialog_f" title="Gestione Topics">Topic: '+pid+
    '<br /><a class="ajaxReq" href="forum_do.php?post_id='+pid+'&action=stick">Rendi Importante</a>'+
    '<br /><a class="ajaxReq" href="forum_do.php?post_id='+pid+'&action=unstick">Togli Importante</a>'+
    '<br /><a class="ajaxReq" href="forum_do.php?post_id='+pid+'&action=lock">Blocca inserimenti</a>'+
    '<br /><a class="ajaxReq" href="forum_do.php?post_id='+pid+'&action=unlock">Sblocca inserimenti</a>'+
    '<br /><a class="post_del" href="forum_newpostUW.php?del='+pid+'">Cancella Topic</a>'+
    '<div class="ajaxResult"></div>'+
    '</div>');
    
    $(newDialog).dialog({ hide: 'fade',
                          show: 'fade',
                          open: function(event, ui) {
                            
                            $(".post_del").click(function(){
                            	url=$(this).attr('href');
							    if(confirm('Sei Sicuro di voler cancellare questo Topic ed i relativi post?')){
							    	$.get(url,function(data){
							    		//alert($(data).filter('span#deletedpost').html());
							    		$(data).filter('span#deletedtopic').each(function(){
    										$('#'+$(this).html()).fadeOut().remove();
    										$(".ajaxResult").html("Topic Cancellato.");
    							  		});   		
							    		
							    	});
							    }
							    return false;
                            });
                            
                            $(".ajaxReq").click(function(event){
                              $.post(this.href, function(data){
                                  
                                  $(data).filter('span#symbol_lock').each(function(){
                                    $("#l_"+pid+".symbol_lock").empty();
                                    $("#l_"+pid+".symbol_lock").append($(this).html());
                                  });
                                  
                                  $(data).filter('span#symbol_stick').each(function(){
                                    $("#s_"+pid+".symbol_stick").empty();
                                    $("#s_"+pid+".symbol_stick").append($(this).html());
                                  });
                                  
                                  $(".ajaxResult").html(data);
                                 
                               });
                              return false;
                            });
                          }
                        });
    
   });
   
   
   $("#reply_box").on('submit', "#form_postForum", function(){
		rowArr = $(this).serializeArray(); 
     	$.post($(this).attr("action"),rowArr,function(data2){
    		$('.forum_table').append(data2);
    		$('#message').val("");
    		$('#newTopicTitle').val("");
    		$("#reply_box").fadeOut();
	  	});
		
		return false;    	
    });
   
   
   $("#reply_box").load("forum_newpostUW.php?b=<?=$_REQUEST['b']?>");
   
   $("#openNew").click(function(){
   	
   	
   	 $("#reply_box").fadeToggle('slow',function(){
   	 	$('#newTopicTitle').focus();
   	 });
   	 $('#content').animate({scrollTop: $("#reply_box").offset().top},'slow');
   	
   	return false;
   });
   
});

</script>
</head>

<body class="forum">
<div class="centertxt forum_spacer"><h2><a href="forum_boards.php"><?=$forum?></a></h2>
<?php

if (!isset($_REQUEST['b']) || $_REQUEST['b']=='' ){
	echo "<br />Devi specificare la board";
	exit();	
}

$ForumBoard=new ForumBoard(null,$_REQUEST['b']);
$ForumBoard->checkExists();

$start_pag=0;

if ($_REQUEST['p']!='' && $_REQUEST['p']>0){
	$start_pag=$_REQUEST['p']*($topics_per_pag);
}

if (!$ForumBoard->readTopics($MyChar_obj,null,true,false,$start_pag,$topics_per_pag))
  echo "Accesso Negato";

$canAdmin=false;
if ($_SESSION['modlevel'] >= $acc_admin_forumtopics_required)
  $canAdmin=true;


  
$pathT="<a href=\"forum_boards.php\">$forum</a> / {$ForumBoard->getName()}";


?>
</div>

<div class="forum_postBoxW">
  <?php echo $pathT; ?>
</div>
<div class="forum_postBoxW">
  <a id="openNew" href="#"><img src="images/icons/document.png" border="0" />Nuovo Topic</a>
</div>

<table class="forum_table clearborder panel_bg">
  <tr class="dark_bg clearborder">
    <th width="300">Oggetto</td>
    <th width="200">Autore</td>
    <th width="100">Risposte</td>
    <th width="200">Ultima Risposta</td>
  </tr>
  <?php
  
  foreach($ForumBoard->getTopics() as $key=>$value){

    $value->loadChildren(true);
    $value->countChildren();
    $lastChild=$value->getLastChild();
    
    $lastRpl="-";
    foreach($value->getLastChild() as $k=>$v){
      $tmpChar=new Character(null,$v->getAuthorName());
      $lastRpl=$tmpChar->getCharNameLink()."<br />il ".itaTime($v->getDate());
    }
    
    $sticked="";
    if($value->getSticky()==1){
      $sticked="<img src=\"images/icons/megaphone.png\" border=\"0\" />";
    }
    
    $locked="";
    if($value->getLocked()==1){
      $locked="<img src=\"images/icons/lock-closed.png\" border=\"0\" />";
    }
    
    $admOpt="";
    if($canAdmin){
      $admOpt="<span class=\"admin_gear\" pid=\"{$value->getId()}\"><img src=\"images/icons/gear-gold.png\" border=\"0\" /></span>";
    }
	
	$recent="";
	if($value->isRecent()){
		$recent="<span class=\"pm_counterW floatleft\"><div class=\"pm_counter\">!</div></span>";
	}
    
    $tmpChar2=new Character(null,$value->getAuthorName());
    
    echo "<tr id=\"forumTID_{$value->getId()}\">
            <td>$recent $admOpt <span id=\"l_{$value->getId()}\" class=\"symbol_lock\">$locked</span> <span id=\"s_{$value->getId()}\" class=\"symbol_stick\">$sticked</span> <a href=\"forum_posts.php?f={$value->getId()}&b={$value->getBoard()}\">{$value->getTitle()}</a></td>
            <td>{$tmpChar2->getCharNameLink()}<br />il ".itaTime($value->getDate())."</td>
            <td>{$value->getNumChild()}</td>
            <td>$lastRpl</td>
          </tr>";
    
  }
  
  ?>
</table>
<div id="reply_box" style="display:none;width:85%;margin:0 auto;"></div>
<table width="600" border="0" class="center" cellpadding="0" cellspacing="0">
  <tr>
    <td>Page: 
	<?php
	$ForumBoard->countTopics();
  
  //echo $ForumBoard->getNumTopics();
	
	$count_pag=$ForumBoard->getNumTopics()/$topics_per_pag;
	
	$n_pages=ceil($count_pag);
	
	for ($i=0;$i<$n_pages;$i++){
		$curpage=$i+1;  
		if ($i==$start_pag/$posts_per_pag){
			echo "[$curpage]";
		}else{
			echo "<a href=\"".$_SERVER['PHP_SELF']."?p=$i&b={$_REQUEST['b']}\">$curpage</a> ";
		}
	}
	
	?></td>
  </tr>
</table>
<div id="dialog" title="Inserisci nuovo Topic" style="diplay:none"></div>
</body>
</html>
