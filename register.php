<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/ability_lib.php");
require_once("libs/recaptcha_lib.php");



define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');


function username_r($v){
	//controllo se lo username c'è già
	$char=new Account(null,null,$v);
	$char->parseFromDb();
	
	if ($char->exists()){
		echo 'Username in uso, scegline un altro<br />';
		return false;
	}
	
	return true;
}

function email_r($v){
	
	if (!validate_email($v)){
			echo 'Formato email non valido<br />';
			return false;
		}else{ // il formato dell'email è valido, faccio il lock e controllo se l'email inserita c'è già
			
			//controllo se la mail c'è già
			if (email_exist($v)){
				echo 'Email in uso<br />';
				return false;
			}
			
		}
	return true;
	
}

if(IS_AJAX){ //faccio controllo asincrono

	if(!empty($_REQUEST['username'])){
		
		if(username_r($_REQUEST['username'])){
			echo '<img src="images/icons/ok.png" alt="ok" />';
		}
		
	}
	
	if(!empty($_REQUEST['pass1'])){
		if(!validate_password($_REQUEST['pass1'])){
			echo "La password deve essere lunga da 4 a 15 caratteri alfanumerici";
		}else{
			echo '<img src="images/icons/ok.png" alt="ok" />';
		}
	}
	
	if(!empty($_REQUEST['email'])){
		
		if(email_r($_REQUEST['email'])){
			echo '<img src="images/icons/ok.png" alt="ok" />';
		}
		
	}
	
	exit();
	
}

	
?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Register</title>
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
	
	$(".ajax_c").keyup(function(){
		
		
		name=$(this).attr("name");
		val = '='+$(this).val(); 
    	
    	$.get("register.php?"+name+val,function(data){ $("#"+name+"_r").html(data); } );
    
		
	});
	
	$(".pass2_c").keyup(function(){
		
		if( $("#pass1").val() != $("#pass2").val() ){
			 $("#pass2_r").html("Le password non combaciano");
		}else{
			 $("#pass2_r").html('<img src="images/icons/ok.png" alt="ok" />');	
		}
    
		
	});
	
	
});
</script>
</head>

<body>

  <h1 class="centertxt">Pagina di registrazione</h1>
<div class="centertxt">
  <?php

if (!$allow_reg)
{echo "La registrazione non è al momento possibile."; exit();}

//eseguo le operazioni di registrazione
if (isset($_REQUEST['r']) && $_REQUEST['r']==1){

	$flag=0;
	
	$privatekey = "6LeKK8USAAAAAFY1jKvnydr1kBy_VkFtmjA0mIab";
  	$resp = recaptcha_check_answer ($privatekey,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

  	if (!$resp->is_valid) {
    	// What happens when the CAPTCHA was entered incorrectly
    	echo "Il captcha inserito non è valido. ({$resp->error})<br />";
		$flag=1;
  	}
	
	if (!isset($_REQUEST['username'])) {
		echo 'Inserire lo username<br />';
		$flag=1;
	}else {
			if(!username_r($_REQUEST['username'])) $flag=1; 
	}
	
	if (!isset($_REQUEST['pass1'])) {
		echo 'Inserire la password<br />';
		$flag=1;
	}else {
		if (!validate_password($_REQUEST['pass1'])){
			echo 'La password deve essere lunga da 4 a 15 caratteri alfanumerici';
			$flag=1;
		}
	}
	
	if (!isset($_REQUEST['pass1']) || !isset($_REQUEST['pass2']) || $_REQUEST['pass1']!=$_REQUEST['pass2']) {
		echo 'Le password non combaciano<br />';
		$flag=1;
	}
	
	if (!isset($_REQUEST['email'])) {
		echo 'Inserire l\'email<br />';
		$flag=1;
	}else {
		
		if(!email_r($_REQUEST['email'])) $flag=1; 
	}
	
	

	if ($flag==0) { //tutto è ok, procedo alla scrittura dal database e all'invio dell'email
	
		$mail_body=$mail_message;
		$activation_key='';			
		
		if ($request_validation){
			
			//se è richiesta l'attivazione via email creo la chiave
			$activation_key=rnd_string(20);
			
			$mail_body.="<br />Clikka sul link sottostante per abilitare l'account e iniziare a giocare.<br /><br />";
			$mail_body.="<a href=\"".$url_land."/validate.php?u=".$_REQUEST['username']."&i=$activation_key\">";
			$mail_body.=$url_land."/validate.php?u=".$_REQUEST['username']."&i=$activation_key</a><br /><br />";
		}
					
		$mail_body.="I tuoi dati d'accesso sono..<br /><br />Username: ".$_REQUEST['username']."<br />Password: ".$_REQUEST['pass1']."<br /><br />Buon Divertimento!";
					
		if (@send_mail($_REQUEST['email'],"Registrazione ".$nome_land,$mail_body)){
			echo "<p>Un'email ti è stata inviata all'indirizzo fornito.<br />Controlla anche la posta indesiderata!</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
		}else{
			//tento un secondo invio
			if (!(@send_mail($_REQUEST['email'],"Registrazione ".$nome_land,$mail_body))){
				echo "<p>L'account è stato creato, ma non è stato possibile inviare l'email all'indirizzo ".$_REQUEST['email'].".<br />Contatta la gestione all'indirizzo <a href=\"mailto:$admin_mail\">$admin_mail</a> per risolvere il problema</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
			}else{
				echo "<p>Un'email ti è stata inviata all'indirizzo fornito.<br />Controlla anche la posta indesiderata!</p><p><a href=\"".$url_land."\">Torna alla homepage</a></p>";
			}
		}
			
			// sono riuscito ad inviare l'email, posso salvare tutto sul database
      $rowAcc=array();
      $rowAcc['username']=$_REQUEST['username'];
      $rowAcc['clear_password']=$_REQUEST['pass1'];
      $rowAcc['email']=$_REQUEST['email'];
      $rowAcc['unlock_code']=$activation_key;
      
      $newAcc=new Account($rowAcc);
      $newAcc->writeToDb();
			
			
			exit();
			
	}

}


?>
  
</div>
<div class="roundcorner panel_bg clearborder width90 center">
<form id="form1" name="form1" method="post" action="register.php?r=1">
<table width="600" border="0" class="center" cellpadding="3" cellspacing="3">
  <tr>
    <td width="50%"><div align="right">Nome Account</div></td>
    <td>
        <input name="username" type="text" id="username" value="<?=$_REQUEST['username'] ?>" maxlength="50" class="ajax_c" /> <span id="username_r" class="errormsg"></span></td>
  </tr>
  <tr>
    <td><div align="right">password (da 4 a 15 caratteri) </div></td>
    <td><input name="pass1" type="password" id="pass1" value="<?=$_REQUEST['pass1'] ?>" maxlength="50" class="ajax_c pass2_c" /> <span id="pass1_r" class="errormsg"></span></td>
  </tr>
  <tr>
    <td><div align="right">conferma password</div></td>
    <td><input name="pass2" type="password" id="pass2" value="<?=$_REQUEST['pass2'] ?>" maxlength="50" class="pass2_c" /> <span id="pass2_r" class="errormsg"></span></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <?php
  if ($request_validation){
  ?>
  <tr>
    <td colspan="2"><div class="centertxt">Al termine della registrazione ti verr&agrave; inviata un'email all'indirizzo segnalato per abilitare l'account creato.</div></td>
  </tr>
  <?php
  }
  ?>
  <tr>
    <td><div align="right">email</div></td>
    <td><input name="email" type="text" id="email" value="<?=$_REQUEST['email'] ?>" maxlength="50" class="ajax_c"/> <span id="email_r" class="errormsg"></span></td>
  </tr>
  <tr>
    <td colspan="2"><div class="center" style="margin-top:30px; margin-bottom: 30px; width: 320px;">
    	<?php
          $publickey = "6LeKK8USAAAAAP2SKq1h1ZT5_X6KTT1C5I9AoGef"; // you got this from the signup page
          echo recaptcha_get_html($publickey);
        ?>
    </div></td>
  </tr>
  <tr>
    <td colspan="2"><div class="centertxt"><input type="submit" name="Submit" value="Prosegui" class="buttonify" /></div></td>
  </tr>
</table>
</form>
</div>
</body>
</html>
