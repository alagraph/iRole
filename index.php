<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/logs_lib.php");

if(!isset($_SESSION))
{
session_start();
} 

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>index</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">
$(function() {
	
	$( ".buttonify" ).button();
	$( ".buttonset" ).buttonset();
	
});
</script>
</head>

<body class="home">
	<h1 class="centertxt" style="margin-top:40px;"><?php echo $nome_land; ?></h1>
<div class="loginbox roundcorner clearborder panel_bg">
<?php
$error = '';
if (isset($_REQUEST['l']) && $_REQUEST['l']==1){ //sta richiedendo di fare il login
	
	//per prima cosa autentico l'account
	if (isset($_REQUEST['login_username']) && isset($_REQUEST['login_password'])){
	  $rowAcc=array();
    $rowAcc['username']=$_REQUEST['login_username'];
    $rowAcc['clear_password']=$_REQUEST['login_password'];
    
    $accLog=new Account($rowAcc);
    $accLog->parseFromDb(true); //true significa che richiedo un login
    
    if($accLog->exists()){ //esiste, devo controllare che sia autorizzato
        
      if ($accLog->getUnlockCode()!='' && $request_validation){ //deve ancora abilitare l'account
        $error= "<p>Non hai ancora attivato l'account.<br />
                  <a href=\"validate.php?a=".$accLog->getUsername()."\">Clikka qui per richiedere nuovamente l'email di attivazione</a>
                 </p>\n";
      }else{ //l'account è abilitato
            
        //finalizzo l'autenticazione  
        $_SESSION['username']=$accLog->getUsername();
        $_SESSION['id']=$accLog->getId(); 
        
        //scrivo il log di accesso
        $arrL=array("acc_id" => $accLog->getId());
        $logIp=new LogIp($arrL);
        $logIp->writeToDb();
        
        //reindirizzo alla schermata di selezione del personaggio (anche se il personaggio è solo uno)
        echo '<script type="text/javascript">window.location.href=\'character_select.php\';</script>';
        
      }
      
    }else{ //non esiste: lo username o la password sono errati
      $error = "Username o password non corretti.";
    }
    
	}elseif(s){ //se non sono settati potrei aver già in memoria l'account
	  
	}else{ // se non ce l'ho nemmeno in memoria, do errore
	  $error = "Devi inserire username e password.<br />";
	}
	
}

echo "<div class=\"centertxt\">$error</div>";
?>
  <form id="form1" name="form1" method="post" action="index.php?l=1">
  <table style="width:100%;" border="0" cellspacing="3" cellpadding="3">
    <tr>
      <td><div align="right">username</div></td>
      <td><input name="login_username" type="text" id="login_username" /></td>
    </tr>
    <tr>
      <td><div align="right">password</div></td>
      <td><input name="login_password" type="password" id="log_password" /></td>
    </tr>
    <tr>
      <td colspan="2"><div class="centertxt">
        <input type="submit" name="Submit" value="Entra" class="buttonify"/>
      </div></td>
    </tr>
  </table>
  </form>
  <p class="centertxt">Non sei ancora registrato? <a href="register.php">Registrati Ora!</a></p>
  <p class="centertxt">Dimenticato la password? <a href="pass_recovery.php">Recuperala!</a></p>
  
  </div>
  <div style="position:absolute: bottom:0;width:50%;margin:70px auto; text-align: center;">
  	Sito ottimizzato per
  	<img style="width:40px;vertical-align:middle;" src="images/icons/firefox.png" alt="Firefox"/>
  	<img style="width:40px;vertical-align:middle;" src="images/icons/chrome.png" alt="Chrome"/>
  	<img style="width:40px;vertical-align:middle;" src="images/icons/safari.png" alt="Safari"/>
  </div>
  
</body>
</html>
