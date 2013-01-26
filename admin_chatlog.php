<?php
/*  I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/chat_lib.php");

if(!isset($_SESSION))
{
session_start();
} 

logged();

$MyChar_obj=new Character(null,$_SESSION['char_name']);
$MyChar_obj->parseFromDb();

if(!($MyChar_obj->exists())){
  echo "Personaggio inesistente.";
  exit();  
}

$_SESSION['modlevel']=$MyChar_obj->Account()->getModLevel();

if ($admin_view_chat_required>$_SESSION['modlevel']){
  echo "Accesso negato, permessi insufficienti.";
  exit();
}


if (isset($_REQUEST['chat']) && $_REQUEST['chat']!='' && isset($_REQUEST['logday']) && $_REQUEST['logday']!=''){
  	
echo "Log di chat per il giorno: {$_REQUEST['logday']} , ID Stanza: {$_REQUEST['chat']}";
	
  $room_id=$_REQUEST['chat'];
  $logday=$_REQUEST['logday'];
  $room_obj= new Room($room_id,null,null,null,$logday,-1);
  
  if (is_null($room_obj->getId())){
    echo "Stanza inesistente.";
    exit();
  }
  if ($room_obj->getPrivate()==1 && $room_obj->getUser_rights()==0){
    echo "Accesso alla stanza negato.";
    exit();
  }
  
  $room_obj->showChat(true);
  exit();

}

$ChatL=new ChatList();
$ChatL->readFromDb();

foreach ($ChatL->getRooms() as $k=>$v){
  
  $optL.="<option value=\"{$v->getId()}\">{$v->getName()}</option>";
  
}

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title><?php echo $nome_land;?></title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

</head>
<body>
<script>
function doDatePicker(){
	$( ".logday" ).datepicker({
	    changeMonth: true,
	    changeYear: true,
	    dateFormat: 'yy-mm-dd'
  	});
}
$(function() {
	
	$("#view_chatlog").live('submit', function() {
		rowArr = $(this).serializeArray(); 
      	$.post($(this).attr("action"),rowArr,function(data2){
    		$('.result').html(data2);
	  	});
	  	return false;
	});
	  
	
	doDatePicker();	
});
</script>
<h2>Visione dei Log di Chat</h2>
<form id="view_chatlog" class="special" name="view_chatlog" action="admin_chatlog.php" method="post">
<div>Stanza: <select name="chat" id="chat"><?php echo $optL; ?></select></div>
<div>Giorno: <input type="text" name="logday" class="logday" value="" /></div>
<input type="submit" value="Continua" />
</form>  
<div class="result">

</div>
</body>
</html>
