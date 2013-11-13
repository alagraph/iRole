<?php
/*  I codici qui inseriti sono proprietà di
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

logged();

$MyChar_obj=new Character(null,$_SESSION['char_name']);
$MyChar_obj->parseFromDb();

if(!($MyChar_obj->exists())){
  echo "Personaggio inesistente.";
  exit();  
}

$_SESSION['modlevel']=$MyChar_obj->Account()->getModLevel();

if ($_SESSION['modlevel']<count($acc_level_array)-1){
  echo "Accesso negato, permessi insufficienti.";
  exit();
}


$landsettingslist=&$LandSettingsList->getSettings();
if (isset($_REQUEST['setting'])){
	foreach($_REQUEST['setting'] as $k=>$v){
		
		//echo $v;

		$landsettingslist[$k]->setValue($v);
		$msg="Preferenze Salvate.<br>\r\n";
	}
}



?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title><?php echo $nome_land;?></title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>

<h2>Gestione delle Opzioni Land</h2>
<div class=""><?php echo $msg; ?></div>
<form id="setrights" name="setlandsetting" action="admin_setconfig.php" method="post">
<table>
	<tr>
		<td>#</td><td>Relativo a</td><td>Descrizione</td><td>Livello</td>
	</tr>
		<?php
		$LandSettingsList->loadSettings();
		foreach($LandSettingsList->getSettings() as $k=>$v){
  			
  			//${$v->getVarName()}=$v->getValue();

  			$set_name = "name=\"setting[{$v->getId()}]\"";

  			$sel_type = "";
  			
  			switch ($v->getType()) {
  				case 'integer':

  					if($v->getAcceptedValues()!=null){
  						$sel_input="<select $set_name >";
	  					foreach ($v->getAcceptedValues() as $acpt_k => $acpt_v ) {
	  						
	  						$v->getValue()==$acpt_v ? $selected='selected="selected"' : $selected="";

	  						$sel_input.="<option value=\"{$acpt_v}\" $selected >{$acpt_k}</option>";
	  					}
	  					$sel_input.="</select>";
  					}else{
  						$sel_input="<input type=\"text\" itype=\"number\" $sel_type $set_name value=\"{$v->getValue()}\"/>";
  					}

  					
  					break;

  				case 'double':
  					$sel_input="<input type=\"text\" dtype=\"number\" $sel_type $set_name value=\"{$v->getValue()}\"/>";
  					break;

  				case 'boolean':
  					$selY =""; $selN = "";
  					$v->getValue() == 1 ? $selY = 'selected="selected"' : $selN = 'selected="selected"';
  					$sel_input="<select $set_name ><option value=\"1\" $selY >Si</option><option value=\"0\" $selN >No</option></select>";
  					break;

  				case 'array':
  					$sel_input="<input type=\"text\" atype=\"arr\" $sel_type $set_name value=\"".htmlspecialchars($v->getValue(false))."\"/>";
  					break;
  				
  				default:
  					$sel_input="<input type=\"text\" stype=\"str\" $sel_type $set_name value=\"".htmlspecialchars($v->getValue())."\"/>";
  					break;
  			}
  			
			
			

			
			
			echo "<tr class=\"".(($i++ % 2 == 0) ? 'even' : 'odd')."\">
					<td>{$v->getId()}</td><td>{$v->getVarName()}</td><td>{$v->getDescription()}</td><td>{$sel_input}</td>
				  </tr>\r\n";
  		}
		?>
</table>
<input type="submit" value="Salva" />

</form>

</body>
</html>
