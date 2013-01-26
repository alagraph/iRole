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

$weather_obj = new weather('RSXX0063', $timeout, "c", null);

// Parse the weather object via cached
// This checks if there's an valid cache object allready. if yes
// it takes the local object data, what's much FASTER!!! if it
// is expired, it refreshes automatically from rss online!
//$weather_obj->parsecached(); // => RECOMMENDED!
// allway refreshes from rss online. NOT SO FAST. 
$weather_obj->parse(); // => NOT recommended!

//print_r($weather_obj->forecast['CURRENT']);


//calcolo della nuova data

$now = new DateTime();
$january = new DateTime('2012-01-01');
$interval = $now->diff($january);

// %a will output the total number of days.
$diff=intval($interval->format('%a'));

$anno=floor($diff/365)+400;
$decade=floor(($diff%365)/10)+1;
$giorno=(($diff%365)%10)+1;
	
$h=$now->format('%H');
$m=$now->format('%i');
$s=$now->format('%s');
 
?>
	<div id="small_meteo" class="center centerTxt">
		<div id="meteo_img"><img src="images/meteo/<?php echo $weather_obj->forecast['CURRENT']['CODE']; ?>.png" /><?php echo $weather_obj->forecast['CURRENT']['TEMP'] ?>°C</div>
		<div id="small_clock">
			<span id="hours"><?php echo "giorno {$giorno}, {$decade}° decade<br /> anno {$anno} AL  <br /> {$now->format('H:i')}"; ?></span></span>		
		</div>
	</div>
