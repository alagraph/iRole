<?php /*  I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once ("config.php");
require_once ("libs/common.php");
require_once ("libs/character_lib.php");
require_once ("libs/logs_lib.php");

if (!isset($_SESSION)) {
	session_start();
}

logged();

$MyChar_obj = new Character(null, $_SESSION['char_name']);
$MyChar_obj -> parseFromDb();

if (!($MyChar_obj -> exists())) {
	echo "Personaggio inesistente.";
	exit();
}

$_SESSION['modlevel'] = $MyChar_obj -> Account() -> getModLevel();

//TODO: livello richiesto per visione log

if ($admin_view_pm_required > $_SESSION['modlevel']) {
	echo "Accesso negato, permessi insufficienti.";
	exit();
}

$author_id = null;
$victim_id = null;
$log_type = -1;

if (isset($_REQUEST['author']) && $_REQUEST['author'] != '') {

	$tmpC = new Character(null, $_REQUEST['author']);
	$tmpC -> checkExistance();
	$author_id = $tmpC -> getCharId();

}

if (isset($_REQUEST['victim']) && $_REQUEST['victim'] != '') {

	$tmpC = new Character(null, $_REQUEST['victim']);
	$tmpC -> checkExistance();
	$victim_id = $tmpC -> getCharId();

}
if (isset($_REQUEST['log_type']) && $_REQUEST['log_type'] != '') {
	$log_type = intval($_REQUEST['log_type']);
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

<form id="view_logs" name="view_logs" action="admin_logs.php" method="post">
<div class="roundcorner panel_bg clearborder width90 center">
<h2>Visione dei Log</h2>
<div>Nota: specificando il nome dell'autore verranno cercati anche i log eseguiti da altri personaggi legati al suo account</div>
<table>
	<tr>
		<td>Pg Autore azione:</td><td><input type="text" name="author" id="author" class="autoCLog" value="<? echo $_REQUEST['author'];?>" /></td>
	</tr>
	<tr>
		<td>Vittima azione:</td><td><input type="text" name="victim" id="victim" class="autoCLog" value="<? echo $_REQUEST['victim'];?>" /></td>
	</tr>
	<tr>
		<td>Tipo di azione:</td>
		<td>
			<select name="log_type">
			<option value="0">Tutte</option>
			<?php
			foreach ($logs_type_array as $k => $v) {
				if ($k == 0)
					continue;
				echo "<option value=\"$k\">$v</option>\n";
			}
			?>
			</select>
		</td>
	</tr>
</table>
<input type="submit" value="Continua" />
</div>
</form>
<?php
if (isset($log_type) && $log_type>=0 && (!empty($author_id) || !empty($victim_id))) {
?>
<div class="result panel_bg dark_bg roundcorner width90 center" style="margin-top: 20px;">
	<h3 class="centertxt even">Risultati della ricerca</h3>
	<?php

	

		$logList = new LogList();
		$logList -> readFromDb($author_id, $victim_id, 1, $log_type);
		$i=0;
		foreach ($logList->getLogs() as $key => $value) {
			$i++;
			$authorObj = new Character($value -> getAuthorId());
			$authorObj -> checkExistance();
			if ($authorObj -> exists()) {
				$author_name = $authorObj -> getCharNameLink();
			} else {
				$author_name = "Personaggio Cancellato";
			}

			$victimObj = new Character($value -> getVictimId());
			$victimObj -> checkExistance();
			if ($victimObj -> exists()) {
				$victim_name = $victimObj -> getCharNameLink();
			} else {
				$victim_name = "Personaggio Cancellato";
			}
			
			$classI = ($i%2==0)? 'even':'odd';
			
			
			echo 	"<div class=\"$classI center roundcorner dark_bg width90\" style=\"margin-top:10px;\">
					<span class=\"floatright\">il " . itaTime($value -> getDate()) . "</span>
					<div>Tipologia azione: " . $logs_type_array[$value -> getType()] . "</div>
					<div>{$value->getText()}</div>
					<div>Autore: {$author_name}</div>
					<div>Vittima: {$victim_name}</div>
					</div>\n";

		}
		
		if($i<=0) echo "Nessun Log per la tipologia di azione selezionata.";

	}
	?>
</div>
</body>
</html> 