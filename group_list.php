<?php
/*  I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once ("config.php");
require_once ("libs/common.php");
require_once ("libs/character_lib.php");
require_once ("libs/group_lib.php");

if(!isset($_SESSION)) {
	session_start();
}

logged();

$MyChar_obj = new Character(null, $_SESSION['char_name']);
$MyChar_obj -> parseFromDb();

if(!($MyChar_obj -> exists())) {
	echo "Personaggio inesistente.";
	exit();
}

$_SESSION['modlevel'] = $MyChar_obj -> Account() -> getModLevel();
$MyChar_obj -> readGroupsAdmin();
if($admin_groups_level_required > $_SESSION['modlevel']) {
	$flag = false;
} else {
	$flag = true;
}
?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title><?php echo $nome_land;?></title>
<script type="text/javascript">
	$(function() {
		$("#group_menu").tabs();
	});
</script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>
<div class="width90 center">
<?
if (isset($_REQUEST['list'])){
	$group=new Group(null,$_REQUEST['list']);
	$group->readFromDb();
	
	$group->loadMembers(true,false,true);
	
	echo "<a href=\"group_list.php\">[torna alla lista]</a>
			<h2>{$group->getLogo(true)} {$group->getName()} {$group->getSite()}</h2>
			<div id=\"group_menu\">
				<ul>
					<li><a href=\"#members\">Membri</a></li>
					<li><a href=\"#statuto\">Statuto</a></li>
				</ul>
			";
	
	echo "<div id=\"members\"><ul>";
	foreach($group->getMembers() as $k=>$v){
		echo "<li>{$v->getElemObj()->getImage(true)} {$v->getCharObj()->getCharNameLink()} - {$v->getElemObj()->getName()} </li>";	
	}
	echo "</ul></div>";
	
	echo "<div id=\"statuto\">
			<h3>Statuto</h3>
			<div>{$group->getStatute()}</div>
		  </div>";
	
	
	
	echo "</div>";


}else{
?>
<h2>Elenco dei gruppi</h2>
<ul>
	<?php
	foreach($groups_name_array as $key => $value) {
		
		$first=true;
		
		$groupList = new GroupList($key);
		
		foreach($groupList->GetList() as $k => $v) {
			
			if($first){
				echo "<li style=\"float:left;margin-left:70px;list-style:none;\"><h3>$value</h3><div class=\"panel_bg roundcorner clearborder\" style=\"padding:8px;\">";
				$first=false;
			}
			
			$CanAdmin = $MyChar_obj -> isGroupAdmin($v -> getId());

			$mod = "";
			if($CanAdmin || $flag)
				$mod = "<a href=admin_groupjoin.php?id={$v->getId()}>[Gestisci]</a>";

			echo "<a href=group_list.php?list={$v->getId()}>{$v->getLogo(true)} {$v->getName()} ({$v->getNumMembers()})</a> {$mod}<br />\n";

		}
		if(!$first)
			echo "</div></li>\n";

	}
	?>
</ul>
<?
}
?>
</div>
</body>
</html>
