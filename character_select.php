<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */
 
if(!isset($_SESSION)){
session_start();
} 

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");

//se l'account non è autenticato, fermo tutto
if (!isset($_SESSION['username']) || $_SESSION['username']=='' || !isset($_SESSION['id']) || $_SESSION['id']<=0) {
  header("location: expired.php"); 
  echo '<script type="text/javascript">window.location.href=\'expired.php\';</script>';
  exit();
}

$myAcc=new Account(null,$_SESSION['id']);
$myAcc->parseFromDb();



//se l'account è autenticato carico la lista dei pg
$charList=new CharacterList();

$charList->readFromDb(null,$_SESSION['id']);

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<title>Selezione Personaggio</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">
$(document).ready(function(){
          
  $( ".buttonify" ).button();
  
});
</script>
</head>

<body>
<h1 class="centertxt">Schermata di selezione del personaggio</h1>

<div class="loginbox roundcorner clearborder panel_bg" style="position:relative;">
<?php

//calcolo il massimo di pg creabili, sulla base del livello utente e dell exp
$max_charnum=1;

// sblocco eventuali nuovi slot sulla base dell'esperienza
$totalEarnedXp = $myAcc->getTotalEarnedXp();
$add_charnum=0;
foreach($force_multichar_on_px as $k => $v){
	if($totalEarnedXp>=$k)
		$add_charnum=$v;
}
$max_charnum+=$add_charnum;

//se è permesso il multichar lo abilito
if ($allow_multichar || $myAcc->getModLevel()>=$admin_always_allow_multichar)
	$max_charnum=$max_multichar;

if ($charList->CountChars()<=0){ //non ci sono personaggi che fanno capo al dato account
  echo "Nessun personaggio presente.";
  
}else{
  //c'è almeno un personaggio
  echo "
        <div>Puoi avere un totale di {$max_charnum} Personaggi.</div><ul class=\"margin\">";
  
  
  foreach($charList->getChars() as $k=>$v){
        
    //$v=new Character();
      
    if ( $max_charnum==1 || ( isset($_REQUEST['charid']) && $v->getCharId()==$_REQUEST['charid']) ){
      
	  
      $_SESSION['char_id']=$v->getCharId();
      $_SESSION['char_name']=$v->getCharName();
      $v->writeLocation(0);
      //header( "Location: layout.php" );
      echo '<script type="text/javascript">window.location.href=\'game\';</script>';
      break;//interrompo gli altri, in realtà non serve perchè c'è già il comando header
    }
    
    echo "<li><a href=\"character_select.php?charid={$v->getCharId()}\">{$v->getCharName()}</a></li>\n";
    
  }
  
  echo "</ul>";
}

if ($max_charnum > $charList->CountChars() || $myAcc->getModLevel()>=$admin_always_allow_multichar){
	echo "<div style=\"position:absolute; text-align: center; bottom:20px;left:0; right:0;\"><a href=\"register_char.php\" class=\"buttonify\">Crea un nuovo personaggio</a></div>";	
}


?>


</div>

</body>
</html>