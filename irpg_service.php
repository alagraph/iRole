<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if(!IS_AJAX) {
   exit("Pagina caricabile solo con ajax.");
}

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/pm_lib.php");
require_once("libs/docs_lib.php");
require_once("libs/chat_lib.php");

//comunico alla pagina che utilizzerò delle session
if(!isset($_SESSION))
{
session_start();
} 

logged();

$ref_id=$_SESSION['id'];
if ($bind_pm_char)
	$ref_id=$_SESSION['char_id'];


$newPM=pm_countNew($ref_id,true);

echo "<span id=\"newPM\" last_pm=\"{$newPM[0]}\">{$newPM[1]}</span>";


if(isset($_REQUEST['n'])){
	$news1=new Document(null,5);
	$news2=new Document(null,6);
	
	$news1->readFromDb();
	$news2->readFromDb();
	
	echo "<span id=\"news1\">{$news1->getContent()}</span>";
	echo "<span id=\"news2\">{$news2->getContent()}</span>";
}

if(isset($_REQUEST['o'])){
	
	echo "<div id=\"onlineResult\">";
	show_onlineList($_REQUEST['o'],1);
	echo "</div>";
}

if(isset($_SESSION['room_id'])){
	
	$room=new Room(intval($_SESSION['room_id']));
	$room->readFromDb($room->getId());
	
	$img=$room->getThumb();
	if($img!='')
		echo "<span id=\"roomthumb\">{$room->getThumb()}</span>";
	
}


?>
