<?php
 /*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */
 
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

require_once ("config.php");
require_once ("libs/common.php");
require_once ("libs/character_lib.php");
require_once ("libs/ability_lib.php");
require_once ("libs/quest_lib.php");
require_once ("libs/logs_lib.php");
require_once ("libs/bans_lib.php");
require_once ("libs/item_lib.php");
require_once ("libs/bbcode_parser.php");

if (!isset($_SESSION)) {
	session_start();
}

//non faccio partire il controllo sulla sessione, voglio che anche chi non è loggato possa aprire la pagina

$character = null;

if (isset($_REQUEST['id'])) {
	//cerco in base all'id
	$character = new Character($_REQUEST['id']);
} elseif (isset($_REQUEST['name'])) {
	//cerco in base al nome
	$character = new Character(null, $_REQUEST['name']);
} else {
	//non ho parametri, chiudo
	echo "Missing character id";
	exit();
}
$character->parseFromDb(true);

if ($character->exists() == false) {
	echo "Specified character doesn't exists";
	exit ;
}

//se sono loggato potrei avere dei privilegi aggiuntivi
if (check_logged()) {

	$allowEdit = false;
	$allowMove = false;
	$allowFullView = false;
	$allowChangeEmail = false;
	$allowChangePws = false;
	$allowChangeModlevel = false;
	$allowDelete = false;
	$allowBan = false;
	$allowAbRemove = false;
	$allowStatChange = false;
	$allowChangeCharName = false;
	$allowChangeAccName = false;

	$MyAcc = new Account(null, $_SESSION['id']);
	$MyAcc->parseFromDb();
	$modlevel = $MyAcc->getModLevel();
	$MyChar = new Character($_SESSION['char_id']);
	$MyChar->parseFromDb(false, false);
	$mymasterlevel = $MyChar->getMasterLevel();
	unset($MyChar);

	if ($modlevel >= $avatar_edit_level_required || $_SESSION['char_id'] == $character->getCharId()) {//sono un moderatore o sono me stesso
		$allowEdit = true;
		$allowFullView = true;
	}

	if ($modlevel >= $char_move_level_required) {//ho il livello sufficiente per spostare il pg
		$allowMove = true;
	}
	if ($modlevel >= $admin_change_email_required) {
		$allowChangeEmail = true;
		$allowChangeCharName = true;
		$allowChangeAccName = true;
	}
	if ($modlevel >= $avatar_edit_level_required || ($_SESSION['char_id'] == $character->getCharId() && $allow_ability_self_remove)) {
		$allowAbRemove = true;
	}
	if ($modlevel >= $admin_change_password_required) {
		$allowChangePws = true;
	}
	if ($modlevel >= $admin_change_modlevel_required) {
		$allowChangeModlevel = true;
	}
	if ($modlevel >= $admin_delete_characc_required) {
		$allowDelete = true;
	}
	if ($modlevel >= $admin_ban_required) {
		$allowBan = true;
	}
	if ($modlevel >= $avatar_edit_level_required) {
		$allowStatChange = true;
	}
}

//controllo se sono arrivati comandi
if (isset($_REQUEST['modify_field']) && isset($_REQUEST['new_value'])) {

	$eKey = $_REQUEST['modify_field'];
	$eValue = $_REQUEST['new_value'];

	$avtCheckAuthEdit = $avatar_fields->getCharXList();
	if (!array_key_exists($eKey, $avtCheckAuthEdit))
		exit();

	$val = $avtCheckAuthEdit[$eKey];

	$editCharX = false;

	//controllo gli effettivi diritti di view ed edit

	//se sono me stesso e se il campo lo prevede, mi abilito
	if ($val->getSelfedit() && $_SESSION['char_id'] == $character->getCharId()) {
		$editCharX = true;
	}

	//se ho un livello di account adeguato all'edit/view, abilito
	if ($val->getEditMinLevel() <= $modlevel) {
		$editCharX = true;
	}

	//se ho un livello di master adeguato all'edit/view, abilito
	if ($val->getEditMinMaster() > 0 && $val->getEditMinMaster() <= $mymasterlevel) {
		$editCharX = true;
	}

	if ($editCharX) {

		$strippedTxt = trim(stripslashes($character->setAvatarX($eKey, $eValue)));

		if ($avatar_allowBBcode) {
			$avtParsedTxt = BBCode2Html(acapo($strippedTxt));
		} else {
			$avtParsedTxt = acapo($strippedTxt);
		}

		echo "<div id=\"parsed\">$avtParsedTxt</div><div id=\"original\">" . html_entity_decode($strippedTxt, ENT_COMPAT, 'UTF-8') . "</div>";
		//echo "$eKey->$eValue";
	} else {
		echo "Errore, permessi insufficienti";
	}

	exit();
}

//controllo se sono arrivati cambi stats
if (isset($_REQUEST['modify_stat']) && isset($_REQUEST['new_stat'])) {
	if ($allowStatChange) {
		$newStat = array();
		$newStat[$_REQUEST['modify_stat']] = $_REQUEST['new_stat'];

		$oldStats = $character->getStats();

		if ($oldStats[$_REQUEST['modify_stat']] != $newStat[$_REQUEST['modify_stat']])
			$character->setStats($newStat);

		echo $_REQUEST['new_stat'];

	} else {
		echo "Errore, permessi insufficienti";
	}
	exit();
}

//controllo se sono arrivati plus stats
if (isset($_REQUEST['plus_stat'])) {
	if ($allowFullView && $character->getCharPx() >= 1) {

		$newS = $character->addStatPt($_REQUEST['plus_stat']);

		if ($newS != false)
			echo $newS;

	} else {
		echo "punti esauriti";
	}
	exit();
}

//controllo se sono arrivati cambi di soldi
if (isset($_REQUEST['modify_money']) && isset($_REQUEST['new_money'])) {
	if ($allowStatChange) {

		if (intval($character->getMoney()) != intval($_REQUEST['new_money'])) {
			$character->setMoney($_REQUEST['new_money']);
		}
		echo $character->getMoney();

	} else {
		echo "Errore, permessi insufficienti";
	}
	exit();
}

//controllo se è arrivato cambio px
if (isset($_REQUEST['modify_pxtal']) && isset($_REQUEST['new_value'])) {
	if ($allowStatChange) {

		switch ($_REQUEST['modify_pxtal']) {
			case 'px' :
				$px = intval($_REQUEST['new_value']) - $character->getPx();

				if ($px != 0)
					$character->addPx($px, true);
				echo $character->getPx();

				break;

			case 'talents' :
				$talents = intval($_REQUEST['new_value']) - $character->getTalents();

				if ($talents != 0)
					$character->addTalents($talents, true);
				echo $character->getTalents();

				break;

			default :
				break;
		}

	} else {
		echo "Errore, permessi insufficienti";
	}
	exit();
}

//controllo se sono arrivate abilità
if (isset($_REQUEST['ability']) && isset($_REQUEST['ability_action']) && $allow_abilities) {

	//se l'azione è di acquisto, compro l'abilità
	if ($_REQUEST['ability_action'] == 'buy' && $allowEdit) {

		$buyAbl = new Ability(null, $_REQUEST['ability']);
		$buyAbl->readFromDb();

		if ($buyAbl->isBuyable($character) && $buyAbl->getAvailableSubscription() != 2) {
			$buyAbl->BuyAbility($character);
			//echo "<div id=\"ablResult\">Abilità acquistata.</div>";
		} else {
			echo "<div id=\"ablResult\">Impossibile acquistare l'abilità.</div>";
		}
	} elseif ($_REQUEST['ability_action'] == 'del' && $allowAbRemove) {

		$delAbl = new BuyedAbility(null, $_REQUEST['ability']);
		$delAbl->deleteBuyedAbility();
		//echo "<div id=\"ablResult\">Abilità rimossa.</div>";

	} elseif ($_REQUEST['ability_action'] == 'upgrade' && $allowEdit) {

		$upAbl = new BuyedAbility(null, $_REQUEST['ability']);
		$upAbl->readFromDb();
		$upAbl->readAbility();
		if ($upAbl->getAbility()->getAvailableSubscription() != 2 && $upAbl->upgrade($character, 1, true)){
			//echo "<div id=\"ablResult\">Abilità Upgradata.</div>";
		}

	} else {
		echo "<div id=\"ablResult\"></div>";
	}

	$ablList = new BuyedAbilityList($character->getCharId());
	echo "
      		<div id=\"descAbility\" class=\"roundcorner\" >Descrizione Abilità</div>
      		<p>Passa il mouse sopra i nomi per visualizzarne la descrizione</p>
      		<div id=\"AbListContainer\">
      		Abilità Possedute:
      		<div id=\"gotAbilities\" class=\"roundcorner\"><ul>";

	foreach ($ablList->getAbilities() as $key => $value) {
		$value->readAbility();
		if ($allowEdit) {

			if ($allowAbRemove)
				$deleter = "<a href=\"#\" class=\"delAbl\" rel=\"{$value->getId()}\"><img src=\"images/icons/delete.png\" border=\"0\" title=\"Cancella\" alt=\"Cancella\" style=\"vertical-align:middle\" /></a>";

			if ($value->isUpgradable($character, 1) && $value->getAbility()->getAvailableSubscription() != 2) {

				$Abbuy_cost_string = "";
				$cost_arr = $value->getUpgradeCost($value->getLevel() + 1);
				if ($cost_arr[0] > 0) {
					$Abbuy_cost_string .= $cost_arr[0] . " $valuta_plurale";
					if ($cost_arr[1] > 0)
						$Abbuy_cost_string .= ", ";
				}

				if ($cost_arr[1] > 0)
					$Abbuy_cost_string .= $cost_arr[1] . " px";

				if ($Abbuy_cost_string == "")
					$Abbuy_cost_string = "Free!";

				$upgrader = "<a href=\"#\" class=\"upAbl\" rel=\"{$value->getId()}\"><img src=\"images/icons/up_16.png\" border=\"0\" title=\"Upgrade\" alt=\"Upgrade\" style=\"vertical-align:middle\" /></a> ( $Abbuy_cost_string )";
			} else { $upgrader = "";
			}

		} else {
			$deleter = "";
			$upgrader = "";
		}

		if ($value->getAbility()->getMaxlevel() > 1) {
			$curAbLev = "[lv: {$value->getLevel()}]";
		} else { $curAbLev = "";
		}

		echo "<li class=\"abilitydesc\"><span class=\"ABDsc\" abname=\"{$value->getAbility()->getName()} {$curAbLev}\" ><span class=\"ABDscH\">{$value->getAbility()->getDescription()}</span> {$value->getAbility()->getName()} {$curAbLev}</span> {$deleter} {$upgrader}</li>\n";

	}
	echo "</ul></div>";

	if ($allowEdit) {
		$ablBuyableList = new AbilityList();
		$ablBuyableList->populateList(null, null, -1, null, true, $character);
		echo "Abilità Acquistabili:
				<div id=\"buyAbilities\" class=\"roundcorner\"><ul>";
		foreach ($ablBuyableList->getList() as $key => $value) {

			$Abbuy_cost_string = "";
			$cost_arr = $value->getCostArray();
			if ($cost_arr[0] > 0) {
				$Abbuy_cost_string .= $cost_arr[0] . " $valuta_plurale";
				if ($cost_arr[1] > 0)
					$Abbuy_cost_string .= ", ";
			}

			if ($cost_arr[1] > 0)
				$Abbuy_cost_string .= $cost_arr[1] . " px";

			if ($Abbuy_cost_string == "")
				$Abbuy_cost_string = "Free!";

			echo "<li class=\"abilitydesc\"><span class=\"ABDsc\" abname=\"{$value->getName()}\" ><span class=\"ABDscH\">{$value->getDescription()}</span>{$value->getName()} </span><a href=\"#\" class=\"buyAbl\" rel=\"{$value->getId()}\"><img src=\"images/icons/plus_16.png\" title=\"Apprendi\" alt=\"Impara\" border=\"0\" style=\"vertical-align:middle\" /></a>( $Abbuy_cost_string )</li>\n";
		}
		echo "</ul></div>";
	}
	echo "</div>";
	exit();
}

//se mi chiede le quest le carico
if (isset($_REQUEST['quest']) && $modlevel >= $admin_view_quest_required && $allow_px) {

	$questList = new QuestList(null, $character->getCharId());
	$questList->readList();

	echo "<div id=\"questResult\">\n";
	echo "<table class=\"stretch\">
  			<tr>
  				<th width=\"25%\" style=\"text-align:left\">Quest</td>
  				<th width=\"13%\" style=\"text-align:left\">Master</td>
  				<th width=\"5%\">Px</td>
  				<th width=\"30%\">Note</td>
  				<th width=\"27%\">Data</td>
  			</tr>\n";

	foreach ($questList->GetList() as $k => $v) {

		$masChar = new Character($v->getMaster());
		$masChar->checkExistance();
		$i = 0;
		//$v=new Quest();
		foreach ($v->getElements() as $ke => $ve) {

			echo "<tr>
              <td>{$v->getName()}</td>
              <td>{$masChar->getCharName()}</td>
              <td style=\"text-align:center\">{$ve->getPx()}</td>
              <td>{$ve->getNote()}</td>
              <td style=\"text-align:center\">" . itaTime($v->getDate()) . "</td>
            </tr>\n";
			$i++;
		}
	}
	echo "<tr><td colspan=\"2\" align=\"right\">Totale:</td><td colspan=\"3\">{$character->Account()->getTotalEarnedXp($character->getCharId())}</td></tr>";
	echo "</table>\n</div>\n";

	exit();
}

//se mi chiede gli ip li carico
if (isset($_REQUEST['logsIp']) && $modlevel >= $admin_view_ip_required) {

	$LogsList = new LogList();
	$LogsList->readFromDb($character->Account()->getId());

	echo "<div id=\"logsResult\">\n";
	echo "<table class=\"stretch\">\n<tr><td>Ip</td><td>client</td><td>data</td></tr>\n";
	$i = 0;
	foreach ($LogsList->getLogsIp() as $k => $v) {

		//$v=new LogIp();
		$browser = getBrowser($v->getUserAgent());

		$logString = $browser['name'] . ' ' . $browser['version'] . ' su ' . $browser['platform'];

		echo "<tr>
            <td>{$v->getIp()}</td><td>$logString</td><td>" . itaTime($v->getDate()) . "</td></tr>\n";
		$i++;
	}
	echo "</table>\n</div>\n";

	exit();
}

//se mi chiede gli oggetti li carico
if (isset($_REQUEST['items']) && $allow_items) {

	if (isset($_REQUEST['item_action']) && $allowEdit) {

		$row_item = array();
		$row_item['char_id'] = $character->getCharId();
		$row_item['id'] = $_REQUEST['items'];

		if ($_REQUEST['item_action'] == 'e') { //equippo

			$buyedItem = new BuyedItem($row_item);
			$buyedItem->Equip(1);

		} elseif ($_REQUEST['item_action'] == 'u') { //un-equippo
			$buyedItem = new BuyedItem($row_item);
			$buyedItem->Equip(0);
		} elseif ($_REQUEST['item_action'] == 'n') { //sono le note
			$buyedItem = new BuyedItem($row_item);
			$buyedItem->setNotes($_REQUEST['value']);
			echo $buyedItem->getNotes();
		}

		exit();
	}

	if (isset($_REQUEST['newowner'])) {

		$newOwner = new Character(null, $_REQUEST['newowner']);
		$newOwner->checkExistance();

		echo "<div id=\"transferResult\">";
		if ($newOwner->exists()) {
			$buyedItem = new BuyedItem(null, $_REQUEST['items']);
			$buyedItem->readFromDb();

			if ($buyedItem->changeOwner($newOwner->getCharId())) {
				echo "Oggetto Trasferito";
			} else {
				echo "Impossibile dare l'oggetto a {$_REQUEST['newowner']}";
			}
		} else {
			echo "Il destinatario specificato non esiste";
		}

		echo "</div>";

	}

	if (isset($_REQUEST['load'])) {

		$buyedItem = new BuyedItem(null, $_REQUEST['items']);
		$buyedItem->readFromDb();
		$buyedItem->readItem();

		echo "
			<span id=\"itemId\">{$buyedItem->getItem()->getId()}</span>
			<span id=\"name\">{$buyedItem->getItem()->getName()}</span>
			<span id=\"image\"><img src=\"{$buyedItem->getItem()->getImage()}\" class=\"shopimg\" /></span>
			<span id=\"description\">{$buyedItem->getItem()->getDescription()}</span>
			<span id=\"notes\">{$buyedItem->getNotes()}</span>
			<span id=\"changeowner\"><form action=\"character_sheet.php?name={$character->getCharName()}&items={$buyedItem->getId()}\"><div>Cedi: <input type=\"text\" name=\"newowner\" class=\"recipient\"/> <input type=\"submit\" value=\"Invia\"/></div></form></span>
			";

		exit();
	}

	$buyedList = new BuyedItemList($character->getCharId());
	echo "<div id=\"itemsResult\">\n";
	
	if(!$allowEquipItems){
		
			echo "<div id=\"itemListContainer\"><span class=\"sortH\">Oggetti: <span id=\"count_equipped\">{$buyedList->getSize()}</span></span>
		  		<ul id=\"equippeditems\" class=\"roundcorner\">\n";
			foreach ($buyedList->getList() as $k => $v) {
		
				echo "<li itemid=\"{$v->getId()}\">{$v->getItem()->getName()}</li>\n";
		
			}
			echo "</ul>";
		
	}else{
			echo "<p>Per equipaggiare un oggetto trascinalo nella apposita sezione.</p>
				  <div id=\"itemListContainer\"><span class=\"sortH\">Oggetti Equipaggiati: <span id=\"count_equipped\">{$buyedList->getEquippedSize()}</span></span>
		  		  <ul id=\"equippeditems\" class=\"connectedItems roundcorner\">\n";
			foreach ($buyedList->getEquippedItems() as $k => $v) {
		
				echo "<li itemid=\"{$v->getId()}\">{$v->getItem()->getName()}</li>\n";
		
			}
			echo "</ul>\n";
		
			echo "<span class=\"sortH\">Oggetti Non Equipaggiati: <span id=\"count_unequipped\">{$buyedList->getUnequippedSize()}</span></span>
		  		<ul id=\"unequippeditems\"  class=\"connectedItems roundcorner\">\n";
			foreach ($buyedList->getUnequippedItems() as $k => $v) {
		
				echo "<li itemid=\"{$v->getId()}\">{$v->getItem()->getName()}</li>\n";
		
			}
			echo "</ul>";
	}
	
	echo "</div>\n";
	

	

	if ($allowEdit) {

		$changeOwner = "<div class=\"changeOwner\"></div>";
	} else {
		$changeOwner = "";

	}

	echo "<div id=\"itemsmallShow\" class=\"roundcorner\">
			<div class=\"theItem\" itemId=\"\">
	  			<h3 class=\"itemname centertxt\"></h3>
	  			<div class=\"itemimg\"></div>
	  			Descrizione:
	  			<div class=\"itemdsc roundcorner\"></div>
	  			Note:
	  			<div class=\"itemnotes edit_itemNotes roundcorner\" id=\"0\"></div>
	  			$changeOwner
  			</div>
  		</div>
  		<div id=\"loadedItems\" style=\"display:none\"></div>\n";

	echo "<p style=\"clear:both;\"></p>";

	echo "</div>\n";
	exit();
}

//se mi chiede la pagina di gestione la carico
if (isset($_REQUEST['config']) && $allowEdit) {

	// controllo se l'utente è bannato
	$BanList = new BanList();
	$BanList->populateList($character->Account()->getId());
	$currentBan = $BanList->getLastBan();

	$i = 0;
	echo "<div id=\"configResult\">\n";

	//SEZIONE DI MODIFICA
	if (isset($_REQUEST['new_mail']) && $_REQUEST['new_mail'] != "" && $allowChangeEmail && $character->Account()->getEmail() != $_REQUEST['new_mail']) {
		if (validate_email($_REQUEST['new_mail']) && !email_exist($_REQUEST['new_mail'])) {
			$character->Account()->changeEmail($_REQUEST['new_mail']);
			echo "Email modificata.<br />";
		} else {
			echo "L'indirizzo email inserito non è valido o è già in uso.";
		}
	}
	if (isset($_REQUEST['new_charname']) && $_REQUEST['new_charname'] != "" && $allowChangeCharName && $character->getCharName() != $_REQUEST['new_charname']) {
		$tmpCh = new Character(null, $_REQUEST['new_charname']);
		$tmpCh->checkExistance();
		if (!$tmpCh->exists() && validate_username($_REQUEST['new_charname'])) {
			$character->setCharname($_REQUEST['new_charname']);
			echo "Nome del Personaggio modificato.<br />";
		} else {
			echo "Il nome del pg inserito non è valido o è già in uso.";
		}
	}
	if (isset($_REQUEST['new_accname']) && $_REQUEST['new_accname'] != "" && $allowChangeAccName && $character->Account()->getUsername() != $_REQUEST['new_accname']) {
		$tmpAcc = new Account(null, null, $_REQUEST['new_accname']);
		$tmpAcc->checkExistance();
		if (!$tmpAcc->exists() && validate_username($_REQUEST['new_accname'])) {
			$character->Account()->setUsername($_REQUEST['new_accname']);
			echo "Nome dell' Account modificato.<br />";
		} else {
			echo "Il nome del account inserito non è valido o è già in uso.";
		}
	}
	if (isset($_REQUEST['new_level']) && $_REQUEST['new_level'] != "" && $allowChangeModlevel) {
		if ($character->Account()->getModLevel() != $_REQUEST['new_level']) {
			$character->Account()->setMod($_REQUEST['new_level']);
			echo "Livello utente modificato.<br />";
		}
	}
	if (isset($_REQUEST['new_masterlv']) && $_REQUEST['new_masterlv'] != "" && $allowChangeModlevel) {
		if ($character->getMasterLevel() != $_REQUEST['new_masterlv']) {
			$character->setMaster($_REQUEST['new_masterlv']);
			echo "Livello master modificato.<br />";
		}
	}
	if (isset($_REQUEST['new_pw1']) && $_REQUEST['new_pw1'] != "" && isset($_REQUEST['new_pw2'])) {

		//se sono player devo mettere la pass vecchia, altrimenti no
		if ($_REQUEST['new_pw2'] != $_REQUEST['new_pw1']) {
			echo "Errore, la nuova password e la conferma sono diverse.<br />";
		} elseif ($character->Account()->hashPassword($_REQUEST['old_pw']) == $character->Account()->getPassword()) {
			echo "Password modificata.<br />";
			$character->Account()->changePass($_REQUEST['new_pw1']);
		} elseif ($allowChangePws) {
			echo "Password modificata.<br />";
			$character->Account()->changePass($_REQUEST['new_pw1'], true);
		} else {
			echo "Errore, la vecchia password non corrisponde.<br />";
		}
	}
	if (isset($_REQUEST['ban_until']) && $_REQUEST['ban_until'] != "" && $allowBan && $BanList->getLastBan() != $_REQUEST['ban_until']) {

		$Ban = new Ban();
		$Ban->createBan($_SESSION['id'], $character->Account()->getId(), $_REQUEST['ban_until'], "nessuna valida");
		$BanList->addBan($Ban);

		echo "-" . $Ban->getStatus();

		$currentBan = $BanList->getLastBan();
		echo "Account Bannato.<br />";
	}
	if (isset($_REQUEST['delete_char']) && $_REQUEST['delete_char'] != "" && $allowDelete) {

		$character->deleteCharacter();

	}
	if (isset($_REQUEST['delete_acc']) && $_REQUEST['delete_acc'] != "" && $allowDelete) {

		$character->Account()->deleteAccount();

	}

	//FINE

	foreach ($acc_level_array as $k => $v) {

		if ($k == $character->Account()->getModLevel()) {
			$ckd = "selected=\"selected\"";
		} else {$ckd = "";
		}

		$ModLevOpt .= "<option value=\"{$k}\" $ckd >{$v}</option>\n";
	}

	foreach ($master_level_array as $k => $v) {

		if ($k == $character->getMasterLevel()) {
			$ckd = "selected=\"selected\"";
		} else {$ckd = "";
		}

		$MasterLevOpt .= "<option value=\"{$k}\" $ckd >{$v}</option>\n";

	}

	echo "<h2>Gestione dell'account e del personaggio</h2>
        <form id=\"config_form\" action=\"character_sheet.php\">
        <input type=\"hidden\" name=\"name\" value=\"{$character->getCharName()}\" />
        <input type=\"hidden\" name=\"config\" value=\"2\" />
        <table class=\"stretch\">
          <tr>
            <td>Vecchia password</td>
            <td><input type=\"password\" name=\"old_pw\" /></td>
          </tr>
          <tr>
            <td>Nuova password</td>
            <td><input type=\"password\" name=\"new_pw1\" /></td>
          </tr>
          <tr>
            <td>Conferma nuova password</td>
            <td><input type=\"password\" name=\"new_pw2\" /></td>
          </tr>";

	if ($allowChangeEmail || $allowChangeModlevel || $allowDelete || $allowBan || $allowChangeAccName || $allowChangeCharName) {

		echo "<tr>
            <td colspan=\"2\">Area riservata agli amministratori</td>
          </tr>";
	}
	if ($allowChangeAccName) {

		echo "<tr>
            <td>Modifica nome Account</td>
            <td><input type=\"text\" name=\"new_accname\" value=\"{$character->Account()->getUsername()}\" /></td>
          </tr>";
	}
	if ($allowChangeCharName) {

		echo "<tr>
            <td>Modifica nome Personaggio</td>
            <td><input type=\"text\" name=\"new_charname\" value=\"{$character->getCharName()}\" /></td>
          </tr>";
	}
	if ($allowChangeEmail) {

		echo "<tr>
            <td>Modifica email</td>
            <td><input type=\"text\" name=\"new_mail\" value=\"{$character->Account()->getEmail()}\" /></td>
          </tr>";
	}
	if ($allowChangeModlevel) {

		echo "<tr>
            <td>Livello Utente</td>
            <td><select name=\"new_level\">{$ModLevOpt}</select></td>
          </tr>
          <tr>
            <td>Livello Master</td>
            <td><select name=\"new_masterlv\">{$MasterLevOpt}</select></td>
          </tr>
          ";
	}
	if ($allowBan) {

		if (isset($currentBan) && $currentBan->isActive()) {
			$isBan = $currentBan->getBanUntil();
		} else {
			$isBan = "";
		}

		echo "<tr>
            <td>Banna account</td>
            <td><input type=\"text\" id=\"ban_until\" name=\"ban_until\" value=\"{$isBan}\" placeholder=\"fino al...\" /></td>
          </tr>";
	}
	if ($allowDelete) {

		echo "<tr>
            <td>Cancella Personaggio</td>
            <td><input type=\"checkbox\" name=\"delete_char\" value=\"1\" /> (Attenzione: operazione irreversibile!)</td>
          </tr>
          <tr>
            <td>Cancella Account</td>
            <td><input type=\"checkbox\" name=\"delete_acc\" value=\"1\" /> (Attenzione: operazione irreversibile!)</td>
          </tr>";
	}

	echo "<tr>
            <td colspan=\"2\"><input type=\"submit\" value=\"Salva\" /></td>
          </tr>
        </table>
        </form>
        </div>";

	exit();
}

//se mi richiede le note le mostro
if (isset($_REQUEST['notes'])) {
	
	
	//faccio un explode delle note, in modo da visualizzare solo quelle richieste
	
	$notesArr=explode("-", $_REQUEST['notes']);
	
	echo "<div id=\"sheetResult\">
			<table class=\"stretch\">\n";

	$AvatarItems = $character->Avatar();
	foreach ($avatar_fields->getCharXList() as $key => $val) {
		
		if($_REQUEST['notes']!='all' && !in_array($key, $notesArr))
			continue;
		
		
		$value = $AvatarItems[$key];

		//$val=new ConfigCharX();

		//per ogni charX inizializzo a false
		$viewCharX = false;
		$editCharX = false;

		//controllo gli effettivi diritti di view ed edit

		//se sono me stesso e se il campo lo prevede, mi abilito
		if ($val->getSelfedit() && $_SESSION['char_id'] == $character->getCharId()) {
			$editCharX = true;
		} elseif ($val->getSelfview() && $_SESSION['char_id'] == $character->getCharId()) {
			$viewCharX = true;
		}

		//se ho un livello di account adeguato all'edit/view, abilito
		if ($val->getEditMinLevel() <= $modlevel) {
			$editCharX = true;
		} elseif ($val->getViewMinLevel() <= $modlevel) {
			$viewCharX = true;
		}

		//se ho un livello di master adeguato all'edit/view, abilito
		if ($val->getEditMinMaster() > 0 && $val->getEditMinMaster() <= $mymasterlevel) {
			$editCharX = true;
		} elseif ($val->getViewMinMaster() > 0 && $val->getViewMinMaster() <= $mymasterlevel) {
			$viewCharX = true;
		}

		//se edit è true, allora forzo true anche su view
		if ($editCharX)
			$viewCharX = true;

		if (!$viewCharX)
			continue;

		if ($editCharX) {
			$edit_field = "<a href=\"#\" class=\"edit_link\" rel=\"{$value->getType()}\"><br/><img src=\"images/icons/pencil_16.png\" border=\"0\" /></a>";
			
			if ($avatar_allowBBcode) {
				$edit_class = "edit_area";
			}else{
				$edit_class="edit_areaNoBB";
			}
		
		} else {
			$edit_field = '';
			$edit_class = "noedit_area";
		}

		if ($value->getType() == 99 || $value->getType()==98){
			
			if(!$editCharX) continue;
			$edit_class="edit_areaNoBB";
		}

		if ($avatar_allowBBcode) {
			$avtParsedTxt = BBCode2Html(acapo($value->getText()));
		} else {
			$avtParsedTxt = acapo($value->getText());
		}

		echo "\t<tr id=\"charx_{$value->getType()}\" justified\">
              <td>
                {$value->getTypeName()}: $edit_field
              </td>
              <td>
                <div style=\"display:none\" id=\"original_{$value->getType()}\">" . html_entity_decode($value->getText(), ENT_COMPAT, 'UTF-8') . "</div>
                <div class=\"{$edit_class}\" id=\"{$value->getType()}\">$avtParsedTxt</div>
              </td>
            </tr>\n";
	}

	echo "</table>
        </div>";

	exit();

}

//se mi chiede la scheda la mostro
if (isset($_REQUEST['sheet'])) {

	$i = 0;
	echo "<div id=\"sheetResult\">
        <table class=\"stretch\">
          \n";

	if ($allowFullView) {
		echo "  <tr>
            <td class=\"setW\">Account:</td>
            <td>{$character->Account()->getUsername()}</td>
          </tr>
          <tr>
            <td>Email:</td>
            <td>{$character->Account()->getEmail()}</td>
          </tr>\n";
	}

	if ($mask_multichar && $character->Account()->getUsername() != $character->getCharName()) {
		$ModLvlName = $acc_level_array[$mask_multichar_as];
	} else {
		$ModLvlName = $character->Account()->getModLevelName();
	}

	$maleicon = "<img src=\"images/icons/male-icon.png\" alt=\"maschio\" title=\"maschio\" />";
	$femaleicon = "<img src=\"images/icons/female-icon.png\" alt=\"femmina\" title=\"femmina\" />";

	//se l'utente può modificare i px/talenti abilito i comandi
	if ($allowStatChange) {
		$edit_pxLink = "<a href=\"#\" class=\"edit_link\" rel=\"px\"><img src=\"images/icons/pencil_16.png\" border=\"0\" /></a>";
		$edit_class = "edit_pxtal";

		$edit_talLink = "<a href=\"#\" class=\"edit_link\" rel=\"talents\"><img src=\"images/icons/pencil_16.png\" border=\"0\" /></a>";

	} else {
		$edit_statLink = '';
		$edit_talLink = '';
		$edit_class = "noedit_px";
	}

	
	$levelRow="";
	if ($allow_levels) {
		$levelRow="<tr>
          	<td>Livello:</td>
          	<td><span>{$character->getLevel()}</span></td>
          </tr>";
	}
	
	$talentRow="";
	if($allow_talents){
		$talentRow="<tr>
          	<td>{$edit_talLink}Talenti spendibili:</td>
          	<td><span class=\"{$edit_class}\" id=\"talents\" >{$character->getTalents()}</span></td>
          </tr>";
	}
	
	$pxRow="";
	if($allow_px){
		$pxRow="<tr>
            <td>{$edit_pxLink}Punti Esperienza:</td>
            <td><span class=\"{$edit_class}\" id=\"px\" >{$character->getPx()}</span></td>
          </tr>";
	}
	
	
	
	$ptsUsable="";
	if($character->getCharPx()>0){
		$ptsUsable="<tr>
          	<td>Punti stats spendibili:</td>
          	<td><span>{$character->getCharPx()}</span></td>
          </tr>";
	}

	echo "  <tr id=\"tr_nome\">
            <td>Nome:</td>
            <td>{$character->getCharName()} <a href=\"pm_new.php?mailto={$character->getCharName()}\" target=\"imessage\" class=\"popUp\"><img src=\"images/icons/letter_16.png\" border=\"0\" alt=\"Invia Messaggio\"/></a></td>
          </tr>
          <tr>
            <td>Livello Account:</td>
            <td>{$ModLvlName}</td>
          </tr>
          <tr>
            <td>Sesso:</td>
            <td>" . (($character->getSex() == 2) ? $femaleicon : $maleicon) . "</td>
          </tr>
          $pxRow
          $levelRow
          $talentRow
          $ptsUsable
          <tr>
            <td>Master:</td>
            <td>{$character->getMasterLevel()}</td>
          </tr>
          <tr>
            <td>Data Creazione:</td>
            <td>" . itaTime($character->getDate()) . "</td>
          </tr>
          <tr>
            <td>Ultimo Accesso:</td>
            <td>" . itaTime($character->getLocationTime()) . "</td>
          </tr>
          <tr>
            <td>Ultimo Luogo Visitato:</td>
            <td>{$character->getLocationName()}</td>
          </tr>\n";

	if ($allowFullView) {

		if ($allowStatChange) {
			$edit_moneyLink = "<a href=\"#\" class=\"edit_link\" rel=\"money_div\"><img src=\"images/icons/pencil_16.png\" border=\"0\" /></a>";
			$edit_class = "edit_money";
		} else {
			$edit_statLink = '';
			$edit_class = "noedit_money";
		}

		echo "  <tr>
            <td>{$edit_moneyLink}{$valuta_plurale}:</td>
            <td><span class=\"{$edit_class}\" id=\"money_div\" >{$character->getMoney()}</span></td>
          </tr>\n";
	}


	if($allow_stats){
		
	
		foreach ($character->getStats(true) as $key => $value) {
	
			if ($key != 'undefined') {
				$bonus = "";
	
				
	
				//ho punti da spendere?
				$plus = "";
				if ($allowFullView && $character->getCharPx() >= 1)
					$plus = "<a href=\"#\" class=\"plus_stat\" rel=\"{$key}\" >+</a>";
	
				//se l'utente può modificare le stats abilito i comandi
				if ($allowStatChange) {
					$edit_statLink = "<a href=\"#\" class=\"edit_link\" rel=\"{$key}\"><img src=\"images/icons/pencil_16.png\" border=\"0\" /></a>";
					$edit_class = "edit_stat";
				} else {
					$edit_statLink = '';
					$edit_class = "noedit_stat";
				}
				echo "\t<tr><td>{$edit_statLink}{$key}:</td><td><span class=\"{$edit_class}\" id=\"{$key}\" >$value</span>{$plus} {$bonus}</td></tr>\n";
			}
		}
		
	}
	

	$character->readGroups();
	foreach ($groups_name_array as $key => $value) {

		$var = "";
		foreach ($character->getGroups() as $k => $v) {

			if ($v->getGroupType() == $key)
				$var .= $v->getName() . "<br />\n";
		}

		echo "\t<tr><td>$value:</td><td>$var</td></tr>\n";
	}

	echo "</table>
        </div>";

	exit();
}
?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Character Sheet - <?php echo $character->getCharName(); ?></title>
<?php
if(!IS_AJAX) {
 include_headers('all');
} ?>
<script type="text/javascript">

<?php
if(!IS_AJAX) {
?>
$.editable.addInputType('markitup', {
    element : $.editable.types.textarea.element,
    plugin  : function(settings, original) {
        $('textarea', this).markItUp(settings.markitup);
    }
});
<? } ?>

function parseItems(ui){
	
	<?php if($allowEdit){ ?>
	
	$( "#equippeditems, #unequippeditems" ).sortable({
			connectWith: ".connectedItems",
			receive: function(event, ui) {
						//alert($(ui.item).attr("itemid"));
						
						if($(ui.sender).attr("id")=='unequippeditems'){
							$("#count_unequipped").html( parseInt($("#count_unequipped").html())-1 );
							$("#count_equipped").html( parseInt($("#count_equipped").html())+1 );
							
							$.post("character_sheet.php",
									{ id: "<?php echo $character->getCharId(); ?>", items: $(ui.item).attr('itemid'), item_action: 'e' }
							);
							
						}
						if($(ui.sender).attr("id")=='equippeditems'){
							$("#count_unequipped").html( parseInt($("#count_unequipped").html())+1 );
							$("#count_equipped").html( parseInt($("#count_equipped").html())-1 );
							
							$.post("character_sheet.php",
									{ id: "<?php echo $character->getCharId(); ?>", items: $(ui.item).attr('itemid'), item_action: 'u' }
							);
						}
						
					 }
	}).disableSelection();
	
	
	<?php } ?>
	
	return;

}

function parseAbilities(ui){
                
        $(ui.panel).filter('div#ablResult').each(function(){
                            $("#ablResult").empty();
                            $("#ablResult").append($(this).html());
                            $('#ablResult').fadeIn();
        });
                
        //helper delle descrizione
		$(".ABDsc").each(function(){
			
			if($(this).find(".ABDscH").html().length){
				descript=$(this).find(".ABDscH").html();
			}else{
				descript="Descrizione mancante";
			}
			
			$(this).CreateBubblePopup({
				width: 300,			
				innerHtml: descript,
				themeName: 'all-black',
				position: 'right',
				align: 'top',
				tail: {align:'top',hidden: false},
				divStyle: {margin:'-8px 0 0 0'},
				themePath: 'js/jquerybubblepopup-themes'
			}); 
		});
                
}

var xhr=null;

$(document).ready(function(){
          
  <?
  if(IS_AJAX){
  ?>
  add_media($("#yt_video").val(),"<?php echo $character->getCharName(); ?>");
  <?
  }else{
  ?>
  parent.add_media($("#yt_video").val(),"<?php echo $character->getCharName(); ?>");
  <? } ?>
  
  
  
		$(".avatar_container").on('click','a.buyAbl', function(){
			if(confirm('Sei Sicuro di voler imparare questa abilità')){
            	$.post("character_sheet.php",
                    { id: "<?php echo $character->getCharId(); ?>", ability: this.rel, ability_action: 'buy' },
                    function(data) {
                      $(myui.panel).html(data);
                      parseAbilities(myui);
                    }
                );
            }
			return false;
        });
        
        
        $(".avatar_container").on('click','a.edit_link', function(){
                $('#'+this.rel).trigger('dblclick');
                return false;
        });
        
        $(".avatar_container").on('click','a.delAbl', function(){
        	if(confirm('Sei Sicuro di voler dimenticare questa abilità')){
            	$.post("character_sheet.php",
                    { id: "<?php echo $character->getCharId(); ?>", ability: this.rel, ability_action: 'del' },
                    function(data) {
                      $(myui.panel).html(data);
                      parseAbilities(myui);
                    }
                );
             }
             return false;
        });
        
        $(".avatar_container").on('click','a.upAbl', function(){
        	if(confirm('Sei Sicuro di voler migliorare questa abilità')){
            	$.post("character_sheet.php",
                    { id: "<?php echo $character->getCharId(); ?>", ability: this.rel, ability_action: 'upgrade' },
                    function(data) {
                      $(myui.panel).html(data);
                      parseAbilities(myui);
                    }
                );
             }
             return false;
        });
        
        $(".avatar_container").on('mouseover',".connectedItems li",function(){
			
			itemId=$(this).attr('itemid');
			
			//prima cerco se è già stato caricato
			if($('#loadedItems .theItem[itemId="'+itemId+'"]').length ){
				$('#itemsmallShow').html($('#loadedItems .theItem[itemId="'+itemId+'"]').clone());
				
				<? if($allowEdit){ ?>
				$("#itemsmallShow .recipient").autocomplete({
					source: "char_list.php",
					minLength: 2,
					delay: 200
				});
				
				$('.edit_itemNotes').editable('character_sheet.php?item_action=n&id=<?php echo $character->getCharId(); ?>', { 
                         id		   : 'items',
                         name      : 'value',
                         cancel    : 'Annulla',
                         submit    : 'Salva',
                         indicator : '<img src="images/icons/loadinfo.net.gif">',
                         tooltip   : 'Click per modificare...',
                         width	   : "95%",
                         onblur    : 'ignore',
                         data      : function(value, settings) {
                            // Convert <br> to newline. 
                            var nVal =value;
                            var retval = nVal.replace(/<br[\s\/]?>/gi, '\n').replace(/^\s\s*/, '').replace(/\s\s*$/, '');
                            return retval;
                         },
                         callback  : function(value, settings){
                         	
                         	$(this).html(value);
                         	$('#loadedItems .theItem[itemId="'+itemId+'"]').find(".itemnotes").html(value);
                         	
                         	
                         }
    			});
    			
    			
    			<? } ?>
    			$("#itemsmallShow").show();
    			
			}else{ //lo carico in ajax
				
				if(xhr) xhr.abort();
				xhr = $.ajax({
						type: "POST",
					    url : "character_sheet.php",
					    data: { id: "<?php echo $character->getCharId(); ?>", items: itemId, load: '1' },
					    success : function(data) {
					        $("#itemsmallShow .theItem").attr("itemId",itemId);
					        $("#itemsmallShow .itemname").html($(data).filter("#name").html());
							$("#itemsmallShow .itemimg").html($(data).filter("#image").html());
							$("#itemsmallShow .itemdsc").html($(data).filter("#description").html());
							$("#itemsmallShow .itemnotes").html($(data).filter("#notes").html());
							$("#itemsmallShow .changeOwner").html($(data).filter("#changeowner").html());
							$("#itemsmallShow .itemnotes").attr("id",itemId);
							
							<? if($allowEdit){ ?>
							$("#itemsmallShow .recipient").autocomplete({
								source: "char_list.php",
								minLength: 2,
								delay: 200
							});
							
							$('.edit_itemNotes').editable('character_sheet.php?item_action=n&id=<?php echo $character->getCharId(); ?>', { 
			                         id		   : 'items',
			                         name      : 'value',
			                         cancel    : 'Annulla',
			                         submit    : 'Salva',
			                         indicator : '<img src="images/icons/loadinfo.net.gif">',
			                         tooltip   : 'Click per modificare...',
			                         width	   : "95%",
			                         onblur    : 'ignore',
			                         data      : function(value, settings) {
			                            // Convert <br> to newline. 
			                            var nVal = value;
			                            var retval = nVal.replace(/<br[\s\/]?>/gi, '\n').replace(/^\s\s*/, '').replace(/\s\s*$/, '');
			                            return retval;
			                         },
			                         callback  : function(value, settings){
			                         	
			                         	$(this).html(value);
			                         	$('#loadedItems .theItem[itemId="'+itemId+'"]').find(".itemnotes").html(value);
			                         	
			                         }
			    			});
			    			
			    			<? } ?>
							
							//copio itemsmallShow
							$('#itemsmallShow .theItem').clone().appendTo('#loadedItems');
							$("#itemsmallShow").show();
							
					    }
				});
			} 
			
			
		});
		
		$(".avatar_container").on('click',"a.plus_stat",function(){

			var rela=this.rel;
            $.post("character_sheet.php",
                    { id: "<?php echo $character->getCharId(); ?>", plus_stat: rela },
                    function(data) {
                      $("#"+rela).html(data);
                    }
            );

             return false;
         });
         
         $(".avatar_container").on('submit',"form",function() {
    
    		rowArr = $(this).serializeArray(); 
    		$.post($(this).attr("action"),rowArr,function(data2){ $(myui.panel).html(data2); parseAbilities(myui); parseItems(myui); } );
    
    		return false;
  		});
		
  
  var myui=null;
  
  $("#avatar_menu2").tabs({
   	
   	load: function(event, ui) {
        
        myui=ui;
        
        $('a.stay', ui.panel).click(function() {
            $(ui.panel).load(this.href);
            return false;
        });
       
       parseItems(ui);
       parseAbilities(ui);
       
    	
        $('.edit_area').editable('character_sheet.php?id=<?php echo $character->getCharId(); ?>', { 
                         id        : 'modify_field',
                         name      : 'new_value',
                         type      : 'markitup',
                         cancel    : 'Annulla',
                         submit    : 'Salva',
                         indicator : '<img src="images/icons/loadinfo.net.gif">',
                         tooltip   : 'Click per modificare...',
                         onblur    : 'ignore',
                         event     : "dblclick",
                         markitup  : mySettings,
                         data      : function(value, settings) {
                            // Convert <br> to newline. 
                            var nVal = $("#original_"+$(this).attr('id')).html();
                            
                            var retval = nVal.replace(/<br[\s\/]?>/gi, '\n').replace(/^\s\s*/, '').replace(/\s\s*$/, '');
                            return retval;
                         },
                         callback  : function(value, settings){
                         
                         	var original=$(value).filter("#original").html();
                         	$("#original_"+$(this).attr('id')).html(original);
                         	var parsed=$(value).filter("#parsed").html();
                         	$(this).html(parsed);
                         	
                         	if($(this).attr("id")=='99'){$(".avatar_img").attr("src", parsed);}
                         	
                         }
        });
        
        $('.edit_areaNoBB').editable('character_sheet.php?id=<?php echo $character->getCharId(); ?>', { 
                         id        : 'modify_field',
                         name      : 'new_value',
                         cancel    : 'Annulla',
                         submit    : 'Salva',
                         indicator : '<img src="images/icons/loadinfo.net.gif">',
                         tooltip   : 'Click per modificare...',
                         onblur    : 'ignore',
                         event     : "dblclick",
                         data      : function(value, settings) {
                            // Convert <br> to newline. 
                            var nVal = $("#original_"+$(this).attr('id')).html();
                            
                            var retval = nVal.replace(/<br[\s\/]?>/gi, '\n').replace(/^\s\s*/, '').replace(/\s\s*$/, '');
                            return retval;
                         },
                         callback  : function(value, settings){
                         
                         	var original=$(value).filter("#original").html();
                         	$("#original_"+$(this).attr('id')).html(original);
                         	var parsed=$(value).filter("#parsed").html();
                         	$(this).html(parsed);
                         	
                         	if($(this).attr("id")=='99'){$(".avatar_img").attr("src", parsed);}
                         	
                         }
        });
        
        
        $('.edit_stat').editable('character_sheet.php?id=<?php echo $character->getCharId(); ?>', { 
                         id        : 'modify_stat',
                         name      : 'new_stat',
                         cancel    : 'Annulla',
                         submit    : 'Salva',
                         indicator : '<img src="images/icons/loadinfo.net.gif">',
                         tooltip   : 'Click per modificare...',
                         event     : "dblclick",
                         onblur    : 'ignore'
        });
        
        $('.edit_money').editable('character_sheet.php?id=<?php echo $character->getCharId(); ?>', { 
                         id        : 'modify_money',
                         name      : 'new_money',
                         cancel    : 'Annulla',
                         submit    : 'Salva',
                         indicator : '<img src="images/icons/loadinfo.net.gif">',
                         tooltip   : 'Click per modificare...',
                         event     : "dblclick",
                         onblur    : 'ignore'
        });
        
        $('.edit_pxtal').editable('character_sheet.php?id=<?php echo $character->getCharId(); ?>', { 
                         id        : 'modify_pxtal',
                         name      : 'new_value',
                         cancel    : 'Annulla',
                         submit    : 'Salva',
                         indicator : '<img src="images/icons/loadinfo.net.gif">',
                         tooltip   : 'Click per modificare...',
                         event     : "dblclick",
                         onblur    : 'ignore'
        });
        
        
        //sposto la sezione appropriata sotto il nome
        //$('#tr_nome').after( $("#charx_6") );
        //$('#tr_nome').after( $("#charx_7") );
        
		$( "#ban_until" ).datepicker({
    		changeMonth: true,
    		changeYear: true,
    		dateFormat: 'yy-mm-dd'
  		});
        
    }
  });
  
            
});
</script>

</head>

<body class="avatar_subpage">
<div class="avatar_container" charname="<?php echo $character->getCharName(); ?>">
<?php //inserisco l'immagine
	$imgAvt = $character->Avatar();
	if (isset($imgAvt[99]) && $imgAvt[99]->getText() != '')
		$imgUrl = '<img class="avatar_img" src="' . $imgAvt[99]->getText() . '" />';
	else
		$imgUrl = '<img class="avatar_img" src="images/icons/noavatarimg.gif" />';

	echo $imgUrl;
	
	if (!empty($imgAvt[98]))
		echo "<input type=\"hidden\" value=\"{$imgAvt[98]->getText()}\" id=\"yt_video\" />";

	$i = 0;
?>
<div id="avatar_menu2"> 
	<ul>
		<li><a href="character_sheet.php?id=<?php echo $character->getCharId(); ?>&sheet=1">Scheda</a></li>
		<li><a href="character_sheet.php?id=<?php echo $character->getCharId(); ?>&notes=all">Note</a></li>
		<? if($allow_items){ ?><li><a href="character_sheet.php?id=<?php echo $character->getCharId(); ?>&items=0">Oggetti</a></li><? } ?>
		<? if($allow_abilities){ ?><li><a href="character_sheet.php?id=<?php echo $character->getCharId(); ?>&ability=0&ability_action=nothing">Abilità</a></li><? } ?>
		<? if($modlevel>=$admin_view_quest_required && $allow_px){ ?><li><a href="character_sheet.php?id=<?php echo $character->getCharId(); ?>&quest=1">Quest</a></li><? } ?>
		<? if($modlevel>=$admin_view_ip_required){ ?><li><a href="character_sheet.php?id=<?php echo $character->getCharId(); ?>&logsIp=1">Logs</a></li><? } ?>
		<? if($_SESSION['char_id']==$character->getCharId()){ ?><li><a href="bank.php">Banca</a></li><? } ?>
		<? if($allowEdit){ ?><li><a href="character_sheet.php?id=<?php echo $character->getCharId(); ?>&config=1"><div style="width:16px;height:16px;background: url(images/icons/settings_16.png) no-repeat;"></div></a></li><? } ?>
	</ul>
</div>
</div>

</body>
</html>
