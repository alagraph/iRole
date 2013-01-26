<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Validazione Account</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>
<div class="center centertxt">
  <?php

if (isset($_REQUEST['a'])){
	$acc=$_REQUEST['a'];
	$query="SELECT * FROM account WHERE username='$acc'";	
	$result=mysql_query($query) or die(mysql_error());
	
	if (mysql_num_rows($result)==1){
		$row=mysql_fetch_array($result);
	
		$code=$row['unlock_code'];
		$mail=$row['email'];
		$username=$row['username'];
		
		if ($code<>'' && $request_validation){
			$mail_body=$mail_message;
			$mail_body.="<br />Clikka sul link sottostante per abilitare l'account e iniziare a giocare.<br /><br />";
			$mail_body.="<a href=\"".$url_land."/validate.php?u=$username&i=$code\">";
			$mail_body.=$url_land."/validate.php?u=$username&i=$code</a><br /><br />";
			
			send_mail($mail,"Codice attivazione ".$nome_land,$mail_body);
			
			echo "<p>Una nuova email di attivazione è stata inviata all'indirizzo $mail.<br />Controlla anche la posta indesiderata!</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
		
		}else{
			echo "<p>Account già abilitato.<br />Puoi iniziare a giocare.</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
		}
	}else{
		echo "<p>Spiacente, l'account $acc è inesistente.</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
	}

}else{
	if (!isset($_REQUEST['u']) || !isset($_REQUEST['i'])){
		echo "<p>Parametri forniti non corretti.</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
	}else{

		$acc=$_REQUEST['u'];
		$code= $_REQUEST['i'];

		$query="SELECT * FROM account WHERE username='$acc'";	
		$result=mysql_query($query) or die(mysql_error());

		if (mysql_num_rows($result)==1){
			$row=mysql_fetch_array($result);
	
			if ($row['unlock_code']=='' || !($request_validation)){
				echo "<p>L'account è già stato validato. Puoi già giocare!</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
			}elseif ($row['unlock_code']==$code){
				mysql_query("UPDATE account SET unlock_code='' WHERE id=".$row['id']) or die(mysql_error());
				echo "<p>Account validato con successo. Puoi iniziare a giocare!</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
			}else{
				echo "<p>Spiacente, il codice di attivazione non è valido.</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
			}
		
		}else{
			echo "<p>Spiacente, l'account è inesistente.</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
		}
	}
}
?>
</div>
</body>
</html>
