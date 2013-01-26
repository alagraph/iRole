<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/chat_lib.php");
require_once("libs/character_lib.php");

//comunico alla pagina che utilizzerò delle session
if(!isset($_SESSION))
{
session_start();
} 

logged();

//prendo l'ora dalla sessione
if (isset($_SESSION['date_chat'])){
	$date_chat=$_SESSION['date_chat'];
}else{
	$date_chat=date("YmdHis",strtotime("-$chat_offset minute"));
	$_SESSION['date_chat']=$date_chat;
}

//prendo il nome della stanza dalla sessione
if (isset($_SESSION['date_chat'])){
	$room_id=$_SESSION['room_id'];
}
else{
	echo "errore";
	exit();
}

//prendo il nome dell'istanza, se c'è
if(isset($_SESSION['chat_instance'])){
	$room_instance=$_SESSION['chat_instance'];
}else{
	$room_instance=0;
}

//valido la stanza..potrebbe non esistere, o non essere autorizzata all'utente
$room_obj= new Room($room_id,null,$_SESSION['char_id'],null,$date_chat,$room_instance);
$char_obj= new Character($_SESSION['char_id']);

if (is_null($room_obj->getId())){
	echo "Stanza inesistente.";
	exit();
}
if ($room_obj->getPrivate()==1 && $room_obj->getUser_rights()==0){
	echo "Accesso alla stanza negato.";
	exit();
}

//se ci sono comandi di invito li eseguo
if(isset($_REQUEST['accessList']) && $_REQUEST['accessList']!=''){
					
	echo "<div class=\"accessList\">";

		//se ci sono comandi di aggiunta/rimozione, li eseguo
	if(isset($_REQUEST['promote_name']) && isset($_REQUEST['promote_level'])){
		
		$tmpChar=new Character(null,$_REQUEST['promote_name']);
		$tmpChar->checkExistance();
		
		if($tmpChar->exists())
			if(!$room_obj->grantAccess($tmpChar->getCharId(),$_REQUEST['promote_level']))
				echo "Impossibile impostare {$_REQUEST['promote_name']} a {$chatPrivilegesArr[$_REQUEST['promote_level']]}";
		
	}		
			
		
		
	echo "<table>
			<tr><th>Personaggio</th><th>Accesso</th></tr>";
	foreach($room_obj->readAccess() as $k=>$v){
		
		
		echo "<tr><td>{$v['char']->getCharName()}</td><td>{$chatPrivilegesArr[$v['level']]}</td></tr>";
		
		
	}
	echo "</table></div>";
	
	//se mi richiedono la accessList non mostro la chat
	exit();
	
}


//scrivo la locazione attuale del pg
$char_obj->writeLocation($room_obj->getId());


$writeMsg=$_REQUEST['roll_ab']!='' ? '.roll_ab '.$_REQUEST['roll_ab'] : $_REQUEST['messaggio'];

//se ci sono dati li posto
if (strlen($writeMsg)>0){
	$chat_tag=null;
	if (isset($_REQUEST['tag']) && strlen($_REQUEST['tag'])>0)
		$chat_tag=$_REQUEST['tag'];
  $room_obj->addMessage($writeMsg,$chat_tag);
  
}

//in ogni caso leggo

$room_obj->showChat();

?>