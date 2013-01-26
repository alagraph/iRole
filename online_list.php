<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");

if(!isset($_SESSION))
{
session_start();
}
 
if ($_REQUEST['request']){

	show_onlineList($_REQUEST['room'],1);
		  
	exit();

}
?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Elenco Presenti</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script>

function onlinelist(){
	
	$.post("<?php echo $_SERVER['REQUEST_URI']; ?>", {request: '1'}, function(data){
		$("#online_list_container").html(data);
	});

}

$(document).ready(function(){
    
    onlinelist();
  	online_timer = setInterval(onlinelist, 5000);
  	
  	
});
</script>
</head>

<div id="online_list_container" class="center width90 panel_bg clearborder roundcorner"></div>
</html>
