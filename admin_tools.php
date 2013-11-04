<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");

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
<title>Admin Tools</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>

<body>

<div class="panel_bg width90 center roundcorner clearborder">
	<h2>Pagina di Gestione</h2>
  <ul>
    <?php if($MyChar_obj->getMasterLevel()>0 || $admin_add_px_required<=$_SESSION['modlevel']){ ?>
      <li><a href="admin_exp.php" class="mainMenu">Gestione Quest</a></li>
    <?php } 
     if($MyChar_obj->Account()->getModLevel()>0){ ?>
      <li><a href="admin_groups.php" class="mainMenu">Gestione Gruppi</a></li>
      <li><a href="admin_ability.php" class="mainMenu">Gestione Abilità</a></li>
      <li><a href="admin_items.php" class="mainMenu">Gestione Oggetti</a></li>
      <li><a href="admin_docs.php" class="mainMenu">Gestione Documenti</a></li>
      <li><a href="admin_pm.php" class="mainMenu">Visione Messaggi Privati</a></li>
      <li><a href="admin_chatlog.php" class="mainMenu">Visione log Chat</a></li>
      <li><a href="admin_setrights.php" class="mainMenu">Imposta permessi utente</a></li>
      <li><a href="admin_setconfig.php" class="mainMenu">Opzioni land</a></li>
    <? } ?>
      <li><a href="item_shop.php" class="mainMenu">Acquista Oggetti </a></li>
      <!--<li><a href="meteo.php" class="mainMenu">Meteo</a></li>-->
  </ul>
</div>
</body>
</html>