<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("character_lib.php");
require_once("bans_lib.php");

if(!isset($_SESSION))
{
session_start();
} 


function include_headers($arr=null){
	  	
	if($arr=='all'){
		$arr=array();
		$arr[]='style';
		$arr[]='jquery';
		$arr[]='markitup';
		$arr[]='jscroller';
		$arr[]='quickpager';
		$arr[]='gearpanel';
		$arr[]='bubble-popup';
		$arr[]='jeditable';
		$arr[]='form';
		$arr[]='autoresize';
		$arr[]='swfobj';
		
	}
	
	if(!is_array($arr)){
		$arr=array();
	}
	
	foreach ($arr as $v) {
		
		switch ($v) {
			case 'style':
				
				echo '<link href="css/style.css" rel="stylesheet" type="text/css" />
				';
				
				break;
				
			case 'jquery':
				echo '<link href="css/alertui/jquery-ui-1.8.11.custom.css" rel="stylesheet" type="text/css" />
					  <link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
					  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
					  <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>
					  ';
				break;
				
			case 'markitup':
				echo '<link rel="stylesheet" type="text/css" href="markitup/skins/simple/style.css" />
					  <link rel="stylesheet" type="text/css" href="markitup/sets/bbcode/style.css" />
					  <script src="markitup/jquery.markitup.js"></script>
					  <script src="markitup/sets/bbcode/set.js"></script>
					  ';
				break;
				
			case 'jscroller':
				echo '<script src="js/jscroller-0.4.js"></script>
				';
				break;
				
			case 'quickpager':
				echo '<script src="js/jquery.quickpager.js"></script>
				';
				break;
				
			case 'gearpanel':
				echo '<script src="js/jquery.gearpanel.js"></script>
				';
				break;
				
			case 'bubble-popup':
				echo '<link rel="stylesheet"  type="text/css" href="js/jquery-bubble-popup-v3.css" />
					  <script src="js/jquery-bubble-popup-v3.min.js"></script>
					  ';
				break;
				
			case 'jeditable':
				echo '<script src="js/jquery.jeditable.js"></script>
					  <script>
					  	$.editable.addInputType(\'markitup\', {
						    element : $.editable.types.textarea.element,
						    plugin  : function(settings, original) {
						        $(\'textarea\', this).markItUp(settings.markitup);
						    }
						});
					  </script>
					  ';
				break;
				
			case 'form':
				echo '<script src="js/jquery.form.js"></script>
				';
				break;
				
			case 'autoresize':
				echo '<script src="js/jquery.autoresize.js"></script>
				';
				break;
				
			case 'swfobj':
				echo '<script src="//ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
				';
				break;

			case 'youtube':
				echo '<script src="js/jquery.youtubeplaylist.js"></script>
				';
				break; 
			
			default:
				
				break;
		}
		
		
		
	}
		
	
	  echo '<!--[if IE]>
			<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
			<![endif]-->';
			
	return;
	
}




function check_logged(){
  if (isset($_SESSION['username']) && $_SESSION['username']!='' &&
      isset($_SESSION['id']) && $_SESSION['id']>0 &&
      isset($_SESSION['char_id']) && $_SESSION['char_id']>0){
        
        $char=new Character($_SESSION['char_id']);
        $char->writeLocation();
        
        return true;
      }
}

function check_banned(){
  
  $banList=new BanList();
  $banList->populateList($_SESSION['id']);
  
  $lastB=$banList->getLastBan();
  
  if (!empty($lastB) && $lastB->isActive()){
    return false;
  }
  return true;
  
}

function logged(){
		if ( !check_logged() )
			header("location: expired.php");
    if ( !check_banned() )
      header("location: banned.php");
}
	
function itaTime($str,$short=false){
	
	$timestamp = strtotime($str);
	
	if($short){
		$datait=date('H:i',$timestamp);
	}else{
		$datait=date('d/m/Y H:i:s',$timestamp);
	}
	
	return $datait;
}

function online_list($room_id,$orderby=null){
	  
	if (!isset($room_id))
		return false;
	
	$room_id=mysql_real_escape_string($room_id);
	
	$order=" ORDER BY c.name,r.name";	
	if (isset($orderby)){
		if ($orderby=='room')
			$order=" ORDER BY r.name,c.name";
	}
	
  
	$date_list=date("YmdHis",strtotime("-".$GLOBALS['online_offset']." minute"));
		
	$query="SELECT c.*, r.name AS roomname FROM `character` c LEFT OUTER JOIN room r
			ON c.location_current=r.id
			WHERE c.location_time>='$date_list' AND c.online=1";
	$append="";
	
	if ($room_id!='*'){
		$append=" AND (";
		$rooms=explode(',',$room_id);
		for ($i=0;$i<count($rooms);$i++){
			if ($i==0){
				$append.=" r.id='".$rooms[$i]."'";
			}else{
				$append.=" OR r.id='".$rooms[$i]."'";
			}
		}
		$append.=")";
	}
	
	$query=$query.$append.$order;
	$result=mysql_query($query) or die(mysql_error());
	return $result;

}

function show_onlineList($roomR,$stretchR,$ShowGroups=true){
	
	$room=0;
	$text='<tr><td>Pg:</td><td align="right">Stanza:</td></tr>';
	
	if (!isset($roomR)){
		$room='*';
	}elseif ($roomR=='0' && isset($_SESSION['room_id'])){
		$room=$_SESSION['room_id'];
		$text='';
	}else{
		$room=$roomR;
	}
	
	$result=online_list($room,null);
	
	if($result==false)
		return;

	$i=0;
	
	while ($row=mysql_fetch_array($result)){
		$room=$row['roomname'];
		$char_obj=new Character(null,$row['name']);
		$maleicon="<img src=\"images/icons/male-icon.png\" alt=\"maschio\" title=\"maschio\" />";
  		$femaleicon="<img src=\"images/icons/female-icon.png\" alt=\"femmina\" title=\"femmina\" />";
		$icons="";
			
		if($ShowGroups){
				
				
			$char_obj->parseFromDb();
			
			//infine aggiungo l'icona del sesso
			$icons.=($char_obj->getSex()==2) ? $femaleicon : $maleicon;
			$icons.=" ";
			
			$joinArrGrp=$char_obj->getGroups();
			
			foreach($GLOBALS['groups_name_array'] as $key=>$value){
				foreach($joinArrGrp as $k=>$v){ 
				if($v->getGroupType()!=$key) continue;
				$icons.="{$v->getImage(true)} ";
			
				//$v=new GroupElement();
				}
				
			}
			
				
		}
		
		if ($room===null){
			$room="Homepage";
		}
		else{
			$room="<a href=\"map.php?roomid={$row['location_current']}\">{$room}</a>";	
		}
		if ($roomR=='0')
			$room='';
		
		
		$text.='<tr class="elem">
					<td>'.$icons.$char_obj->getCharNameLink().'</td>
					<td align="right">'.$room.'</td>
				  </tr>';
	  	$i++;      
          
	}
	
	$title='<h2>Personaggi Presenti: '.$i.'</h2>';
	if ($roomR=='0')
		$title="Personaggi presenti: $i";


	if ($stretchR==1)
		$stretch="stretch";
		
	echo '<div>'.$title.'</div>
		  <table style="text-align:left;">
			'.$text.'
		  </table>';
	
	
	return;
	
}

function validate_password($password){
		
	// controllo che la lunghezza sia corretta e che non ci siano caratteri non validi
    if (!preg_match("/^[A-Z0-9]{4,15}$/i",$password))
		return false;
	return true;
}

function validate_username($username){
		
	// controllo che la lunghezza sia corretta e che non ci siano caratteri non validi
    if (!preg_match("/^[A-Z]{2,15}$/i",$username))
		return false;
	return true;
	
}

// generazione di una stringa casuale di n caratteri
function rnd_string($lunghezza){
		
	//$caratteri_disponibili ="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
	$caratteri_disponibili ="abcdefghijklmnopqrstuvwxyz1234567890";
	$stringa = "";
	for($i = 0; $i<$lunghezza; $i++){
		$stringa .= substr($caratteri_disponibili,rand(0,strlen($caratteri_disponibili)-1),1);
	}
				
	return $stringa;
}

function validate_email($email){
		
	// controllo se l'email passata alla funzione è valida. Se lo è ritorno 1
    if (!preg_match("/^[a-z0-9][_\.a-z0-9-]+@([a-z0-9][0-9a-z-]+\.)+([a-z]{2,4})/i", $email))
    		return false;
	
	return true;
}

function email_exist($email){ //controlla che la mail esista

	$query="SELECT email FROM account WHERE email='$email'";
	$result = mysql_query($query) or die(mysql_error());
	
	if (mysql_num_rows($result)>0)	return true;
	
	return false;
}


function acapo($subject){
	return str_replace( array("\r\n","\r","\n") , "<br />" , $subject);
}

function send_mail($to,$subject,$body){
	// costruiamo alcune intestazioni generali
	global $admin_mail;
	$header = "From: $nome_land <$admin_mail>\r\n";

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

function getBrowser($u_agent=null)
{
    if(!isset($u_agent))	
	    $u_agent = $_SERVER['HTTP_USER_AGENT'];
		
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'Linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'Mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'Windows';
    }
   
    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    }
    elseif(preg_match('/Firefox/i',$u_agent))
    {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    }
    elseif(preg_match('/Chrome/i',$u_agent))
    {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    }
    elseif(preg_match('/Safari/i',$u_agent))
    {
        $bname = 'Apple Safari';
        $ub = "Safari";
    }
    elseif(preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Opera';
        $ub = "Opera";
    }
    elseif(preg_match('/Netscape/i',$u_agent))
    {
        $bname = 'Netscape';
        $ub = "Netscape";
    }
   
    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }
   
    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }
   
    // check if we have a number
    if ($version==null || $version=="") {$version="?";}
   
    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
}


class GenericRecord {
	
	
	protected $table_name;
	protected $db_fields = array();
	protected $db_keys;
	
	public function __construct() {
		$this->table_name=NULL;
		$this->db_fields=NULL;
		$this->db_keys=NULL;	
	}
	
	private function check_fields(){
		
		if(empty($this->table_name)) exit("table_name is empty");
		if(empty($this->db_fields)) exit("db_fields is empty");
		if(empty($this->db_keys)) exit("db_keys is empty");
			
	}
	
	private function setTableName($value){
		
		if(empty($value))		
			exit("Il nome della tabella non può essere vuoto");
		
		
		$query="SHOW COLUMNS FROM `".mysql_real_escape_string($value)."`";
		$result = mysql_query($query) or die(mysql_error());
		
		if(mysql_num_rows($result)<1)
			exit("La tabella non contiene campi");
		
		//altrimenti li salvo
		$this->table_name=mysql_real_escape_string($value);
		
		while($row=mysql_fetch_assoc($result)){
				
			$this->db_keys[]=$row['Field'];	
			$this->db_fields[$row['Field']]=NULL;	
			
		}
		
		
		
	}
	
	
	protected function setCustomField($field,$value) {
			
		$this->check_fields();	
			
		if (empty($this->db_fields['id']))
			die("Id is missing! Unable to update $field field.");

		$table=$this->table_name;
		
		$tmpArr=array('id' => $this->db_fields['id'], $field => $value);
		standard_UPDATE_query($table, $tmpArr);

		$this->db_fields[$field]=$value;

		return true;
	}
	
	public function readFromDb() {

		$this->check_fields();

		//estraggo i dati da db
		if (isset($this->db_fields['id'])) {
			$sel = " WHERE a.id='{$this->escapeField($this->db_fields['id'])}'";
		}
		
		$table=$this->table_name;

		if (isset($sel)) {
			$query = "SELECT a.* FROM `{$table}` a " . $sel;

			$result = mysql_query($query) or die(mysql_error());
			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_array($result);
				
				if(method_exists($this, 'parse')){
					$this->parse($row);
				}else {
					$this->generic_parse($row);
				}
				return true;
			}
			return false;
		}
	}
	
	public function writeToDb() {
		
		$this->check_fields();
			
		if (isset($this->db_fields['id']))//UPDATE
			$this->db_fields=standard_UPDATE_query($this->table_name, $this->db_fields);
		else//INSERT
			$this->db_fields=standard_INSERT_query($this->table_name, $this->db_fields);
		
		return true;

	}
	
	protected function escapeField($name){
		
		return mysql_real_escape_string($name);
		
	}
	
	
	protected function standard_UPDATE_query($array_fields){
	
		//genero la query coi campi corretti
	
		if (!count($array_fields) > 0)
			return false;
	
		$i = 0;
		$fields_q = "";
		foreach ($array_fields as $k => $v) {
	
			//skippo il primo, perchè è la condizione di WHERE (ovvero l'ID), e non un campo da aggiornare
			if ($i == 0) {
				$index = $k;
				$i++;
				continue;
			}
	
			if ($i > 1) $fields_q .= ",\n";
			$fields_q .= "`$k`='" . mysql_real_escape_string($v) . "'";
			$i++;
		}
	
		$query = "UPDATE `{$this->table_name}` SET\n" . $fields_q . "\nWHERE `{$index}`='" . mysql_real_escape_string($array_fields[$index]) . "' ";
		mysql_query($query) or die(mysql_error());
	
		return $array_fields;
	
	}
	
	protected function standard_INSERT_query($array_fields, $replace=false){
	
		//genero la query coi campi corretti
	
		if (!count($array_fields) > 0)
			return false;
	
		$i = 0;
		$fields_q = "";
		foreach ($array_fields as $k => $v) {
	
			//skippo il primo, perchè è la condizione di WHERE (ovvero l'ID), e non un campo da aggiornare
			if ($i == 0) {
				$index = $k;
				$i++;
				continue;
			}
	
			if ($i > 1) $fields_q .= ",\n";
			$fields_q .= "`$k`='" . mysql_real_escape_string($v) . "'";
			$i++;
		}
	
		if($replace)
			$type_q="REPLACE";
		else
			$type_q="INSERT";
	
		$query = "$type_q INTO `{$this->table_name}` SET\n" . $fields_q;
	
		$result = mysql_query($query) or die(mysql_error());
		$array_fields[$index] = mysql_insert_id();
	
		return $array_fields;
	
	}

	protected function generic_parse($fillingArr){
	
		foreach ($fillingArr as $k => $v) {
			if (array_key_exists($k, $this->db_fields))
				$this->db_fields[$k] = trim($v);
		}
	
		return $db_fieldsArr;
	
	}
	
	
	
}


?>
