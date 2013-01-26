<?php
/*  I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/pm_lib.php");

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

if ($admin_view_pm_required>$_SESSION['modlevel']){
  echo "Accesso negato, permessi insufficienti.";
  exit();
}

$sender=null;
$recipient=null;

if(isset($_REQUEST['sender']) && $_REQUEST['sender']!=''){
  
  $tmpC=new Character(null,$_REQUEST['sender']);
  $tmpC->checkExistance();
  $sender=$tmpC->getCharId();
  
}
  
if(isset($_REQUEST['recipient']) && $_REQUEST['recipient']!=''){
    
  $tmpC=new Character(null,$_REQUEST['recipient']);
  $tmpC->checkExistance();
  $recipient=$tmpC->getCharId(); 

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
<script>
$(function() {
	$( "#search_field" ).autocomplete({
			source: "char_list.php",
			minLength: 2,
			delay: 200
	});
	
});
</script>
<body>

<h2>Visione dei Messaggi Privati</h2>
<form method="get" action="admin_pm.php" id="search" autocomplete="off">
	<input id="search_field" name="recipient" type="text" size="40" value="<? echo $_REQUEST['recipient']; ?>" placeholder="Utente da spiare..." />
</form>

<div class="result" style="margin: 0 auto; margin-top: 20px; width: 90%;">
<?php
 
  $strp=0;
  
  $pmList= new PrivateMessageList($recipient,null);

  foreach ($pmList->PMList() as $key => $PM_obj) {
	  	//$PM_obj=new PrivateMessage();
       	$pm_arr_rply=explode(',',$PM_obj->getReplyTo());
	   
		echo "<div class=\"dark_bg center centertxt clearborder\" style=\"margin: 10px; padding-top:10px;\">
				<div class=\"panel_bg center centertxt roundcorner clearborder pm_cronologia\">Cronologia della discussione <strong>{$PM_obj->getSubject()}</strong></div>
            	<div style=\"padding:15px;\">";
            	
		for($i=0;$i<count($pm_arr_rply);$i++){
	        if ($pm_arr_rply[$i]==0)
	          continue;
	        $PM_subobj=new PrivateMessage(null,$pm_arr_rply[$i]);
	        $PM_subobj->pm_superread();
			
			if($PM_obj->getSender()==$PM_subobj->getSender()){
			$asw="answerBox2";
			}else {$asw="answerBox1";}
			
			echo 	'<div class="panel_bg roundcorner clearborder answerBox clearboth '.$asw.'">
		  				<div class="answerBoxDate">'.itaTime($PM_subobj->getSentDate()).'</div>
		  				<div class="answerBoxAuth">'.$PM_subobj->getSenderNameLink().' -&gt; '.$PM_subobj->getRecipientNameLink().'</div>
		  				<div class="answerBoxMsg">'.acapo($PM_subobj->getMessage()).'</div>
		  			 </div>';
			
				
            	
		}
		
		
		echo 	'<div class="panel_bg roundcorner clearborder answerBox clearboth answerBox2">
		  				<div class="answerBoxDate">'.itaTime($PM_obj->getSentDate()).'</div>
		  				<div class="answerBoxAuth">'.$PM_obj->getSenderNameLink().' -&gt; '.$PM_obj->getRecipientNameLink().'</div>
		  				<div class="answerBoxMsg">'.acapo($PM_obj->getMessage()).'</div>
		  		</div>';
					
        echo "	</div>
           	  </div>";
     
  }
?>
</div>
</body>
</html>
