<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/docs_lib.php");


if(isset($_REQUEST['doc']) && $_REQUEST['doc']!=''){
	$docX=new Document(null,intval($_REQUEST['doc']));
	$docX->readFromDb();
}



?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Documentazione</title>
<?php
if(!IS_AJAX) {
?>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<?php
}

?> 
</head>
<body>
	<?php
	if(!isset($docX)){
		echo "Documento Inesistente";
	}else{
	?>
	<h1><?php echo $docX->getName(); ?></h1>
	
	<div class="doc_content">
		<?php echo $docX->getContent(); ?>
	</div>
	<?php
	}
	?>
</body>
</html>