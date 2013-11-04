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


$rightslist=&$UserRightsList->getRights();
if (isset($_REQUEST['setting'])){
	foreach($_REQUEST['setting'] as $k=>$v){
		
		$rightslist[$k]->setValue($v);
		$msg="Preferenze Salvate.<br>\r\n";
	}
}


$avatarFields=&$avatar_fields->getCharXList();
if (isset($_REQUEST['configXvA'])){
	foreach($_REQUEST['configXvA'] as $k=>$v){
		$avatarFields[$k]->setViewMinLevel($v);
		$msg="Preferenze Salvate.<br>\r\n";
	}
}
if (isset($_REQUEST['configXeA'])){
	foreach($_REQUEST['configXeA'] as $k=>$v){
		$avatarFields[$k]->setEditMinLevel($v);
		$msg="Preferenze Salvate.<br>\r\n";
	}
}
if (isset($_REQUEST['configXvM'])){
	foreach($_REQUEST['configXvM'] as $k=>$v){
		$avatarFields[$k]->setViewMinMaster($v);
		$msg="Preferenze Salvate.<br>\r\n";
	}
}
if (isset($_REQUEST['configXeM'])){
	foreach($_REQUEST['configXeM'] as $k=>$v){
		$avatarFields[$k]->setEditMinMaster($v);
		$msg="Preferenze Salvate.<br>\r\n";
	}
}
if (isset($_REQUEST['configXse'])){
	foreach($_REQUEST['configXse'] as $k=>$v){
		$avatarFields[$k]->setSelfedit($v);
		$msg="Preferenze Salvate.<br>\r\n";
	}
}
if (isset($_REQUEST['configXsv'])){
	foreach($_REQUEST['configXsv'] as $k=>$v){
		$avatarFields[$k]->setSelfview($v);
		$msg="Preferenze Salvate.<br>\r\n";
	}
}

foreach($avatar_fields->getCharXList() as $k=>$v){
	$v->writeToDb();	
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

<h2>Gestione dei Permessi Utente</h2>
<div class=""><?php echo $msg; ?></div>
<form id="setrights" name="setrights" action="admin_setrights.php" method="post">
<table>
	<tr>
		<td>#</td><td>Relativo a</td><td>Descrizione</td><td>Livello</td>
	</tr>
		<?php
		foreach($UserRightsList->getRights() as $k=>$v){
  			//$v=new UserRight();	
  			//${$v->getVarName()}=$v->getValue();
			
			$sel_opt="<select name=\"setting[{$v->getId()}]\">";
			
			foreach($types_levels[$v->getType()] as $key=>$value){
				$slct="";	
				if($key==$v->getValue())
					$slct="selected=\"selected\"";
					
				$sel_opt.="<option value=\"{$key}\" $slct >{$value}</option>";	
			}
			
			
			$sel_opt.="</select>";
			
			if($v->getType()==1)
				$relatedto="Lv. Master";
			elseif($v->getType()==0)
				$relatedto="Lv. Account";
			
			echo "<tr class=\"".(($i++ % 2 == 0) ? 'even' : 'odd')."\">
					<td>{$v->getId()}</td><td>$relatedto</td><td>{$v->getDescription()}</td><td>{$sel_opt}</td>
				  </tr>\r\n";
  		}
		?>
</table>
<input type="submit" value="Salva" />
<table>
	<tr>
		<td>Campo Scheda</td><td>Visibile al possessore</td><td>Modificabile dal possessore</td>
		<td>Visibile ad Account</td><td>Modificabile da Account</td>
		<td>Visibile ad Master</td><td>Modificabile da Master</td>
	</tr>
	<?php
	foreach($avatar_fields->getCharXList() as $k=>$v){
		
		//$v=new ConfigCharX();
		
		$sel_vA="<select name=\"configXvA[{$v->getId()}]\">";
		$sel_eA="<select name=\"configXeA[{$v->getId()}]\">";
		$sel_vM="<select name=\"configXvM[{$v->getId()}]\">";
		$sel_eM="<select name=\"configXeM[{$v->getId()}]\">";
			
			foreach($types_levels[0] as $key=>$value){
				$slct_vA="";
				$slct_eA="";	
				
				if($key==$v->getViewMinLevel())
					$slct_vA="selected=\"selected\"";
				if($key==$v->getEditMinLevel())
					$slct_eA="selected=\"selected\"";
					
				$sel_vA.="<option value=\"{$key}\" $slct_vA >{$value}</option>";
				$sel_eA.="<option value=\"{$key}\" $slct_eA >{$value}</option>";
			}
			
			foreach($types_levels[1] as $key=>$value){
				$slct_vM="";
				$slct_eM="";	
				
				if($key==$v->getViewMinMaster())
					$slct_vM="selected=\"selected\"";
				if($key==$v->getEditMinMaster())
					$slct_eM="selected=\"selected\"";
					
				$sel_vM.="<option value=\"{$key}\" $slct_vM >{$value}</option>";
				$sel_eM.="<option value=\"{$key}\" $slct_eM >{$value}</option>";
			}
			
			
			$sel_vA.="</select>";
			$sel_eA.="</select>";
			$sel_vM.="</select>";
			$sel_eM.="</select>";
			
			$ck_checked="";
			if($v->getSelfedit())
				$ck_checked="checked=\"checked\"";
			
			$ck_box_selfedit="<input type=\"checkbox\" name=\"configXse[{$v->getId()}]\" value=\"1\" $ck_checked />";
			
			$ck_checked="";
			if($v->getSelfview())
				$ck_checked="checked=\"checked\"";
			
			$ck_box_selfview="<input type=\"checkbox\" name=\"configXsv[{$v->getId()}]\" value=\"1\" $ck_checked />";
			
			echo "<tr class=\"".(($i++ % 2 == 0) ? 'even' : 'odd')."\">
					<td>{$v->getName()}</td><td>{$ck_box_selfview}</td><td>{$ck_box_selfedit}</td>
					<td>$sel_vA</td><td>$sel_eA</td>
					<td>$sel_vM</td><td>$sel_eM</td>
				  </tr>";
		
	}
	?>
</table>
<input type="submit" value="Salva" />
</form>

</body>
</html>
