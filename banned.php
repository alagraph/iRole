<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/bans_lib.php");
 
if(!isset($_SESSION))
{
session_start();
}

if(isset($_SESSION['id'])){
  
  $banList=new BanList();
  $banList->populateList($_SESSION['id']);  
  
  $b =& $banList->getLastBan();

}

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Account Bannato</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>
<div class="center">
  <p>
  <?php
    echo "Il tuo account è stato bannato fino al {$b->getBanUntil()}<br />
          Autore: {$b->getAuthorAccName()}, il {$b->getBanDate()}<br />
          Motivazione: {$b->getBanReason()}<br />\n";
  ?>
  <a href="<?php echo $url_land; ?>">Torna alla home</a>
  </p>
</div>
</body>
</html>
