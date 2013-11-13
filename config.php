<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

//codifica in utf-8
	header('Content-type: text/html; charset=utf-8');


/* variabili connessione mysql */
		
	
	$ini_array = parse_ini_file('../iroleconfig.ini.php');
	$third_level_domain = explode(".",$_SERVER['HTTP_HOST']);

	mysql_connect($ini_array['db']['host'], $ini_array['db']['user'], $ini_array['db']['password']) or die(mysql_error());
	mysql_select_db("irole_".$third_level_domain[0]);
	mysql_query("SET CHARACTER SET utf8");

/* variabili di salvataggio files */

	$store_logs = "logs/";
	$store_avatar_img = "users/avatar/";
	$cache_dir = "cachedir/";
	
	require_once ("libs/settings_lib.php");
	
	//carico la configurazione dei settings dal db
	$LandSettingsList = new LandSettingsList();
	foreach ($LandSettingsList->getSettings() as $k => $v) {
		
		/*
		//write
		$varVal = ${$v->getVarName()};
		$kind = gettype($varVal);

		if($kind == "array"){
			$varVal = json_encode($varVal,JSON_UNESCAPED_UNICODE);
		}

		if($kind == "boolean" && !$varVal)
			$varVal=0;

		$v->setValue($varVal);
		*/

		//read
		${$v->getVarName()} = $v->getValue();
	}

	//force some values for not-dinamyc vars

	$url_land='http://'.$_SERVER['SERVER_NAME'];

	if ($closed != 0)
		header("Location: closed.php?c=$closed");

	// log GM commands, se true vengono memorizzati
	$log_GM_commands = true;
	
	// Nome del file di log per i comandi GM
	$log_gm_commands_file_name = "logs/gm_commands.txt";

	// compongo la matrice dei permessi
	$types_levels = array(0 => $acc_level_array, 1 => $master_level_array);

	// nome delle caratteristiche editabili all'iscrizione
	$n_car = 0;
	$name_car = array_flip($name_car);
	foreach ($name_car as $key => $value) {
		$value = $default_value_single_field_at_signup;
		$n_car++;
	}

	// nome delle caratteristiche non editabili all'iscrizione
	$n_carStatic = count($name_carStatic);
	$name_carStatic = array_flip($name_carStatic);


	if(!$allow_talents) $talents_onLevelup=0;

	if(!$allow_stats) {$allow_roll_stats=0;}

	if(!$allow_abilities){ $allow_roll_abilityes=0; $allow_roll_only_owned=true; }

	//true=bind to character, false=bind to account
	$bind_pm_char = 1;

	//millisec di interval tra i refresh
	$chat_refresh_rate = 5000;

	//numero di minuti in cui è possibile vedere i presenti
	$online_offset = 1;

	//numero di minuti in cui è possibile vedere i messaggi vecchi nella chat
	$chat_offset = 20;

	// directory in cui caricare le immagini dei gruppi e degli items
	$group_img_dir = "uploads/groups/";
	$items_img_dir = "uploads/items/";
	$maps_img_dir = "uploads/maps/";
	$rooms_img_dir = "uploads/roomthumb/";


	//carico la configurazione dei permessi utente dal db
	$UserRightsList = new UserRightsList();
	foreach ($UserRightsList->getRights() as $k => $v) {
		${$v -> getVarName()} = $v -> getValue();
	}
	
	// Oggetto lista dei campi estesi dell'avatar
	$avatar_fields = new ConfigCharXList();


/* Variabili e Funzioni di Portabilità */
/* NON MODIFICARE QUANTO SEGUE */
	
	date_default_timezone_set('Europe/Rome');
	
	// se nel php.ini non è abilitato il "magic_quotes_gpc", lo abilito run-time
	if (!get_magic_quotes_runtime()) {
		// funzione ricorsiva per l'aggiunta degli slashes ad un array
		function magicSlashes($element) {
			if (is_array($element))
				return array_map("magicSlashes", $element);
			else
				return addslashes($element);
		}
	
		// Aggiungo gli slashes a tutti i dati GET/POST/COOKIE
		if (isset($_GET) && count($_GET))
			$_GET = array_map("magicSlashes", $_GET);
		if (isset($_COOKIES) && count($_COOKIES))
			$_COOKIE = array_map("magicSlashes", $_COOKIE);
		if (isset($_REQUEST) && count($_REQUEST))
			$_REQUEST = array_map("magicSlashes", $_REQUEST);
	
		// la post è disabilitata!!!!
	}
?>
