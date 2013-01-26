<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once ("common.php");
require_once ("character_lib.php");
require_once ("logs_lib.php");
require_once ("ability_lib.php");

/**
 *
 */
class GroupElement {

	private $id;
	private $id_group;
	private $element_name;
	private $element_image;
	private $group_admin;
	private $create_date;
	private $default;
	private $salary;

	private $groupType;

	public function __construct($row = null, $id = null) {

		if (isset($id)) {
			$this -> id = $id;
		}

		if (isset($row)) {
			$this -> parse($row);
		}

	}

	private function parse($row) {

		if (isset($row)) {

			if (isset($row['id']))
				$this -> id = $row['id'];
			if (isset($row['id_group']))
				$this -> id_group = $row['id_group'];
			if (isset($row['element_name']))
				$this -> element_name = $row['element_name'];
			if (isset($row['element_image']) && $row['element_image'] != '') {
				$this -> element_image = $row['element_image'];
			}
			if (isset($row['group_admin']))
				$this -> group_admin = $row['group_admin'];
			if (isset($row['create_date'])) {
				$this -> create_date = $row['create_date'];
			} else {
				$this -> create_date = date("YmdHis");
			}
			if (isset($row['default'])) {
				$this -> default = $row['default'];
			} else {
				$this -> default = 0;
			}
			if (isset($row['salary'])) {
				$this -> salary = intval($row['salary']);
				if (!$this -> salary > 0)
					$this -> salary = 0;
			}

			if (isset($row['grouptype']))
				$this -> groupType = $row['grouptype'];

		}
	}

	public function writeToDb() {

		if (isset($this -> id)) {//l'id è settato, faccio l'update
			$query = "UPDATE groups_element SET
              element_name='{$this->element_name}',
              element_image='{$this->element_image}',
              create_date='{$this->create_date}',
              id_group='{$this->id_group}',
              group_admin='{$this->group_admin}',
              
              salary='{$this->salary}'
              WHERE id='{$this->id}'";
		} else {
			$query = "INSERT INTO groups_element SET
              element_name='{$this->element_name}',
              element_image='{$this->element_image}',
              create_date='{$this->create_date}',
              id_group='{$this->id_group}',
              group_admin='{$this->group_admin}',
              `default`='{$this->default}',
              salary='{$this->salary}'";
		}

		if (isset($query)) {
			$result = mysql_query($query) or die(mysql_error());
			if (!isset($this -> id))
				$this -> id = mysql_insert_id();
			return 0;
		}

		return -1;
		//ritorno -1 quando non son riuscito a scrivere
	}

	public function storeImage($img, $ext) {

		if (isset($this -> id) && isset($img) && $img != '') {
			$upload = new Upload($img, $GLOBALS['group_img_dir'], $this -> id_group . "_" . $this -> id . "." . $ext);
			$this -> element_image = $GLOBALS['group_img_dir'] . $upload -> GetFileName();
			@unlink($this -> element_image);
			$upload -> UploadFile();
			$this -> writeToDb();
		}

	}

	public function JoinGroup($charObj) {
		if (isset($this -> id)) {

			//$charObj= new Character();
			$charid = $charObj -> getCharId();

			if (!$GLOBALS['allow_multigroup_sametype']) {
				//controllo che l'utente non sia gruppato in gruppi dello stesso tipo
				foreach ($charObj->getGroups() as $k => $v) {
					//$v=new GroupElement();
					if ($v -> getGroupType() == $this -> groupType && $v -> getGroup() != $this -> id_group)
						return false;
				}
			}

			$curdate = date("YmdHis");
			$query = "INSERT INTO groups_joined SET
              char_id='{$charid}',
              group_id='{$this->id}',
              joindate='{$curdate}',
              active=1";

			$query2 = "UPDATE groups_joined SET
              active=0
              WHERE group_id IN (SELECT id from groups_element WHERE id_group='{$this->id_group}') AND char_id='{$charid}'";

			mysql_query($query2) or die(mysql_error());
			mysql_query($query) or die(mysql_error());
			
			//$charObj=new Character();
			$charObj->parseFromDb();
			$charObj->readGroups();
			
			//controllo quali abilità vengono automaticamente assegnate a questo elemento
			$abList=new AbilityList();
			$abList->populateList(null,$this->id);
			foreach($abList->getList() as $k=>$v){
				//$v=new Ability();
				if($v->isBuyable($charObj))
					$v->BuyAbility($charObj,true);
				
			}

			return true;
		}
	}

	public function LeaveGroup($joinid) {

		if (isset($this -> id)) {

			$curdate = date("YmdHis");
			$query = "UPDATE groups_joined SET
              leavedate='{$curdate}',
              active=0
              WHERE group_id IN (SELECT id from groups_element WHERE id_group='{$this->id_group}')
              AND char_id='{$joinid}'";

			mysql_query($query) or die(mysql_error());
			return true;
		}

		return false;

	}

	public function getId() {
		return $this -> id;
	}

	public function getName() {
		return $this -> element_name;
	}

	public function getAdmin() {
		return $this -> group_admin;
	}

	public function getDefault() {
		return $this -> default;
	}

	public function getImage($tag = false) {

		if ($tag && strlen($this -> element_image) > 4)
			return "<img src=\"{$this->element_image}\" alt=\"{$this->element_name}\" />";

		return $this -> element_image;
	}

	public function getGroup() {
		return $this -> id_group;
	}

	public function getSalary() {
		return $this -> salary;
	}

	public function getGroupType() {
		return $this -> groupType;
	}

}

/**
 *
 */
class Group {

	private $id;
	private $name;
	private $typeG;
	private $typeName;
	private $create_date;
	private $logo;
	private $website;
	private $statute;

	private $group_elements = array();

	private $group_members = array();
	//una lista di oggetti GroupMember
	private $total_members;

	public function __construct($row = null, $id = null) {

		$this -> total_members = 0;

		if (isset($row)) {

			$this -> parse($row);

		}
		if (isset($id)) {//lo leggo da db

			$this -> id = $id;

		}

	}

	public function parse($row) {

		if (isset($row)) {

			if (isset($row['id']))
				$this -> id = $row['id'];
			if (isset($row['name']))
				$this -> name = $row['name'];
			if (isset($row['type'])) {
				$this -> typeG = $row['type'];
				$this -> typeName = $GLOBALS['groups_name_array'][$this -> typeG];
			}
			if (isset($row['create_date'])) {
				$this -> create_date = $row['create_date'];
			} else {
				$this -> create_date = date("YmdHis");
			}
			if (isset($row['logo']) && $row['logo'] != '') {
				$this -> logo = $row['logo'];
			}
			if (isset($row['website']) && $row['website'] != '')
				$this -> website = $row['website'];
			if (isset($row['statute']) && $row['statute'] != '')
				$this -> statute = $row['statute'];

		}
	}

	public function readFromDb() {

		if (isset($this -> id)) {
			$query = "SELECT * FROM groups WHERE id='{$this->id}'";
			$result = mysql_query($query) or die(mysql_error());

			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_array($result);
				$this -> parse($row);
			}
		}

	}

	public function writeToDb() {

		if (isset($this -> id)) {//l'id è settato, faccio l'update
			$query = "UPDATE groups SET
              name='{$this->name}',
              type='{$this->typeG}',
              create_date='{$this->create_date}',
              logo='{$this->logo}',
              website='{$this->website}',
              statute='{$this->statute}'
              WHERE id='{$this->id}'";
		} else {
			$query = "INSERT INTO groups SET
              name='{$this->name}',
              type='{$this->typeG}',
              create_date='{$this->create_date}',
              logo='{$this->logo}',
              website='{$this->website}',
              statute='{$this->statute}'";
		}

		if (isset($query)) {

			$result = mysql_query($query) or die(mysql_error());
			if (!isset($this -> id))
				$this -> id = mysql_insert_id();
			return 0;
		}

		return -1;
		//ritorno -1 quando non son riuscito a scrivere
	}

	public function loadElements($OnlyDefault=false) {

		if (isset($this -> id)) {
			
			
			$counter=0;
			
			if($OnlyDefault)
				$sel="AND `default`=1";
			
			
			$query = "SELECT ge.* FROM groups_element ge WHERE ge.id_group='{$this->id}' $sel order by ge.id";
			$result = mysql_query($query) or die(mysql_error());

			while ($row = mysql_fetch_array($result)) {
				$row['grouptype'] = $this -> typeG;
				$tmpE = new GroupElement($row);
				$this -> group_elements[$tmpE -> getId()] = $tmpE;
				$counter++;
			}
		
			return $counter;
		}
	}

	public function storeImage($img, $ext) {

		echo $ext;

		if (isset($this -> id) && isset($img) && $img != '') {
			echo "lol2 $img";

			$upload = new Upload($img, $GLOBALS['group_img_dir'], $this -> id . "." . $ext);
			$this -> logo = $GLOBALS['group_img_dir'] . $upload -> GetFileName();
			@unlink($this -> logo);
			$upload -> UploadFile();
			$this -> writeToDb();
		}

	}

	public function setStatute($txt) {

		if (!isset($this -> id))
			return false;

		$this -> statute = $txt;

		$query = "UPDATE groups SET statute='{$this->statute}' WHERE id='{$this->id}'";
		mysql_query($query) or die(mysql_error());

	}

	public function deleteGroup($myAcc) {

		if (isset($this -> id)) {

			//flaggo come cancellato
			$query = "UPDATE groups SET deleted=1 WHERE id='{$this->id}'";
			mysql_query($query) or die(mysql_error());

			//sbatto fuori gli utenti
			if (empty($this -> group_members))
				$this -> loadMembers();
			foreach ($this->group_members as $key => $value) {
				$value -> getElemObj() -> LeaveGroup($key);
			}

			//scrivo i logs
			$log = new Log();
			$log -> newLog($myAcc -> getId(), $this -> id, "Cancellato gruppo {$this->name}", 11);

			return true;
		}

		return false;

	}

	public function undeleteGroup($myAcc) {

		if (isset($this -> id)) {

			$query = "UPDATE groups SET deleted=0 WHERE id='{$this->id}'";
			mysql_query($query) or die(mysql_error());

			//scrivo i logs
			$log = new Log();
			$log -> newLog($myAcc -> getId(), $this -> id, "Ripristinato gruppo {$this->name}", 10);

			return true;

		}

		return false;

	}

	public function loadMembers($onlyactive = true, $onlyadmin = false, $orderbyrank = false) {

		if (isset($this -> id)) {

			//se non ho ancora caricato gli elementi li carico
			if (empty($this -> group_elements))
				$this -> loadElements();

			$selMax = "";
			if ($onlyactive) {
				$sel .= " AND jd.active=1 AND jd.leavedate < jd.joindate";
			}
			if ($onlyadmin) {
				$sel .= " AND ge.group_admin='{$onlyadmin}'";
			}

			if ($orderbyrank) {
				$ord = "ORDER BY ge.id";
			} else {
				$ord = "ORDER BY joinedid";
			}

			$query = "
      		SELECT ge.id AS elementid, c.name AS charname, c.id AS charid, jd.id AS joinedid

			FROM 
			`groups_joined` jd
			
			INNER JOIN `character` c
			ON c.id=jd.char_id
			INNER JOIN groups_element ge
			ON ge.id=jd.group_id
			INNER JOIN groups g
			ON ge.id_group=g.id
			WHERE g.id='{$this->id}' {$sel}
			$ord";

			if (isset($query)) {

				//echo $query;

				$result = mysql_query($query) or die(mysql_error());
				while ($row = mysql_fetch_array($result)) {

					//creo l'oggetto character
					$char = new Character($row['charid'], $row['charname']);

					//li do alla GroupMember
					$groupmember = new GroupMember($this, $this -> group_elements[$row['elementid']], $char);

					//inserisco la GroupMember nella mia lista
					$this -> group_members[$row['joinedid']] = $groupmember;
					$this -> total_members++;

				}
			}
		}

	}

	public function getName() {
		return $this -> name;
	}

	public function getLogo($tag = false) {

		if ($tag && strlen($this -> logo) > 4)
			return "<img src=\"$this->logo\" alt=\"{$this->name}\"/>";

		return $this -> logo;
	}

	public function getTypeN() {
		return $this -> typeG;
	}

	public function getTypeName() {
		return $this -> typeName;
	}

	public function getId() {
		return $this -> id;
	}

	public function getSite() {
		return $this -> website;
	}

	public function getStatute() {
		return $this -> statute;
	}

	public function getElements() {
		return $this -> group_elements;
	}

	public function getMembers() {
		if (empty($this -> group_members))
			$this -> loadMembers();

		return $this -> group_members;
	}

	public function getNumMembers() {
		if (empty($this -> group_members))
			$this -> loadMembers();

		return $this -> total_members;
	}

	public function setLogo($argv) {
		$this -> logo = $argv;
	}

}

/**
 *
 */
class GroupList {

	private $type;
	private $type_name;
	private $deleted;
	private $counter;
	private $group_list = array();

	public function __construct($type = null, $deleted = 0) {
		$this->counter=0;	
		if (isset($type)) {
			$this -> type = $type;
		}

		$this -> deleted = $deleted;
		$this -> readList();
	}

	private function readList() {

		$sel = " WHERE deleted='{$this->deleted}'";
		if (isset($this -> type))
			$sel = " WHERE type='{$this->type}' AND deleted='{$this->deleted}'";

		$query = "SELECT * FROM groups" . $sel;
		$result = mysql_query($query) or die(mysql_error());

		$this -> type_name = $GLOBALS['groups_name_array'][$this -> type];

		while ($row = mysql_fetch_array($result)) {

			$tmpG = new Group($row);
			$this -> group_list[] = $tmpG;
			$this -> counter++;

		}

	}

	public function GetList() {
		return $this -> group_list;
	}
	
	public function getNum(){
		return $this->counter;
	}

}

class GroupElementList {
	
	private $counter;
	private $element_list = array();

	public function __construct() {
		$this->counter=0;
		//nothing to do
	}

	public function readList($default = 0) {

		$sel = "";
		if (isset($default))
			$sel = " WHERE `default`='$default'";

		$query = "SELECT * FROM groups_element" . $sel;
		$result = mysql_query($query) or die(mysql_error());

		while ($row = mysql_fetch_array($result)) {

			$tmpG = new GroupElement($row);
			$this -> element_list[] = $tmpG;
			$this->counter++;
		}

	}

	public function GetList() {
		return $this -> element_list;
	}
	public function getNum(){
		return $this->counter;
	}

}

/**
 *
 */
class JoinedGroups {

	//oggetto di tipo GroupList
	private $GroupList = array();

	//oggetto di tipo CharList
	//private $CharList=array();

	private $arraySize;

	public function __construct($charid, $grouptype = null, $onlyactive = true, $onlyadmin = false) {
		if (isset($charid) && $charid > 0) {

			$sel = "";
			if (isset($grouptype))
				$sel .= " AND g.type='{$grouptype}'";

			if ($onlyactive) {
				$sel .= " AND jd.active=1";
			}

			if ($onlyadmin)
				$sel .= " AND ge.group_admin='{$onlyadmin}'";

			$query = "SELECT ge.*, jd.id AS joinedid, g.type AS grouptype
		  		  FROM groups_joined jd
		          INNER JOIN groups_element ge
		          ON jd.group_id=ge.id
		          INNER JOIN groups g
		          ON ge.id_group=g.id
		          WHERE jd.char_id='{$charid}'" . $sel;
		}

		if (isset($query)) {

			//echo $query;

			$result = mysql_query($query) or die(mysql_error());
			$this -> arraySize = 0;
			while ($row = mysql_fetch_array($result)) {

				$joinedid = $row['joinedid'];

				$tmpGrp = new GroupElement($row);

				$this -> GroupList[$joinedid] = $tmpGrp;
				$this -> arraySize = $this -> arraySize + 1;

			}
		}
	}

	public function count() {
		return $this -> arraySize;
	}

	/*
	 * restituisce un oggetto array di tipo GroupElement
	 */
	public function getGroups() {
		return $this -> GroupList;
	}

}

/**
 *
 */
class GroupMember {

	private $grpObj;
	private $elemObj;
	private $charObj;

	function __construct($groupObj, $elemObj, $charObj) {
		$this -> grpObj = $groupObj;
		$this -> charObj = $charObj;
		$this -> elemObj = $elemObj;

	}

	public function getGroupObj() {
		return $this -> grpObj;
	}

	public function getElemObj() {
		return $this -> elemObj;
	}

	public function getCharObj() {
		return $this -> charObj;
	}

}
?>