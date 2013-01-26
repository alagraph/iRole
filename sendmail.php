<?php
require_once "config.php";


function isAjax() {
return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
}

function send_mail($from,$to,$subject,$body){
	// costruiamo alcune intestazioni generali
	global $admin_mail;
	$header = "From: <$from>\r\n";

	// costruiamo le intestazioni specifiche per il formato HTML
	$header .= "MIME-Version: 1.0\r\n";
	$header .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
	$header .= "Content-Transfer-Encoding: 7bit\r\n";

	//costruiamo il testo in formato HTML
	$final_body = "<html><body>$body</body></html>";
		
	if (mail($to,$subject,$final_body,$header))
		return true;
	
	return false;
}

function validate_email($email){
// set return value to 0 until the email address has been evaluated 
	$return = 0;
	// Check syntax of the given email address 
	if( (preg_match('/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/', $email)) || (preg_match('/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,6}|[0-9]{1,3})(\]?)$/',$email)) ) 
		{
	// Extraxt domainname from given email address
		list(, $domain) = explode('@', $email);
		$domain = trim($domain);
		// Fill in your personal api key (see http://www.block-disposable-email.com/register.php)  
		$key     = 'e4071676028cbb37c2fc7e4b2bf5d66f';
		$request = 'http://check.block-disposable-email.com/api/json/'.$key.'/'.$domain; 
		if ($response = @file_get_contents($request))
			{
			$dea = json_decode($response);
			// Analyse the domain_status response only if the request_status was successful
			if ($dea->request_status == 'success')
				{
				if ($dea->domain_status == 'ok') $return = 1;
				if ($dea->domain_status == 'block') $return = 0;
				}
			// If MX checks fail also return 0
			elseif ($dea->request_status == 'fail_input_domain') $return = 0;
			// If the API query return some other response accept the given address anyway. 
			// Too high risk to lose one customer! 
			else $return = 1;
			}
		// Wenn Website down ist Registrierung auch zulassen
	// If the service is currently down and the api does not respond also accept the given email address
		else $return = 1;
	}
	return $return;
}



if (isAjax()){
	
	//controllo i campi
	$sender=$_REQUEST['email'];
	$recipient="francescomaria.stasi@gmail.com";
	$obj = "Contact Us - iRole";
	$textmsg=$_REQUEST['text-message'];
	
	if(!validate_email($sender)){
		echo "Please enter a valid email address.";
		exit();
	}
	
	if(strlen($textmsg)<2){
		echo "Please enter a longer text.";
		exit();	
	}
	
	
	mysql_query("INSERT INTO contact (email,motivation,beta_req) VALUES ('{$sender}','{$textmsg}',0)") or die ("Impossibile inviare richiesta beta.");
	
	
	if(send_mail($sender,$recipient,$obj,$textmsg)){
		echo "Thank you!<br />We will reply to your email as soon as possible.";
		
		
		
	}else{
		echo "Unable to send the email.<br />Please try again later.";
	}
	
	
}




?>