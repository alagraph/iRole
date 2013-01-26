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

$folder=0;
if (isset($_REQUEST['f']) && $_REQUEST['f']=='1'){
	$folder=1;
}

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>PM List</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">
/**** PM ****/

var cur_view;
var uipan

function refresh(){
   	//post_page(null,cur_view);
   	$(uipan).load(cur_view);
}

function deletePm(url,id){
	
	$.post(url);
	$('#'+id).remove();

}

function creaN(url){
	$("#imessage").attr('src',url);
}
    
$(function() {
	
	
	/* MESSAGGI PRIVATI */
	$( "#pm_menu" ).tabs({
			ajaxOptions: {
				error: function( xhr, status, index, anchor ) {
					$( anchor.hash ).html("Si è verificato un errore." );
				}
			},
			load: function(event,ui){
				$("#pm_unread").html($(ui.panel).find("#pm_countH").html());
			},
			select: function(event, ui) {
		        
		        var url = $.data(ui.tab, 'load.tabs');
		        
		        if($(ui.tab).attr("id")=='forceMexNew'){
		        	creaN(url);
		        	return false;
		        }
		        uipan=ui.panel
		        cur_view=url;
		        return true;
		    }
	});	
	
	$("#pm_wrapper").on('submit','#search', function(){
		
		cur_view="pm_slist.php?s="+$("#search_field").val();
		$(uipan).load(cur_view);
		return false;
		
	});
	
	  
	
	$( "#pm_menu" ).tabs("select" , 1 );
	
	//pm_timer = setInterval(refresh, 7000);
		  
});
</script>
</head>

<body>
<div id="pm_wrapper">
	<div>
	<form method="get" action="#" id="search" autocomplete="off">
  		<input id="search_field" name="q" type="text" size="40" placeholder="Cerca..." />
	</form>
	</div>
<div id="pm_menu">
	<ul id="pm_menu_items">
		<li><a href="pm_new.php" id="forceMexNew">Scrivi <?php echo $missive;?></a></li>
		<li><a href="pm_slist.php?p=0&f=0" class="menuitem"> <?php echo $missive_plurali;?> <span id="pm_unread"></span></a></li>
		<!--<li><a href="pm_slist.php?p=0&f=-1" class="menuitem"> <?php echo $missive_plurali;?> Inviati</a></li>-->
		<li><a href="pm_slist.php?p=0&f=1" class="menuitem">Cestino</a></li>
	</ul>
</div>
</div>
</body>
</html>
