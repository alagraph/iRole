<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Password Recovery</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
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
	<h1 class="centertxt" style="margin-top:40px;"><?php echo $nome_land; ?></h1>
	<form action="pass_recovery.php" method="post">
	<div class="loginbox roundcorner clearborder panel_bg">
		<h2>Utilità di recupero Password</h2>
		<?php
		
$msg="<h3>Inserisci il nome dell'account o l'email</h3>
			<div class=\"transferResult\"></div>
			<table style=\"width:100%\">
				<tr><td>Account</td><td><input name=\"account\" type=\"text\"/></td></tr>
				<tr><td>Email</td><td><input name=\"email\" type=\"text\"/></td></tr>
				<tr><td>&nbsp;</td><td><input type=\"submit\" value=\"Conferma\" class=\"buttonify\" /></td></tr>
			</table>";
		
if(isset($_REQUEST['account']) || isset($_REQUEST['email']) ){ //mando le istruzioni di recupero via email


	if(isset($_REQUEST['account']) && $_REQUEST['account']!=''){
		
		$acc=new Account(null,null,$_REQUEST['account']);
		
	}elseif(isset($_REQUEST['email']) && $_REQUEST['email']!=''){
		
		$acc=new Account(null,null,null,$_REQUEST['email']);
		
	}else{
		
		
		
	}
	
	if (isset($acc)){
		
		$acc->parseFromDb();
		
		if($acc->exists()){
			
			$email_content="Per avviare la procedura di reimpostazione della password per il tuo
account su $nome_land, fai clic sul seguente link:
<br /><br />
$url_land/pass_recovery.php?c={$acc->getEmailRecoveryCode()}&id={$acc->getId()}
<br /><br />
Se il link sopra indicato non funziona, copia l'URL e incollalo in una
nuova finestra del browser.
<br /><br />
Se hai ricevuto questa mail per errore, è probabile che un altro utente abbia inserito
erroneamente il tuo indirizzo email mentre tentava di reimpostare una password. Se non sei il mittente di questa richiesta,
non devi intraprendere altre azioni e puoi
tranquillamente ignorare questa email.
<br /><br />
Buon divertimento su $nome_land.";

			if(send_mail($acc->getEmail(),"Assistenza password di $nome_land",$email_content)){
				$msg= "Ti abbiamo inviato un'email con le istruzioni per reimpostare la password del tuo account.";	
			}else{
				$msg="Attenzione: impossibile inviare l'email con le istruzioni per reimpostare la password. Contatta i gli amministratori.".$msg;
			}

			
		}
		
	}else{
		$msg= "Attenzione: non esistono Account corrispondenti al criterio di ricerca.".$msg;
	}
	
	
}


if(isset($_REQUEST['c']) && isset($_REQUEST['id'])){
	
	if($_REQUEST['id']!='' && intval($_REQUEST['id'])>0){
		
		$acc=new Account(null,$_REQUEST['id']);
		$acc->parseFromDb();
		
		if($acc->exists()){
				
			if($acc->getEmailRecoveryCode()==$_REQUEST['c'] && isset($_REQUEST['pw1']) && isset($_REQUEST['pw2']) && $_REQUEST['pw1']==$_REQUEST['pw2'] && validate_password($_REQUEST['pw1'])){
				$acc->changePass($_REQUEST['pw1']);
				$msg= "Password modificata correttamente.";
			}elseif($acc->getEmailRecoveryCode()==$_REQUEST['c']){
						
				$msg= "<h3>Inserisci la nuova password</h3>
				\n
				<input name=\"c\" value=\"{$_REQUEST['c']}\" type=\"hidden\"/><input name=\"id\" value=\"{$_REQUEST['id']}\" type=\"hidden\"/>
				<table>\n
					<tr><td>password:</td><td><input name=\"pw1\" type=\"password\"/></td></tr>\n
					<tr><td>conferma:</td><td><input name=\"pw2\" type=\"password\"/></td></tr>\n
					<tr><td colspan=\"2\"><input type=\"submit\" value=\"Conferma\" class=\"buttonify\" /></td></tr>
				</table>\n
				";	
				
			}else{
				$msg= "Il codice non è corretto, o non più valido.";
			}	
			
		}else{
			$msg= "Account inesistente.";
		}
		
	}else{
		$msg="Parametri non corretti. Controlla l'URL nell'email con le istruzioni.";
	}

	
}
		
		?>
		<div>
			<?php echo $msg; ?>
		</div>
	</div></form>
</body>
</html>