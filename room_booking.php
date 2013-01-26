<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
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



?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Gestione Stanze</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.js"></script>
</head>

<body>
<?php
switch ($_REQUEST['a']) {
	case 'rent':
		
		break;
	case 'buy': //creo una stanza, con scadenza
		
		break;
	default:
		
		break;
}
?>

<div>
<form action="room_booking.php?a=rent" method="post">
<h2>Riserva stanza in Hotel</h2>
Seleziona una stanza
<?php


?>
</form>
</div>

<div>
<form action="room_booking.php?a=buy" method="post">
<h2>Acquista Appartamento</h2>
</form>
</div>


</body>
</html>