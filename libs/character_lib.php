<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("common.php");
require_once("group_lib.php");
require_once("logs_lib.php");
require_once("libs/pm_lib.php");
require_once("libs/ability_lib.php");
require_once("libs/item_lib.php");

if(!isset($_SESSION))
{
	session_start();
}

class Account {

	private $id;
	private $username;
	private $password;
	private $email;
	private $unlock_code;
	private $join_date;
	private $modlevel;
	private $modlevelname;

	private $acc_exists;

	public function __construct($row=null,$id=null,$username=null,$email=null){

		if (isset($row)){
			$this->populateClass($row);
		}elseif(isset($id)){
			$this->id=$id;
		}elseif(isset($username)){
			$this->username=$username;
		}elseif(isset($email)){
			$this->email=$email;
		}

	}

	private function populateClass($row){

		if (isset($row)){

			if (isset($row['id'])) $this->id=$row['id'];
			if (isset($row['username'])) $this->username=$row['username'];
			if (isset($row['password'])) $this->password=$row['password'];
			if (isset($row['clear_password'])){
				$this->password=$this->hashPassword($row['clear_password']);
			}
			if (isset($row['email'])) $this->email=$row['email'];
			if (isset($row['unlock_code'])) $this->unlock_code=$row['unlock_code'];
			if (isset($row['join_date'])){
				$this->join_date=$row['join_date'];
			}else{
				$this->join_date=date("YmdHis");
			}
			if (isset($row['modlevel'])){
				$this->modlevel=$row['modlevel'];
			}else{
				$this->modlevel=0;
			}
			if (isset($GLOBALS['acc_level_array'][$this->modlevel]))
				$this->modlevelname=$GLOBALS['acc_level_array'][$this->modlevel];
		}
	}

	public function hashPassword($clear_password){
		return md5($clear_password);
	}

	public function changePass($clear_password,$sendemail=false){

		if (isset($this->id)){
			$this->password=$this->hashPassword($clear_password);

			$query="UPDATE account SET
              password='{$this->password}'
              WHERE id='{$this->id}'";

			$result = mysql_query($query) or die(mysql_error());

			//se $sendemail è true, notifico al personaggio il cambio password
			if ($sendemail){

				if(!isset($this->email)){
					$this->parseFromDb();
				}
				$mail_body="La nuova password con cui accedere a {$GLOBALS['nome_land']} è: {$clear_password}<br /><br />Buon Divertimento!";
				if (@send_mail($this->email,"Notifica cambio password ".$GLOBALS['nome_land'],$mail_body)){
					echo "Email di notifica cambio password inviata all'utente.";
				}else{
					echo "Impossibile inviare all'utente l'email di notifica cambio password.";
				}
			}
		}
	}

	public function changeEmail($email){

		if (isset($this->id)){
			$this->email=$email;

			$query="UPDATE account SET
              email='{$this->email}'
              WHERE id='{$this->id}'";

			$result = mysql_query($query) or die(mysql_error());
		}
	}

	public function unlock(){

		if (isset($this->id)){
			$this->unlock_code='';

			$query="UPDATE account SET
              unlock_code='{$this->unlock_code}'
              WHERE id='{$this->id}'";

			$result = mysql_query($query) or die(mysql_error());
		}
	}

	public function setMod($level){

		if (isset($this->id)){

			$this->modlevel=$level;

			$query="UPDATE account SET
              modlevel='{$this->modlevel}'
              WHERE id='{$this->id}'";

			$result = mysql_query($query) or die(mysql_error());
		}
	}

	public function setUsername($username){

		if (isset($this->id)){

			$this->username=$username;

			$query="UPDATE account SET
              username='{$this->username}'
              WHERE id='{$this->id}'";

			$result = mysql_query($query) or die(mysql_error());
		}
	}

	public function writeToDb(){

		if (isset($this->id)){
			$query="UPDATE account SET
              username='{$this->username}',
              password='{$this->password}',
              email='{$this->email}',
              unlock_code='{$this->unlock_code}',
              join_date='{$this->join_date}',
              modlevel='{$this->modelevel}'
              WHERE id='{$this->id}'";
		}elseif(isset($this->username)){
			$query="INSERT INTO account SET
              username='{$this->username}',
              password='{$this->password}',
              email='{$this->email}',
              unlock_code='{$this->unlock_code}',
              join_date='{$this->join_date}',
              modlevel='{$this->modelevel}'";
		}

		if (isset($query)){

			$result = mysql_query($query) or die(mysql_error());
			if (!isset($this->id))
				$this->id=mysql_insert_id();
			return 0;
		}

		return -1; //ritorno -1 quando non son riuscito a scrivere

	}

	public function parseFromDb($login=false){

		$this->acc_exists=false;

		//estraggo i dati da db
		if (isset($this->id)){
			$sel=" WHERE a.id='{$this->id}'";
		}elseif (isset($this->username)){
			$sel=" WHERE a.username='{$this->username}'";
		}elseif(isset($this->email)){
			$sel=" WHERE a.email='{$this->email}'";
		}

		if ($login){
			$sel=" WHERE a.username='{$this->username}' AND a.password='{$this->password}'";
		}


		if (isset($sel)){
			$query="SELECT a.* FROM account a ".$sel;

			$result = mysql_query($query) or die(mysql_error());
			if (mysql_num_rows($result)>0){
				$row=mysql_fetch_array($result);
				$this->populateClass($row);
				$this->acc_exists=true;
			}
		}
	}

	public function checkExistance(){

		$this->acc_exists=false;

		if (isset($this->id)){
			$sel=" WHERE a.id='{$this->id}'";
		}elseif (isset($this->username)){
			$sel=" WHERE a.username='{$this->username}'";
		}

		if (isset($sel)){
			$query="SELECT a.username,a.id FROM `account` a ".$sel;
			$result = mysql_query($query) or die(mysql_error());

			if (mysql_num_rows($result)>0){
				$row=mysql_fetch_array($result);
				$this->populateClass($row);
				$this->acc_exists=true;
			}
		}
	}

	public function deleteAccount(){

		if(isset($this->id)){
			//carico la lista dei personaggi, e uno ad uno li cancello

			$this->parseFromDb();

			$charL=new CharacterList();
			$charL->readFromDb(null,$this->id);

			//scrivo il log
			$Log=new Log(null,null);
			$Log->newLog($_SESSION['id'], $this->id, "Cancellazione dell'account {$this->username} (id: {$this->id} )",2);


			foreach($charL->getChars() as $k=>$v){
				$v->realDeleteChar();
			}

			//infine cancello l'account e tutte le tabelle ad esso relative

			//logs_ip, chiave acc_id
			mysql_query("DELETE FROM `logs_ip` WHERE `acc_id`={$this->id}")
			or die ("Impossibile cancellare log degli ip.<br/>\n ERRORE:". mysql_error());
			echo "<br/>Log Ip dell'account cancellati. Righe interessate: ".mysql_affected_rows();

			//bans, chiave victim_accid
			mysql_query("DELETE FROM `bans` WHERE `victim_accid`={$this->id}")
			or die ("Impossibile cancellare Ban<br/>\n ERRORE:". mysql_error());
			echo "<br/>Storia dei ban cancellata. Righe interessate: ".mysql_affected_rows();

			// l'account vero e proprio
			mysql_query("DELETE FROM `account` WHERE `id`={$this->id}")
			or die ("Impossibile cancellare l'account.<br/>\n ERRORE:". mysql_error());
			echo "<br/>Account cancellato con successo.";

		}

	}

	public function getTotalEarnedXp($onSingleChar=0){
		if(isset($this->id)){

			if($onSingleChar>0){
				$sel=" AND c.id='{$onSingleChar}'";
			}else{
				$sel="";
			}

			$query="SELECT SUM(q.element_px) as totalXp FROM `account` a, `character` c, `quest_element` q
				WHERE c.account=a.id AND q.element_id=c.id
				AND a.id='{$this->id}'".$sel;

			$result=mysql_query($query);
			$row=mysql_fetch_array($result);

			if(isset($row['totalXp']) && $row['totalXp']>0)
				return $row['totalXp'];

			return 0;

		}
	}


	public function getId(){
		return $this->id;
	}
	public function getUsername(){
		return $this->username;
	}
	public function getPassword(){
		return $this->password;
	}
	public function getEmail(){
		return $this->email;
	}
	public function getJoinDate(){
		return $this->join_date;
	}
	public function getUnlockCode(){
		return $this->unlock_code;
	}
	public function getModLevel(){
		return $this->modlevel;
	}
	public function getModLevelName(){
		return $this->modlevelname;
	}
	public function exists(){
		return $this->acc_exists;
	}
	public function getEmailRecoveryCode(){
		return md5("!{$this->email}:{$this->password}?");
	}


}

class Xchar {

	private $id;
	private $character;
	private $type;
	private $type_name;
	private $text;
	private $edit_time;
	private $edit_by;

	public function __construct($row=null) {
		global $avatar_fields;

		$AvtFld=$avatar_fields->getCharXList();

		if (isset($row)){
			if (isset($row['id'])) $this->id=$row['id'];
			if (isset($row['character'])) $this->character=$row['character'];
			if (isset($row['type'])){
				$this->type=$row['type'];
				if (isset($AvtFld[$this->type]))
					$this->type_name=$AvtFld[$this->type]->getName();
			}
			if (isset($row['text'])) $this->text=$row['text'];
			if (isset($row['edit_time'])) $this->edit_time=$row['edit_time'];
			if (isset($row['edit_by'])) $this->edit_by=$row['edit_by'];
		}

	}

	public function writeToDb(){

		if (isset($this->character)){
			if(isset($this->id)){
				$query="UPDATE `character_extended` SET
              `type`='{$this->type}',
              `text`='{$this->text}',
              `edit_time`='".date("YmdHis")."',
              `edit_by`='".$_SESSION['char_id']."'
              WHERE `id`='{$this->id}'";
			}elseif(isset($this->character)){
				$query="INSERT INTO `character_extended`
                (`character`,`type`,`text`,`edit_time`,`edit_by`)
                VALUES
                ('{$this->character}','{$this->type}','{$this->text}','".date("YmdHis")."','".$_SESSION['char_id']."')";
			}

			if (isset($query)){
				$result = mysql_query($query) or die(mysql_error());
				if (!isset($this->id))
					$this->id=mysql_insert_id();
				return 0;
			}

		}
		return -1; //ritorno -1 se non posso scrivere su db poichè non è specificato il character
	}

	public function getTypeName(){
		return $this->type_name;
	}

	public function getType(){
		return $this->type;
	}

	public function getText(){
		return htmlentities($this->text,ENT_COMPAT,'UTF-8');
	}

	public function setText($val){
		$this->text=trim($val);
	}

	public function getEditBy(){
		return $this->edit_by;
	}

	public function getEditTime(){
		return $this->edit_time;
	}


}

class Character {

	// ATTRIBUTI

	private $char_id;
	private $char_name;
	private $sex;
	private $creation_date;
	private $location_time;
	private $current_location_name;
	private $current_location_id;
	private $level;
	private $master_level;
	private $money;
	private $px;
	private $px_extra;
	private $word_count;
	private $master_pts;
	private $stat_pts;
	private $talents;
	private $master_word_count;
	private $available;

	private $stats=array();
	private $avatar_OBJ=array();
	private $acc_OBJ;

	//contiene la lista dei gruppi joinati (tutti oggetti di classe group)
	private $groupList=array();
	// contiene la lista dei gruppi joinati di cui è admin
	private $groupAdmin=array();

	private $abilityList=array();

	private $char_exists; //vale true SOLO se è stato confermato dal database

	// METODI

	public function __construct($char_id=null,$char_name=null){ //mi limito a creare l'oggetto

		$this->char_exists=false;

		if (isset($char_id)) $this->char_id=$char_id;
		if (isset($char_name)) $this->char_name=$char_name;

	}

	public function __destruct(){
		// non ho nulla da fare
	}

	public function writeToDb(){

		if (isset($this->char_id)){
			$query="UPDATE `character` SET
              account='{$this->acc_OBJ->getId()}',
              name='{$this->char_name}',
              px='{$this->px}',
              level='{$this->level}',
              px_extra='{$this->px_extra}',
              word_count='{$this->word_count}',
              master_pts='{$this->master_pts}',
              master_word_count='{$this->master_word_count}',
              sex='{$this->sex}',
              stats='".implode(' ',$this->stats)."',
              creation_date='{$this->creation_date}',
              location_time='{$this->location_time}',
              master_level='{$this->master_level}',
              money='{$this->money}',
              location_current='{$this->current_location_id}',
              available='{$this->available}'
              WHERE id='{$this->id}'";
		}else {
			$query="INSERT INTO `character` SET
              account='{$this->acc_OBJ->getId()}',
              name='{$this->char_name}',
              px='{$this->px}',
              level='{$this->level}',
              px_extra='{$this->px_extra}',
              word_count='{$this->word_count}',
              master_pts='{$this->master_pts}',
              master_word_count='{$this->master_word_count}',
              sex='{$this->sex}',
              stats='".implode(' ',$this->stats)."',
              creation_date='{$this->creation_date}',
              location_time='{$this->location_time}',
              master_level='{$this->master_level}',
              money='{$this->money}',
              location_current='{$this->current_location_id}',
              available='{$this->available}'";
		}

		if (isset($query)){

			$result = mysql_query($query) or die(mysql_error());
			if (!isset($this->char_id))
				$this->char_id=mysql_insert_id();

			$this->char_exists=true;
			return 0;
		}

		return -1; //ritorno -1 quando non son riuscito a scrivere

	}
	
	public function deleteCharacter(){
		if(isset($this->char_id)){


			$this->available=0;


			$query="UPDATE `character` SET
					available=0
					WHERE id='{$this->char_id}'";
			mysql_query($query)
			or die ("Impossibile cancellare il personaggio.<br/>\n ERRORE:". mysql_error());
				echo "<br/>Personaggio cancellato con successo.";
				
			$Log=new Log(null,null);
			$Log->newLog($_SESSION['id'], $this->char_id, "Cancellazione del personaggio {$this->char_name} (id: {$this->char_id} )",1);
			
			
			//room_private. campo char_id (per ora no)
			mysql_query("DELETE FROM `room_private` WHERE `char_id`={$this->char_id}")
			or die ("Impossibile cancellare riferimento a stanze private.<br/>\n ERRORE:". mysql_error());
			echo "<br/>Permessi di accesso a stanze riservate cancellati. Righe interessate: ".mysql_affected_rows();
			
			//groups_joined. campo char_id
			mysql_query("DELETE FROM `groups_joined` WHERE `char_id`={$this->char_id}")
			or die ("Impossibile cancellare riferimento ai gruppi.<br/>\n ERRORE:". mysql_error());
			echo "<br/>Gruppi del personaggio cancellati. Righe interessate: ".mysql_affected_rows();
				
			
			
			$this->setCharname("~OLD_{$this->char_id}~".$this->char_name);



		}
	}
	

	public function realDeleteChar(){

		if (isset($this->char_id)){

			$this->checkExistance();

			if ($this->exists()){

				//per prima cosa scrivo i log, così anche se l'azione dovesse fallire parzialmente, risalgo a chi l'ha fatta
				$Log=new Log(null,null);
				$Log->newLog($_SESSION['id'], $this->char_id, "Cancellazione REALE del personaggio {$this->char_name} (id: {$this->char_id} )",1);

				//cancello le varie entry nelle tabelle collegate ai character

				//room_private. campo char_id (per ora no)
				mysql_query("DELETE FROM `room_private` WHERE `char_id`={$this->char_id}")
				or die ("Impossibile cancellare riferimento a stanze private.<br/>\n ERRORE:". mysql_error());
				echo "<br/>Permessi di accesso a stanze riservate cancellati. Righe interessate: ".mysql_affected_rows();
				
				//groups_joined. campo char_id
				mysql_query("DELETE FROM `groups_joined` WHERE `char_id`={$this->char_id}")
				or die ("Impossibile cancellare riferimento ai gruppi.<br/>\n ERRORE:". mysql_error());
				echo "<br/>Gruppi del personaggio cancellati. Righe interessate: ".mysql_affected_rows();

				//character_extended. campo character
				mysql_query("DELETE FROM `character_extended` WHERE `character`={$this->char_id}")
				or die ("Impossibile cancellare campi estesi.<br/>\n ERRORE:". mysql_error());
				echo "<br/>Campi estesi cancellati. Righe interessate: ".mysql_affected_rows();

				//abilities_buyed. campo char_id
				mysql_query("DELETE FROM `abilities_buyed` WHERE `char_id`={$this->char_id}")
				or die ("Impossibile cancellare riferimento alle abilità.<br/>\n ERRORE:". mysql_error());
				echo "<br/>Abilità del personaggio cancellate. Righe interessate: ".mysql_affected_rows();

				//quest_element. campo element_id
				mysql_query("DELETE FROM `quest_element` WHERE `element_id`={$this->char_id}")
				or die ("Impossibile cancellare riferimento alle quest.<br/>\n ERRORE:". mysql_error());
				echo "<br/>Quest del personaggio cancellate. Righe interessate: ".mysql_affected_rows();

				//items_buyed. campo buyer (per ora no)
				mysql_query("DELETE FROM `items_buyed` WHERE `char_id`={$this->char_id}")
				or die ("Impossibile cancellare riferimento agli oggetti.<br/>\n Errore:". mysql_error());
				echo "<br/>Oggetti del personaggio cancellati. Righe interessate: ".mysql_affected_rows();

				//imposto il seller degli oggetti da lui venduti ad un valore inesistente (-1)
				//mysql_query("UPDATE `items_buyed` SET `seller`='-1' WHERE `buyer`={$this->char_id}")
				//        or die ("Impossibile aggiornare gli oggetti venduti dal personaggio.<br/>\n ERRORE:". mysql_error());

				//devo fare lo stesso anche per i messaggi
				//mysql_query("UPDATE `private_message` SET `sender`='-1' WHERE `sender`={$this->char_id}")
				//        or die ("Impossibile aggiornare i messaggi privati spediti dal personaggio.<br/>\n ERRORE:". mysql_error());
				//mysql_query("UPDATE `private_message` SET `recipient`='-1' WHERE `recipient`={$this->char_id}")
				//        or die ("Impossibile aggiornare i messaggi privati ricevuti dal personaggio.<br/>\n ERRORE:". mysql_error());

				//e infine cancello il personaggio
				mysql_query("DELETE FROM `character` WHERE `id`={$this->char_id}")
				or die ("Impossibile cancellare il personaggio.<br/>\n ERRORE:". mysql_error());
				echo "<br/>Personaggio cancellato con successo.";

			}else{
				echo "<br/>Personaggio inesistente, impossibile cancellarlo.";
			}
		}

	}

	public function populateClass($row=null,$resultx=null){ //dato l'array row popola gli attributi della classe

		if (isset($row)){

			//l'array contenente i dati relativi all'account
			$arraytoacc=array();

			if (isset($row['account'])){
				$arraytoacc['id']=$row['account'];
			}
			if (isset($row['email'])){
				$arraytoacc['email']=$row['email'];
			}
			if (isset($row['username'])){
				$arraytoacc['username']=$row['username'];
			}
			if (isset($row['modlevel'])){
				$arraytoacc['modlevel'] = $row['modlevel'];
			}
			if (isset($row['join_date'])){
				$arraytoacc['join_date'] = $row['join_date'];
			}
			if (isset($row['password'])){
				$arraytoacc['password'] = $row['password'];
			}

			if (isset($row['id'])) $this->char_id = $row['id'];
			if (isset($row['name'])) $this->char_name = $row['name'];
			if (isset($row['px'])){
				$this->px = $row['px'];
			}else{
				$this->px = 0;
			}
			if (isset($row['available'])){
				$this->available = $row['available'];
			}else{
				$this->available = 1;
			}
			if (isset($row['px_extra'])){
				$this->px_extra = $row['px_extra'];
			}else{
				$this->px_extra = 0;
			}
			if (isset($row['word_count'])){
				$this->word_count = $row['word_count'];
			}else{
				$this->word_count = 0;
			}
			if (isset($row['master_pts'])){
				$this->master_pts = $row['master_pts'];
			}else{
				$this->master_pts = 0;
			}
			if (isset($row['stat_pts'])){
				$this->stat_pts = $row['stat_pts'];
			}else{
				$this->stat_pts = 0;
			}
			if (isset($row['level'])){
				$this->level = $row['level'];
			}else {$this->level=0;}

			if (isset($row['talents'])){
				$this->talents = $row['talents'];
			}
			if (isset($row['master_word_count'])){
				$this->master_word_count = $row['master_word_count'];
			}else{
				$this->master_word_count = 0;
			}
			if (isset($row['sex'])) $this->sex = $row['sex'];
			if (isset($row['creation_date'])){
				$this->creation_date = $row['creation_date'];
			}else{
				$this->creation_date = date("YmdHis");
			}
			if (isset($row['location_time'])) $this->location_time = $row['location_time'];
			if (isset($row['master_level'])){
				$this->master_level = $row['master_level'];
			}else{
				$this->master_level = 0;
			}
			if (isset($row['money'])) $this->money = $row['money'];
			if (isset($row['roomname'])) $this->current_location_name = $row['roomname'];
			if (isset($row['location_current'])){
				$this->current_location_id = $row['location_current'];
			}else{
				$this->current_location_id = 0;
			}

			//creo l'oggetto account associato al character, passandogli i dati che già ho preso
			$this->acc_OBJ = new Account($arraytoacc);

			//inserisco le stats nella classe in base a quante (e quali) ne ho definite nel file config.php
			if (isset($row['stats'])){

				$statsarray=explode(" ",$row['stats']);
				$i=0;
				foreach ($GLOBALS['name_carStatic'] as $key => $value){
					$this->stats[$key]=$statsarray[$i];
					$i++;
				}
				foreach ($GLOBALS['name_car'] as $key => $value){
					$this->stats[$key]=$statsarray[$i];
					$i++;
				}

			}

			//creo sempre un array di avatar

			//$charXL=new ConfigCharXList();
			global $avatar_fields;

			foreach($avatar_fields->getCharXList() as $k=>$v){

				//$v=new ConfigCharX();
				$arraytoavt=array();
				$arraytoavt['character']=$this->char_id;
				$arraytoavt['type']=$k;
				$arraytoavt['text']='';
				$temp=new Xchar($arraytoavt);

				$this->avatar_OBJ[$k]=$temp;

			}

		}


		if (isset($resultx)){ //popolo i campi avatar

			while($rowx=mysql_fetch_array($resultx)){
				$temp=new Xchar($rowx);
				$this->avatar_OBJ[$temp->getType()]= $temp;
			}
		}

		return;
	}

	public function setStats($statsArr){
		if(isset($this->char_id)){

			foreach ($statsArr as $key => $value){

				$chiave=$key;
				$valore=$value;
				$oldvalore=$this->stats[$key];

				if (array_key_exists($key, $this->stats) )
					$this->stats[$key]=$value;
			}

			$query="UPDATE `character` SET
              stats='".implode(' ',$this->stats)."'
              WHERE id='{$this->char_id}'";

			$result = mysql_query($query) or die(mysql_error());

			$Log= new Log(null,null);
			$Log->newLog($_SESSION['id'], $this->char_id, "Forzata stat $chiave a $valore (prima valeva $oldvalore)", 5);


			return true;

		}
		return false;
	}

	public function addStatPt($stat,$pt=1){

		$pt=abs(intval($pt));

		if(!isset($this->char_id))
			exit("empty id");


		//controllo che l'utente abbia abbastanza punti
		if(!$this->stat_pts>=$pt)
			return false;

		//prendo il valore corrente
		if(!array_key_exists($stat, $this->stats))
			return false;

		$this->stat_pts-=$pt;
		$this->stats[$stat]+=$pt;

		$query="UPDATE `character` SET
			  stat_pts='{$this->stat_pts}',
              stats='".implode(' ',$this->stats)."'
              WHERE id='{$this->char_id}'";

		mysql_query($query) or die(mysql_error());

		return $this->stats[$stat];


	}

	public function setMoney($newMoney){
		if(isset($this->char_id)){

			$oldvalore=$this->money;

			$this->money=$newMoney;

			$query="UPDATE `character` SET
              money='$newMoney'
              WHERE id='{$this->char_id}'";

			$result = mysql_query($query) or die(mysql_error());

			$Log= new Log(null,null);
			$Log->newLog($_SESSION['id'], $this->char_id, "Forzati $newMoney ".$GLOBALS['valuta_plurale']." (prima erano $oldvalore)", 5);


			return true;

		}
		return false;
	}

	public function parseFromDb($loadAvatar=null,$onlyAccount=false){

		//estraggo i dati da db
		if (isset($this->char_id)){
			$sel=" WHERE c.id='{$this->char_id}'";
		}elseif (isset($this->char_name)){
			$sel=" WHERE c.name='{$this->char_name}'";
		}

		$tabs="";
		if ($onlyAccount==false)
			$tabs="c.*,";

		if (isset($sel)){
			$query="SELECT {$tabs}a.username,a.modlevel,a.email,a.password,r.name AS roomname
              FROM `character` c
              LEFT JOIN account a
              ON c.account=a.id
              LEFT OUTER JOIN room r
              ON c.location_current=r.id
              ".$sel;

			$result = mysql_query($query) or die(mysql_error());
			if (mysql_num_rows($result)>0){
				$row=mysql_fetch_array($result);
				$this->populateClass($row);
				$this->char_exists=true;
			}
		}

		//se $loadavatar è true leggo anche i campi della tabella character_extended
		if (isset($loadAvatar) && $loadAvatar==true && $this->char_exists==true){
			$query="SELECT x.* FROM character_extended x
              WHERE x.`character`='{$this->char_id}'";

			$result = mysql_query($query) or die(mysql_error());

			if (mysql_num_rows($result)>0)
				$this->populateClass(null,$result);

		}
		return;
	}

	public function writeLocation($locationId=null){

		if (isset($this->char_id)){
			$sel=" WHERE c.id='{$this->char_id}'";
		}elseif (isset($this->char_name)){
			$sel=" WHERE c.name='{$this->char_name}'";
		}

		if (isset($locationId)){
			$insertLocation="c.location_current='$locationId',";
			$_SESSION['room_id']=$locationId;
		}else{
			$insertLocation="";
		}


		if (isset($sel)){
			$time=date("YmdHis");
			$query="UPDATE `character` AS c SET
              $insertLocation c.location_time='$time', c.online='1'
              ".$sel;

			mysql_query($query) or die(mysql_error());

			if (mysql_affected_rows()>0)
				$this->char_exists=true;

		}
	}

	public function setOffline(){ //setta come offline il pg che ha effettuato il logoff

		if (isset($this->char_id)){
			$sel=" WHERE c.id='{$this->char_id}'";
		}elseif (isset($this->char_name)){
			$sel=" WHERE c.name='{$this->char_name}'";
		}

		if (isset($sel)){
			$query="UPDATE `character` AS c SET c.online='0'".$sel;
			mysql_query($query) or die(mysql_error());
			if (mysql_affected_rows()>0)
				$this->char_exists=true;

		}
	}

	public function setAvatarX($key,$value){
	
		$this->avatar_OBJ[$key]->setText($value);
		$this->avatar_OBJ[$key]->writeToDb();

		return $this->avatar_OBJ[$key]->getText();
	}

	public function checkExistance(){

		if (isset($this->char_id)){
			$sel=" WHERE c.id='{$this->char_id}'";
		}elseif (isset($this->char_name)){
			$sel=" WHERE c.name='{$this->char_name}'";
		}

		if (isset($sel)){
			$query="SELECT c.name,c.id FROM `character` c ".$sel;
			$result = mysql_query($query) or die(mysql_error());

			if (mysql_num_rows($result)>0){
				$row=mysql_fetch_array($result);
				$this->populateClass($row);
				$this->char_exists=true;
			}
		}
	}

	public function addTalents($pts,$writeLog=false){
		if(isset($this->char_id)){

			$oldTal=$this->talents;
			$this->talents+=intval($pts);


			$query="UPDATE `character` SET
					talents=talents+{$pts}
					WHERE id='{$this->char_id}'";
			mysql_query($query) or die(mysql_error());
			
			//infine scrivo i log
			if($writeLog){
				$Log= new Log(null,null);
				$Log->newLog($_SESSION['id'], $this->char_id, "Forzati {$this->talents} Talenti (prima erano $oldTal)", 5);
			}


		}
	}

	public function spendPx($px){
		if(isset($this->char_id)){


			$this->px+=intval($px);


			$query="UPDATE `character` SET
					px=px+{$px}
					WHERE id='{$this->char_id}'";
			mysql_query($query) or die(mysql_error());


		}

	}

	/*
	   * Imposta e scrive su db la cifra vecchia sommata a quella passata al metodo
	   */
	public function addPx($px,$writeLog=false){
		if(isset($this->char_id)){

			$oldpx=$this->px;
			$Lv=new Levels();
			$this->px+=intval($px);
			$LvDiff=($Lv->getLv($this->px))-($Lv->getLv($oldpx));
			$Add_talents=$GLOBALS['talents_onLevelup']*$LvDiff;
			$Add_statpts=$GLOBALS['stats_onLevelup']*$LvDiff;


			$query="UPDATE `character` SET
					px=px+{$px},
					talents=talents+{$Add_talents},
					stat_pts=stat_pts+{$Add_statpts},
					level=level+{$LvDiff}
					WHERE id='{$this->char_id}'";
			mysql_query($query) or die(mysql_error());

			//infine scrivo i log
			if($writeLog){
				$Log= new Log(null,null);
				$Log->newLog($_SESSION['id'], $this->char_id, "Forzati {$this->px} px (prima erano $oldpx)", 5);
			}

		}
	}

	public function addWordCount($num,$type=0){
		if(isset($this->char_id)){

			switch ($type) {
				case 0: // testo standard

					$this->word_count+=intval($num);
					$refer_to=$this->word_count;
					$typeXp=$GLOBALS['giveExp_every'];
					$amount=$GLOBALS['giveExp_amount'];
					$destfield='px_extra';
					$source="word_count";

					$merge=$GLOBALS['giveExp_mergexp'];

					break;

				case 1: //testo master

					$this->master_count+=intval($num);
					$refer_to=$this->master_count;
					$typeXp=$GLOBALS['master_pts_wordcount'];
					$amount=$GLOBALS['master_pts_wordcount_amount'];
					$destfield='master_pts';
					$source="master_word_count";

					$merge=$GLOBALS['master_pts_mergexp'];

					break;

				default:
					return false;
					break;
			}


			if($refer_to >= $typeXp && $typeXp>0){

				$molt=floor($refer_to/$typeXp);
				$refer_to=$refer_to%$typeXp;

				$addExtraPx=$molt*$amount;

				$query="UPDATE `character` SET {$destfield}={$destfield}+{$addExtraPx} WHERE id='{$this->char_id}'";
				mysql_query($query) or die(mysql_error());

				if ($merge && $type==0){ //se merge è true, aggiungo i punti anche a quelli standard
					
					$this->addPx($addExtraPx);
					
				}


			}

			$query="UPDATE `character` SET {$source}={$refer_to} WHERE id='{$this->char_id}'";
			mysql_query($query) or die(mysql_error());



		}

	}

	/*
	   * Imposta e scrive su db la cifra vecchia sommata a quella passata al metodo
	   */
	public function addMoney($money){
		if(isset($this->char_id)){
			$this->money+=intval($money);
			$query="UPDATE `character` SET money=money+{$money} WHERE id='{$this->char_id}'";
			mysql_query($query) or die(mysql_error());
		}
	}

	public function moveMoney($money,$dstCharObj,$writeLog=true,$notifyPM=false){
		if(isset($this->char_id)){

			$this->addMoney(-$money);
			$dstCharObj->addMoney($money);

			//infine scrivo i log
			if($writeLog){
				$Log= new Log(null,null);
				$Log->newLog($this->char_id, $dstCharObj->getCharId(), "Trasferimento $money {$GLOBALS['valuta_plurale']}", 3);
			}

			//e mando un PM al ricevente
			if($notifyPM){
				$notifyPMtxt="Messaggio generato automaticamente\r\n Hai ricevuto un accredito di $money {$GLOBALS['valuta_plurale']}";
				$notifyPM=new PrivateMessage(null,null);
				$notifyPM->sendNew($this->char_id, $dstCharObj->getCharName(), "Accredito {$GLOBALS['valuta_plurale']}", $notifyPMtxt, 0);
			}

			return true;
		}
	}

	public function isEligibleSalaryWithdraw($groupElementId){

		if(isset($this->char_id)){

			$money=-1;
			//controllo che il pg abbia la carica groupElement
			foreach ($this->getGroups() as $k=>$v){
				if($v->getId()==$groupElementId) $money=$v->getSalary()*$GLOBALS['salary_cooldown'];
			}

			if($money<0)
				return -1;

			//controllo l'ultimo prelievo
			$loglist=new LogList();
			$loglist->readFromDb($groupElementId,$this->char_id,1,4,true);

			$lastWithdraw=0;
			foreach($loglist->getLogs() as $k=>$v){
				$lastWithdraw=$v->getDate();
			}

			//echo "ultimo: $lastWithdraw";
			//ora che so quando è avvenuto l'ultimo prelievo controllo se è valido
			$lastWithdraw=strtotime($lastWithdraw);
			

			$dateCompare=date("YmdHis");
			if($GLOBALS['salary_unlockat']==-1){ //se è -1 controllo l'ora esatta

				$dateNextWithdraw=date("YmdHis",strtotime("+{$GLOBALS['salary_cooldown']} day",$lastWithdraw));

			}else{

				$arr_xpld=explode(":",$GLOBALS['salary_unlockat']);
				$dateNextWithdraw=date("YmdHis",mktime($arr_xpld[0], $arr_xpld[1], $arr_xpld[2], date("m",$lastWithdraw), date("d",$lastWithdraw)+$GLOBALS['salary_cooldown'], date("Y",$lastWithdraw)));

			}

			if( $dateNextWithdraw < $dateCompare )
				return $money;

		}

		return -1;
	}

	public function salaryWithdraw($groupElementId){

		//se sono idoneo al ritiro, per il dato gruppo, procedo
		$money=$this->isEligibleSalaryWithdraw($groupElementId);
		if($money>=0){

			$this->addMoney($money);
			$Log= new Log(null,null);
			$Log->newLog($groupElementId, $this->char_id, "Ritirato salario di $money {$GLOBALS['valuta_plurale']} (id carica: $groupElementId)", 4);

			return true;
		}

		return false;

	}

	/* popola una lista di oggetti group element */
	public function readGroups($type=null){

		$listObj= new JoinedGroups($this->char_id,$type);
		$this->groupList=$listObj->getGroups();
	}

	public function setMaster($lv){
		if(isset($this->char_id)){
			$this->master_level=intval($lv);
			$query="UPDATE `character` SET master_level={$this->master_level} WHERE id='{$this->char_id}'";
			mysql_query($query) or die(mysql_error());
		}
	}

	public function setCharname($char){
		if(isset($this->char_id)){
			$this->char_name=$char;
			$query="UPDATE `character` SET name='{$this->char_name}' WHERE id='{$this->char_id}'";
			mysql_query($query) or die(mysql_error());
		}
	}

	public function readAbilities(){
		$listObj= new BuyedAbilityList($this->char_id);
		$this->abilityList=$listObj->getAbilities(true);
	}

	public function readGroupsAdmin($type=null){
		$listObj= new JoinedGroups($this->char_id,$type,true,true);
		$this->groupAdmin=$listObj->getGroups();
	}

	public function addGroup($key,$obj){

		$this->groupList[$key]=$obj;

	}

	public function isGroupAdmin($groupId){

		foreach($this->groupAdmin as $key => $value){
			if ($value->getGroup()!=$groupId)
				continue;
			if ($value->getAdmin()==1)
				return true;
		}

		return false;

	}

	public function getCharId(){
		return $this->char_id;
	}

	public function getCharName(){
		
			
		if(substr($this->char_name, 0,1)=='~'){
			$arr=explode('~', $this->char_name);
			return $arr[sizeof($arr)-1];
			
		}	
			
		return $this->char_name;
	}

	public function getPx(){
		return $this->px;
	}

	public function getExtraPx(){
		return $this->px_extra;
	}
	public function getMasterPts(){
		return $this->master_pts;
	}
	public function getLevel(){
		return $this->level;
	}
	public function getTalents(){
		return $this->talents;
	}
	public function getCharPx(){
		return $this->stat_pts;
	}
	public function getAvailable(){
		return $this->available;
	}
	public function getCharNameLink($showName=true,$showother=false){

		if($showother!=false)
			$showL=$showother;
		else
			$showL=$this->getCharName();


		if($showName)
			$showName=$showL;
		else
			$showName='';
		
		if(!empty($this->char_id)){
			$link="id={$this->char_id}";
		}else{
			$link="name={$this->char_name}";
		}

		return '<a href="character_sheet.php?'.$link.'" class="popUp" target="iavatar" >'.$showName.'</a>';
	}

	public function getSex(){
		return $this->sex;
	}

	/**
	 * restituisce la stringa con la data di creazione del personaggio
	 */
	public function getDate(){
		return $this->creation_date;
	}

	/**
	 * restituisce l'id della locazione attuale
	 */
	public function getLocationId(){
		return $this->current_location_id;
	}

	/**
	 * restituisce il nome della locazione attuale
	 */
	public function getLocationName(){
		return $this->current_location_name;
	}

	/**
	 * restituisce l'ultima ora in cui il character è stato avvistato
	 */
	public function getLocationTime(){
		return $this->location_time;
	}

	public function getMasterLevel(){
		return $this->master_level;
	}

	public function getMoney(){
		return $this->money;
	}

	/**
	 * restituisce l'array con tutte le stats
	 */
	public function getStats($bonuses=false){
							
		//se bonuses=true carico anche i bonus
		
		if($bonuses){
			
			$stats_with_bonus=$this->stats;	
				
			//leggo le abilità	
			$ablList = new BuyedAbilityList($this->char_id);
			$bonusAB = $ablList->getTotalStatsBonus();
			
			//leggo gli oggetti
			$itmList = new BuyedItemList($this->char_id);
			if($GLOBALS['allowEquipItems']){
				$bonusITM = $itmList->getEquippedBonus();
			}else{
				$bonusITM = $itmList->getTotalBonus();
			}
			
			//prendo i bonus
			foreach ($stats_with_bonus as $key => $value) {
					
				if($GLOBALS['abilitiesImproveStats'] && array_key_exists($key, $bonusAB)){
					$stats_with_bonus[$key]+=$bonusAB[$key];
				}
				
				if($GLOBALS['itemsImproveStats'] && array_key_exists($key, $bonusITM)){
					$stats_with_bonus[$key]+=$bonusITM[$key];	
				}
					
				
			}
			
			
			return $stats_with_bonus;	
				
			
		}				
					
				
			
		return $this->stats;
	}

	/**
	 * restituisce un array di oggetti Avatar (background, amicizie,immagine, etc)
	 */
	public function Avatar(){
		return $this->avatar_OBJ;
	}

	/*
	   * restituisce un oggetto Account
	   */
	public function Account(){

		if(empty($this->acc_OBJ))
			$this->parseFromDb(null,true);

		return $this->acc_OBJ;
	}

	/**
	 * restituisce true se l'esistenza del personaggio è stata verificata su db
	 */
	public function exists(){
		return $this->char_exists;
	}

	/**
	 * restituisce un array di oggetti GroupElement a cui il personaggio fa parte
	 */
	public function getGroups(){
		if(empty($this->groupList))
			$this->readGroups();
		return $this->groupList;
	}

	/*
	   * Verifica che il pg faccia parte del gruppo con id=$id
	   */
	public function inGroup($id){
		foreach($this->getGroups() as $k=>$v){
			//echo "__".$v->getId();
			if($v->getGroup()==$id)
				return true;
		}
		return false;
	}

	/**
	 * restituisce un array di oggetti BuyedAbility che il personaggio possiede
	 */
	public function getAbilities(){
		//  echo "cerco";
		if(empty($this->AbilityList))
			$this->readAbilities();
		return $this->abilityList;
	}

}

/**
 *
 */
class CharacterList {

	private $chars_array=array();
	private $count_char;
	private $count_avail;

	function __construct() {
		$this->count_char=0;
	}

	public function readFromDb($stringMatch=null,$accIdMatch=null,$accNameMatch=null,$lastCreated=null){

		if(isset($stringMatch)){
			$sel=" WHERE c.name LIKE '{$stringMatch}%' ORDER BY c.name";
		}

		if(isset($accNameMatch)){
			$sel=" WHERE a.username LIKE '{$accNameMatch}%' ORDER BY a.username,c.name";
		}

		if(isset($accIdMatch) && $accIdMatch>0){
			$sel=" WHERE c.account='{$accIdMatch}'";
		}

		if(isset($lastCreated) && $lastCreated>0){
			$sel=" ORDER BY c.id DESC LIMIT $lastCreated";
		}


		$this->count_char=0;
		$this->count_avail=0;

		$query="SELECT c.*,a.username,a.modlevel,a.email,a.join_date,r.name AS roomname
              FROM `character` c
              LEFT JOIN account a
              ON c.account=a.id
              LEFT OUTER JOIN room r
              ON c.location_current=r.id".$sel;

		$result=mysql_query($query) or die (mysql_error());

		while($row=mysql_fetch_array($result)){

			$tmpChar=new Character();
			$tmpChar->populateClass($row);
			if($tmpChar->getAvailable()==1)
				$this->count_avail++;
			$this->chars_array[]=$tmpChar;
			$this->count_char++;
		}

	}


	public function getChars($available=true){
			
		if($available){
			$tmparr=array();
			
			foreach ($this->chars_array as $key => $value) {
				
				if($value->getAvailable()==1)
					$tmparr[]=$value;
				
				
			}	
			
			return $tmparr;
			
		}	
			
		return $this->chars_array;
	}

	public function CountChars($available=true){
		
		if($available)
			return $this->count_avail;	
			
		return  $this->count_char;
	}

}


/**
 *
 */
class Levels {

	private $levels;
	private $const_add=200;

	function __construct() {
			
		if(!empty($GLOBALS['levelup_array'])){
			$this->levels=$GLOBALS['levelup_array'];
		}else { 
	
			$this->levels=array(
				0 => 0,
				1 => 30,
				2 => 70,
				3 => 150,
				4 => 310
			);
		}

	}

	function getLv($curExp){

		$counting=TRUE;
		$i=0;
		$th=0;


		while($counting){


			if(isset($this->levels[$i])){
				$th=$this->levels[$i];
			}else{
					
				$th+=$this->const_add;
			}

			if($curExp<$th) //mi fermo
				break;

			$i++;

		}

		return $i;


	}
}

?> 
