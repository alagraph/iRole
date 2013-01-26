<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
require_once("libs/xmlparser_lib.php");
require_once("libs/meteo_lib.php");

if(!isset($_SESSION))
{
session_start();
} 

logged();




$timeout=60*30;  // mezz'ora
if (isset($_ENV["TEMP"]))
  $cachedir=$_ENV["TEMP"];
else if (isset($_ENV["TMP"]))
  $cachedir=$_ENV["TMP"];
else if (isset($_ENV["TMPDIR"]))
  $cachedir=$_ENV["TMPDIR"];
else
// Default Cache Directory  
  $cachedir=$cache_dir;
  
$cachedir=str_replace('\\\\','/',$cachedir);
if (substr($cachedir,-1)!='/') $cachedir.='/';

$weather_obj = new weather($weather_code, $timeout, "c", $cachedir);

// Parse the weather object via cached
// This checks if there's an valid cache object allready. if yes
// it takes the local object data, what's much FASTER!!! if it
// is expired, it refreshes automatically from rss online!
$weather_obj->parsecached(); // => RECOMMENDED!

// allway refreshes from rss online. NOT SO FAST. 
//$weather_obj->parse(); // => NOT recommended!
?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Meteo</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="css/jdigiclock.css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.jdigiclock.js"></script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">
    $(document).ready(function() {
        $('#digiclock').jdigiclock({
            // Configuration goes here
            clockImagesPath: 'images/jdigiclock/clock/',
            weatherImagesPath: 'images/jdigiclock/weather/' ,
            weatherLocationCode: '<?php echo $weather_code; ?>'
        });
    });
</script>
</head>
<body>
	<div id="digiclock"></div>
</body>
</html>