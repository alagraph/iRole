<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
 
if(!isset($_SESSION))
{
session_start();
} 

if(isset($_SESSION['char_id'])){
  $char_obj=new Character($_SESSION['char_id']);
  $char_obj->setOffline();
}


session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Logout</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body class="logout">
<div class="center">
  <p class="centertxt">
  Logout effettuato con successo. <br />
  <a href="<?php echo $url_land; ?>" target="_top" class="popUp">Effettua nuovamente il login.</a></p>
</div>
</body>
</html>
