<?
//============================================================================
//============================================================================
// Script:    	PHP Class "weather"
// Version:     2.0 / 12.12.2006
//
// Rewritten: Dec 2006 by Matt Brown
//   fixed all PHP warnings
//   added cachedir parameter
//   changed structure of results
//   more adaptive to changes in Yahoo RSS feed
//============================================================================
// From:	http://www.voegeli.li
// Autor:	Marco Voegeli, Switzerland >> www.voegeli.li >> fly forward! >>
// Date:	28-Oct-2005 
// License/
// Usage:	Open Source / for free	
//============================================================================
// DEPENDENCIES:
// -  It requires the class "xmlParser" (Be lucky: Also in the Archive file!)
//============================================================================
// DESCRIPTION:
// This Class gets Weather RSS from WEATHER.YAHOO.COM and parses it into
// a weather object with usable attributes. Use it for:
//
// - Actual Situation (temperature/sunrise/sunset/Image...)
// - Forecast Day 1 (temp low, high/text/date/day/image...)
// - Forecast Day 2 (temp low, high/text/date/day/image...)
//
// PUBLIC METHODS
// - parse() :		Gets the XML File parses it and fills attributes
// - parsecahed() : 	Much quicker!!! Writes a cached version to a local
//				file with expiry date! expiry date is calculated
//				with the given input parameter
//		
//============================================================================
// SAMPLE:
// - See the file "weather.test.php" in this archive for Santiago de Chile
//
// WEB GUI URL: http://weather.yahoo.com/forecast/CIXX0020_c.html?force_units=1
// RSS URL:     http://xml.weather.yahoo.com/forecastrss?u=C&p=CIXX0020
//
// The class needs one Attribute in the Constructor Method:
//
// $weather_chile = new weather("CIXX0020", 60);
//
// "CIXX0020" is the Yahoo code for Santiago de Chile. See WEB GUI URL above!
// 
// "60" means 60 seconds until the cache expires. If not needed set = 0.
//
// GO TO WEATHER.YAHOO.COM and search for your desired weather location. If
// found, click on the location link (must see the forecast). Now take
// the code from the URL in your browsers address field.
//
//============================================================================
// Changes:
// - 19.11.2005 MAV : XML Feed Structure from Yahoo changed. Adapted script.
//============================================================================
// Visit http://dowdybrown.com , the contributor of the new version. Thank you
// Matt for this great and better version of the yahoo weather class! You have
// done a good job!
//============================================================================


$meteo_array=array(
				0 =>'tornado',
				1 =>'tempesta tropicale',
				2 =>'uragano',
				3 =>'forte temporale',
				4 =>'temporale',
				5 =>'pioggia mista neve',
				6 =>'pioggia mista nevischio',
				7 =>'neve mista nevischio',
				8 =>'pioggerella ghiacciata',
				9 =>'pioggerella',
				10=>'pioggia ghiacciata',
				11=>'rovesci',
				12=>'rovesci',
				13=>'folate di neve',
				14=>'leggera nevicata',
				15=>'tormenta di neve',
				16=>'neve',
				17=>'grandine',
				18=>'nevischio',
				19=>'polvere',
				20=>'nebbia',
				21=>'foschia',
				22=>'fumo',
				23=>'bufera',
				24=>'vento',
				25=>'freddo',
				26=>'nuvoloso',
				27=>'molto nuvoloso',
				28=>'molto nuvoloso',
				29=>'parzialmente nuvoloso',
				30=>'parzialmente nuvoloso',
				31=>'sereno',
				32=>'sereno',
				33=>'sereno',
				34=>'sereno',
				35=>'pioggia mista nebbia',
				36=>'caldo',
				37=>'temporali isolati',
				38=>'temporali sparsi',
				39=>'temporali sparsi',
				40=>'pioggerelle sparse',
				41=>'forte nevicata',
				42=>'lievi nevicate sporadiche',
				43=>'forte nevicata',
				44=>'parzialmente nuvoloso',
				45=>'temporale',
				46=>'lieve nevicata',
				47=>'temporali isolati',
				3200=>'non disponibile'
			);


class weather
{


// ------------------- 
// ATTRIBUTES DECLARATION
// -------------------

// HANDLING ATTRIBUTES
var $locationcode; // Yahoo Code for Location
var $allurl;       // generated url with location
var $parser;       // Instance of Class XML Parser
var $unit;         // F or C / Fahrenheit or Celsius

// CACHING ATTRIBUTES
var $cache_expires;
var $cache_lifetime;
var $source;       // cache or live

var $forecast=array();


// ------------------- 
// CONSTRUCTOR METHOD
// -------------------
function weather($location, $lifetime, $unit, $cachedir)
{

// Set Lifetime / Locationcode
$this->cache_lifetime = $lifetime;
$this->locationcode   = $location;
$this->unit           = $unit;
$this->cachedir       = $cachedir;
$this->filename       = $cachedir . $location;

}

// ------------------- 
// FUNCTION PARSE
// -------------------
function parse()
{
$this->allurl = "http://xml.weather.yahoo.com/forecastrss";
$this->allurl .= "?u=" . $this->unit;
$this->allurl .= "&p=" . $this->locationcode;

// Create Instance of XML Parser Class
// and parse the XML File
$this->parser = new xmlParser();
$this->parser->parse($this->allurl);
$content=&$this->parser->output[0]['child'][0]['child'];
foreach ($content as $item) {
  //print "<hr><pre>";
  //print_r($item);
  //print "</pre></p>";
  switch ($item['name']) {
    case 'TITLE':
    case 'LINK':
    case 'DESCRIPTION':
    case 'LANGUAGE':
    case 'LASTBUILDDATE':
      $this->forecast[$item['name']]=$item['content'];
      break;
    case 'YWEATHER:LOCATION':
    case 'YWEATHER:UNITS':
    case 'YWEATHER:ASTRONOMY':
      foreach ($item['attrs'] as $attr=>$value)
        $this->forecast[$attr]=$value;
      break;
    case 'IMAGE':
      break;
    case 'ITEM':
      foreach ($item['child'] as $detail) {
        switch ($detail['name']) {
          case 'GEO:LAT':
          case 'GEO:LONG':
          case 'PUBDATE':
            $this->forecast[$detail['name']]=$detail['content'];
            break;
          case 'YWEATHER:CONDITION':
            $this->forecast['CURRENT']=$detail['attrs'];
            break;
          case 'YWEATHER:FORECAST':
            array_push($this->forecast,$detail['attrs']);
            break;
        }
      }
      break;
  }
}
$this->source = 'live';

// FOR DEBUGGING PURPOSES
//print "<hr><pre>";
//print_r($this->forecast);
//print "</pre></p>";
}

// ------------------- 
// WRITE OBJECT TO CACHE
// -------------------
function writecache() {
  unset($this->parser);
  $this->cache_expires = time() + $this->cache_lifetime;
  $fp = fopen($this->filename, "w");
  fwrite($fp, serialize($this));
  fclose($fp);
}

// ------------------- 
// READ OBJECT FROM CACHE
// -------------------
function readcache()
{
$content=@file_get_contents($this->filename);
if ($content==false) return false;
$intweather = unserialize($content);
if ($intweather->cache_expires < time()) return false;

$this->source = 'cache';
$this->forecast = $intweather->forecast;
return true;
}


// ------------------- 
// FUNCTION PARSECACHED
// -------------------
function parsecached() {
  if ($this->readcache()) return;
  $this->parse();
  $this->writecache();
}

} // class : end

?>