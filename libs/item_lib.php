<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("common.php");
require_once("character_lib.php");
require_once("logs_lib.php");


/**
 * 
 */
class BuyedItem {

	private $id;
	private $char_id;
	private $item_id;
	private $notes;
	private $equipped;
	private $buydate;

	
	private $item;
	
	
	public function __construct($row=null,$id=null,$item=null) {

		if (isset($id)){
			$this->id=$id;
		}
		if (isset($item)){
			$this->item=$item;
			$this->item_id=$this->item->getId();
		}
		if (isset($row)){
			$this->parse($row);
		}		
	}
	
	private function parse($row){

		if(isset($row)){
			
			if (isset($row['id']) && $row['id']!="") $this->id=$row['id'];
			if (isset($row['item_id'])) $this->item_id=$row['item_id'];
			if (isset($row['char_id'])) $this->char_id=$row['char_id'];
			if (isset($row['buydate'])){
				$this->buydate=$row['buydate'];
			}else{
				$this->buydate=date("YmdHis");        
			} 
			if (isset($row['equipped'])){
				$this->equipped=$row['equipped'];
			}else{
				$this->equipped=0;
			}
			if (isset($row['notes'])) $this->notes=$row['notes'];
			
		}
	}
	
	public function writeToDb(){
		
		if (isset($this->id)){ //l'id è settato, faccio l'update
		$query="UPDATE items_buyed SET
		item_id='{$this->item_id}',
		char_id='{$this->char_id}',
		buydate='{$this->buydate}',
		equipped='{$this->equipped}',
		notes='{$this->notes}'
		WHERE id='{$this->id}'";      
	}else{
		$query="INSERT INTO items_buyed SET
		item_id='{$this->item_id}',
		char_id='{$this->char_id}',
		buydate='{$this->buydate}',
		equipped='{$this->equipped}',
		notes='{$this->notes}'"; 
	}

	if (isset($query)){

		$result = mysql_query($query) or die(mysql_error());
		if (!isset($this->id))
			$this->id=mysql_insert_id();
		return 0;
	}

		return -1; //ritorno -1 quando non son riuscito a scrivere
		
		
	}
	
	
	public function Equip($status=1){
		if(isset($this->char_id) && isset($this->id)){
			$query="UPDATE items_buyed SET equipped='{$status}'
			WHERE id='{$this->id}'
			AND char_id='{$this->char_id}'";
			
			mysql_query($query) or die(mysql_error());
		}
	}
	
	public function setNotes($val){
		if(isset($this->char_id) && isset($this->id)){

			$this->notes=$val;
			$query="UPDATE items_buyed SET notes='".mysql_real_escape_string($val)."'
			WHERE id='{$this->id}'
			AND char_id='{$this->char_id}'";
			
			mysql_query($query) or die(mysql_error());
		}
	}
	
	public function changeOwner($newOwnerId){
		if(isset($this->char_id) && isset($this->id)){

			if(empty($this->item))
				$this->readItem();

			$query="UPDATE items_buyed SET char_id='{$newOwnerId}'
			WHERE id='{$this->id}'";
			
			mysql_query($query) or die(mysql_error());
			
			$Log=new Log();
			$Log->newLog($this->char_id, $newOwnerId, "Ceduto oggetto: {$this->item->getName()} (id:{$this->item->getId()})", 7);

			return true;
		}
		return false;
	}

	public function deleteBuyedItem($char_id=null,$item_obj=null){

		$query="";

		if(isset($this->id)){

			//carico l'abilità da cancellare
			$this->readFromDb();
			$this->readItem();

			$query="DELETE FROM items_buyed WHERE id='{$this->id}'";
		}elseif (char_id!=null && $item_obj!=null) {
			
			$this->item = $item_obj;
			$this->item_id = $this->item->getId();
			$this->char_id = $char_id;

			$query="DELETE FROM items_buyed WHERE item_id='{$this->item_id}' AND char_id='{$this->char_id}' ";
		}

		if($query!=""){
			
			mysql_query($query) or die(mysql_error());

		//in ogni caso scrivo i log
			$newL = new Log();
			$newL->newLog(null, $this->char_id, "Rimozione oggetto {$this->item->getName()} (id:{$this->item_id})", $type);

		}
	}
	
	public function readFromDb(){
		
		if(isset($this->id)){

			$query="SELECT * FROM items_buyed WHERE id='{$this->id}'";
			
			$result = mysql_query($query) or die(mysql_error());
			
			if (mysql_num_rows($result)>0){
				$this->parse(mysql_fetch_array($result));
			}
		}
	}
	
	public function readItem(){
		if (isset($this->item_id)){
			$this->item=new Item(null,$this->item_id);
			$this->item->readFromDb();
		}
	}
	
	public function getItem(){
		return $this->item;
	}
	public function getId(){
		return $this->id;
	}
	public function getEquipped(){
		return $this->equipped;
	}
	public function getNotes(){
		return $this->notes;
	}
	
}



/**
 * 
 */
class Item {
	
	private $id;
	private $type;
	private $public;
	private $name;
	private $description;
	private $image;
	private $cost;
	private $create_date;
	private $creator;
	private $binds_to_groupelement;
	private $autoadd_groupjoin;
	private $autoremove_groupleave;
	private $stats_bonus;
	
	private $stats_bonus_array=array();
	
	private $groups_element_array=array();
	
	/*
	 * 0=soldi
	 * 1=px
	 */
	private $cost_array=array();

	public function __construct($row=null,$id=null) {

		if (isset($id)){
			$this->id=$id;
		}  
		if (isset($row)){
			$this->parse($row);
		}
		
	}
	
	private function parse($row){

		if(isset($row)){
			
			if (isset($row['id']) && $row['id']!="") $this->id=$row['id'];
			if (isset($row['type'])) $this->type=$row['type'];
			if (isset($row['public'])) $this->public=$row['public'];
			if (isset($row['name'])) $this->name=$row['name'];
			if (isset($row['description'])) htmlspecialchars($this->description=$row['description']);
			if (isset($row['image'])) $this->image=$row['image'];
			if (isset($row['create_date'])){
				$this->create_date=$row['create_date'];
			}else{
				$this->create_date=date("YmdHis"); 
			}
			if (isset($row['creator'])) $this->creator=$row['creator'];
			if (isset($row['cost'])){
				$this->cost=$row['cost'];
				$this->cost_array=explode(' ',$this->cost);
			}
			if (isset($row['binds_to_groupelement'])){
				$this->binds_to_groupelement=trim($row['binds_to_groupelement']);
				if(strlen($this->binds_to_groupelement)>0)
					$this->groups_element_array=explode(',',$this->binds_to_groupelement);
			}      
			if (isset($row['autoadd_groupjoin'])) $this->autoadd_groupjoin=$row['autoadd_groupjoin']; 
			if (isset($row['autoremove_groupleave'])) $this->autoremove_groupleave=$row['autoremove_groupleave']; 
			if (isset($row['stats_bonus'])){
				$this->stats_bonus=$row['stats_bonus'];
			}else{
				$this->stats_bonus='';
			}
			
			//in ogni caso creo l'array dei bonus, basato sulle stats definite nel config
			$tmpBonusArr=explode(' ',$this->stats_bonus);
			$i=0;
			foreach($GLOBALS['name_carStatic'] as $k=>$v){

				if(!isset($tmpBonusArr[$i]) || !is_int(intval($tmpBonusArr[$i])))
					$tmpBonusArr[$i]=0;  

				$this->stats_bonus_array[$k]=intval($tmpBonusArr[$i]); 
				$i++;

			}
			$this->totalStatsBonus[$k]=0;

			foreach($GLOBALS['name_car'] as $k=>$v){
				
				if(!isset($tmpBonusArr[$i]) || !is_int(intval($tmpBonusArr[$i])))
					$tmpBonusArr[$i]=0;  

				$this->stats_bonus_array[$k]=intval($tmpBonusArr[$i]); 
				$i++;
				
			}
			//infine riscrivo lo stats bonus in base all'array parsato e corretto
			$this->stats_bonus=implode(' ',$this->stats_bonus_array);
			
			
		}
	}
	
	public function writeToDb(){
		
		if (isset($this->id)){ //l'id è settato, faccio l'update
		$query="UPDATE items SET
		type='{$this->type}',
		public='{$this->public}',
		name='{$this->name}',
		description='{$this->description}',
		create_date='{$this->create_date}',
		creator='{$this->creator}',
		cost='{$this->cost}',
		binds_to_groupelement='{$this->binds_to_groupelement}',
		autoadd_groupjoin='{$this->autoadd_groupjoin}',
		autoremove_groupleave='{$this->autoremove_groupleave}',
		stats_bonus='{$this->stats_bonus}'

		WHERE id='{$this->id}'";      
	}else{
		$query="INSERT INTO items SET
		type='{$this->type}',
		public='{$this->public}',
		name='{$this->name}',
		description='{$this->description}',
		create_date='{$this->create_date}',
		creator='{$this->creator}',
		cost='{$this->cost}',
		binds_to_groupelement='{$this->binds_to_groupelement}',
		autoadd_groupjoin='{$this->autoadd_groupjoin}',
		autoremove_groupleave='{$this->autoremove_groupleave}',
		stats_bonus='{$this->stats_bonus}'"; 
	}

	if (isset($query)){

		$result = mysql_query($query) or die(mysql_error());
		if (!isset($this->id))
			$this->id=mysql_insert_id();
		return 0;
	}

		return -1; //ritorno -1 quando non son riuscito a scrivere
	}
	
	public function deleteItem(){
		if(isset($this->id)){

			//leggo da db la locazione dell'immagine e la cancello
			$this->readFromDb();
			@unlink($this->image);	
			
			$query="DELETE FROM items WHERE id='{$this->id}'";
			mysql_query($query) or die(mysql_error());
			
			$query="DELETE FROM items_buyed WHERE item_id='{$this->id}'";
			mysql_query($query) or die(mysql_error());
			
		}
	}
	
	public function readFromDb(){
		
		if(isset($this->id)){

			$query="SELECT * FROM items WHERE id='{$this->id}'";
			
			$result = mysql_query($query) or die(mysql_error());
			
			if (mysql_num_rows($result)>0){
				$this->parse(mysql_fetch_array($result));
			}
		}
	}

	private function setImage($img){
		if(isset($this->id)){
			$this->image=$img;
			$query="UPDATE items SET image='{$this->image}' WHERE id='{$this->id}'";
			
			$result = mysql_query($query) or die(mysql_error());
			
		}

	}

	public function storeImage($img,$ext){

		if(isset($this->id) && isset($img) && $img!=''){

			$imgName=	$this->id.".".$ext;

			$upload=new Upload($img,$GLOBALS['items_img_dir'],$imgName);
			$this->image=$GLOBALS['items_img_dir'].$upload->GetFileName();
			@unlink($this->image);
			$upload->UploadFile(); 
			$this->setImage($this->image);
		}
		
	}
	
	public function isBuyable($charObj){

		if($this->id<=0)
			return false;	
		


		$array_flag=array(
			'group_flag'=>false,
			'exp_flag'=>false,
			'money_flag'=>false,
			'public_flag'=>false
			);

		//controllo che appartenga ad ALMENO UN gruppo
		if(count($this->groups_element_array)>0){
			foreach($charObj->getGroups() as $k => $v){
				if (in_array($v->getId(),$this->groups_element_array)){
					$array_flag['group_flag']=true;
					break;       
				}       
			}
		}else{
			$array_flag['group_flag']=true;
		}
		
		
		//controllo che abbia i soldi
		if ($charObj->getMoney()>=$this->cost_array[0]){
			$array_flag['money_flag']=true;
		}
		
		//controllo che abbia i px
		if ($charObj->getPx()>=$this->cost_array[1]){
			$array_flag['exp_flag']=true;
		}
		
	//echo "public: {$this->public}";
		if($this->public<1){
			if ($charObj->Account()->getModLevel()>=$GLOBALS['admin_edit_items_required']) $array_flag['public_flag']=true;
		}else{
			$array_flag['public_flag']=true;
		}
		


		$ret=true;
		foreach($array_flag as $k=>$v){

			if(!$v){
				$ret=false;
			//echo "$k is $v";
			}

		}

		return $ret;
	}

	/**
	 *
	 * @return oggetto di tipo BuyedItem
	 * @author  
	 */
	public function BuyItem($charObj, $pay=true){
		
		$row['item_id']=$this->id;
		$row['char_id']=$charObj->getCharId();

		$newItem=new BuyedItem($row,null,$this);
		$newItem->writeToDb();
		
		//se $pay è true allora pago (px e soldi)
		if($pay){
			//$charObj=new character();  
			$charObj->addMoney(-($this->cost_array[0]));
			if($GLOBALS['pay_exp_forItems'])
				$charObj->spendPx(-($this->cost_array[1]));
		}
		$Log=new Log();
		$Log->newLog($_SESSION['char_id'], $charObj->getCharId(), "Acquisto oggetto: {$this->name} (id:{$this->id})", 6);

		return $newItem;
		
	}	
	
	public function getId(){
		return $this->id;
	}
	public function getType(){
		return $this->type;
	}
	public function getName(){
		return $this->name;
	}
	public function getPublic(){
		return $this->public;
	}
	public function getDescription(){
		return $this->description;
	}
	public function getBindToGroups(){
		return $this->binds_to_groupelement;
	}
	public function getGroupsArray(){
		return $this->groups_element_array;
	}
	public function getImage(){
		return $this->image;
	}
	public function getCreator(){
		return $this->dependencies;
	}
	public function getCreateDate(){
		return $this->dependencies_array;
	}
	public function getCost(){
		return $this->cost;
	}
	
	/**
	 * 
	 *
	 * @return array(soldi,px)
	 * @author  
	 */
	public function getCostArray(){
		return $this->cost_array;
	}
	public function getAutoRemove(){
		return $this->autoremove_groupleve;
	}
	public function getAutoAdd(){
		return $this->autoadd_groupjoin;
	}
	public function getStatsBonus(){
		return $this->stats_bonus;
	}
	public function getStatsBonusArray(){
		return $this->stats_bonus_array;
	}
	
}



/**
 * 
 */
class ItemList {
	
	protected $list=array();
	protected $listsize;
	
	protected $totalMoneyBonus;
	protected $totalStatsBonus=array();
	
	public function __construct() {
		$this->init();
	}
	
	protected function init(){

		$this->listsize=0;
		
		foreach($GLOBALS['name_carStatic'] as $k=>$v)
			$this->totalStatsBonus[$k]=0;
		
		foreach($GLOBALS['name_car'] as $k=>$v)
			$this->totalStatsBonus[$k]=0;
		
		$this->totalMoneyBonus=0;    
	}
	
	public function populateList($type=null,
		$group=null,
		$onlybuyable=false,
		$charObj=null,
		$onlypublic=false
		){

		$sel="";    
		if(isset($type)){
			$sel="WHERE type='$type'";
		}

		if ($onlypublic){
			$sel.=" AND public=1";
		}

		$query="SELECT * FROM items ".$sel;    

		$result = mysql_query($query) or die(mysql_error());

		while ($row=mysql_fetch_array($result)){

			if(isset($group)){
				$grB=explode(',',$row['binds_to_groupelement']);
				if(!in_array($group, $grB))
					continue;
				
			}
			
			$tmpAb=new Item($row);
			if ($onlybuyable && isset($charObj)){

				if(!$tmpAb->isBuyable($charObj))
					continue;
			}
			
			$this->list[]=$tmpAb;
			$this->sumBonus($tmpAb,$this->totalStatsBonus);
			$this->listsize++; 
		}
		
	}

	protected function sumBonus($Item,&$arr){

		
		$this->sumStatsBonus($Item->getStatsBonusArray(),$arr);


	}
	
	protected function sumStatsBonus($arr,&$dst){
		
		foreach($dst as $k=>$v){
			
			$dst[$k]+=$arr[$k];
			
		}

	}
	
	public function getList(){
		return $this->list;
	}
	
	public function getSize(){
		return $this->listsize;
	}
	
	public function getTotalStatsBonus(){
		return $this->totalStatsBonus;
	}
	
}

/**
 * 
 */
class BuyedItemList extends ItemList{
	
	//private $list=array();
	
	private $equipped_arr=array();
	private $equippedsize;
	private $unequipped_arr=array();
	private $unequippedsize;
	
	private $equipped_bonus=array();
	private $total_bonus=array();
	
	public function __construct($char_id) {

		$this->init();

		$this->equipped_bonus=$this->totalStatsBonus;
		$this->total_bonus=$this->totalStatsBonus;

		$this->equippedsize=0;
		$this->unequippedsize=0;


		
		$query="SELECT ib.*
		FROM items_buyed ib
		WHERE
		ib.char_id='$char_id'";    

		$result = mysql_query($query) or die(mysql_error());

		while ($row=mysql_fetch_array($result)){
			
			$tmpIb=new Item(null,$row['item_id']);
			$tmpIb->readFromDb();
			
			$tmpIbB=new BuyedItem($row,null,$tmpIb);
			
			if($row['equipped']==1){
				$this->equipped_arr[$row['id']]= $tmpIbB;
				$this->equippedsize++;
				$this->sumBonus($tmpIb, $this->equipped_bonus);
			}else{
				$this->unequipped_arr[$row['id']]= $tmpIbB;
				$this->unequippedsize++;
			}
			
			$this->sumBonus($tmpIb, $this->total_bonus);

			$this->list[$row['id']]=$tmpIbB;
			$this->listsize++;
		}
	}
	
	public function getEquippedItems(){
		return $this->equipped_arr;
	}
	
	public function getUnequippedItems(){
		return $this->unequipped_arr;
	}
	
	public function getEquippedSize(){
		return $this->equippedsize;
	}
	public function getUnequippedSize(){
		return $this->unequippedsize;
	}
	
	public function getEquippedBonus(){
		return $this->equipped_bonus;
	}
	
	public function getTotalBonus(){
		return $this->total_bonus;
	}
	
}

?>