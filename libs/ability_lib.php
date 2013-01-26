<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("common.php");
require_once("character_lib.php");
require_once("group_lib.php");

if(!isset($_SESSION))
{
session_start();
}



/**
 * 
 */
class BuyedAbility {
    
  private $id;
  private $ability_id;
  private $char_id;
  private $buydate;
  private $visible;
  private $level;

  
  private $ability;
  
  
	public function __construct($row=null,$id=null,$ability=null) {
      
    if (isset($id)){
      $this->id=$id;
    }
    if (isset($ability)){
      $this->ability=$ability;
      $this->ability_id=$this->ability->getId();
    }
    if (isset($row)){
      $this->parse($row);
    }		
	}
  
  private function parse($row){
      
    if(isset($row)){
      
      if (isset($row['id']) && $row['id']!="") $this->id=$row['id'];
      if (isset($row['ability_id'])) $this->ability_id=$row['ability_id'];
      if (isset($row['char_id'])) $this->char_id=$row['char_id'];
      if (isset($row['buydate'])){
        $this->buydate=$row['buydate'];
      }else{
        $this->buydate=date("YmdHis");        
      } 
      if (isset($row['visible'])){
        $this->visible=$row['visible'];
      }else{
        $this->visible=1;
      }
      if (isset($row['level'])){
      	$this->level=$row['level'];
		if($this->level<1)
			$this->level=1;
		
	  }else{$this->level=1;}
      
    }
  }
  
  public function writeToDb(){
    
    if (isset($this->id)){ //l'id è settato, faccio l'update
      $query="UPDATE abilities_buyed SET
              ability_id='{$this->ability_id}',
              char_id='{$this->char_id}',
              buydate='{$this->buydate}',
              visible='{$this->visible}',
              level='{$this->level}'
              WHERE id='{$this->id}'";      
    }else{
      $query="INSERT INTO abilities_buyed SET
              ability_id='{$this->ability_id}',
              char_id='{$this->char_id}',
              buydate='{$this->buydate}',
              visible='{$this->visible}',
              level='{$this->level}'"; 
    }
    
    if (isset($query)){
          
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere
    
    
  }
  
  public function deleteBuyedAbility(){
    if(isset($this->id)){
        
      //carico l'abilità da cancellare
      $this->readFromDb();
      $this->readAbility();
      
      //imposto a visibili le ablità che non sono più "replaced"  
        
        
      $query="DELETE FROM abilities_buyed WHERE id='{$this->id}'";
      
      mysql_query($query) or die(mysql_error());
      //eventualmente restituisco il prezzo
      if(mysql_affected_rows()==1 && $GLOBALS['restore_points_onab_remove']){
			
		$tmpChar=new Character($this->char_id);
		$costArr=$this->ability->getCostArray();
		$tmpChar->addMoney($costArr[0]);
		$tmpChar->spendPx($costArr[1]);
		
		unset($tmpChar);
      	
	  }
	  
	  //in ogni caso scrivo i log
	  $newL = new Log();
	  $newL->newLog(null, $this->char_id, "Rimozione abilità {$this->ability->getName()} (id:{$this->ability_id})", $type);
	  
    }
  }
  
  public function Hide($status=0){
    if(isset($this->char_id) && isset($this->ability_id)){
      $query="UPDATE abilities_buyed SET visible='{$status}'
              WHERE ability_id='{$this->ability_id}'
              AND char_id='{$this->char_id}'";
      
      mysql_query($query) or die(mysql_error());
    }
  }
  
  public function isUpgradable($charObj,$step=1,$pay=true){
    
   
    //controllo che con lo step il valore sia nel range accettato
    if ( ($this->level + $step) >=0 && ($this->level + $step)<= $this->ability->getMaxlevel() ){
      $newLv= $this->level + $step;
      //ok
    }else{
        
      return false;
    }
    
    
    if ($pay){
    
      //carico i nuovi costi
      $cost_arr=$this->getUpgradeCost($newLv);
      
      //controllo che abbia i soldi
      if ($charObj->getMoney()>=$cost_arr[0]){
        $money_flag=true;
      }
      
      //controllo che abbia i px
      if ($charObj->getPx()>=$cost_arr[1]){
        $exp_flag=true;
      }
      
      if (!$money_flag || !$exp_flag)
        return false;
      
    }
    
    return true;
    
  }
  
  public function getUpgradeCost($newLv){
  	
	$cost_arr=$this->ability->getCostArray();
	
	if(!isset($cost_arr[3])){
          $multiplier=1;
    }else { $multiplier=$cost_arr[3]; }
	
	$cost_arr[0]=$multiplier*$newLv*$cost_arr[0];
	$cost_arr[1]=$multiplier*$newLv*$cost_arr[1];
	$cost_arr[2]=$multiplier*$newLv*$cost_arr[2];
	
	return $cost_arr;
	
  }
  
  public function upgrade($charObj,$step=1,$pay=true){
    
    
    
    if($this->isUpgradable($charObj,$step,$pay) && isset($this->id)){ //allora posso upgradare!
      
      $this->level += $step;
      
      //se $pay è true allora pago (px e soldi)
      if($pay){
        //$charObj=new character();  
        
        //carico i nuovi costi
      	$cost_arr=$this->getUpgradeCost($this->level);
        
        $charObj->addMoney(-($cost_arr[0]));
        $charObj->spendPx(-($cost_arr[1]));
	      $charObj->addTalents(-($cost_arr[2]));
      }
        
      $query="UPDATE abilities_buyed SET level='{$this->level}'
              WHERE id='{$this->id}'";
                  
      mysql_query($query) or die(mysql_error()); 
      
      return true;
    }
    
    return false;
    
  }
  
  
  public function readFromDb(){
    
	if(empty($this->id)) die ("Manca l'id dell'abilità acquistata.");
	
        $query="SELECT * FROM abilities_buyed WHERE id='{$this->id}'";
      
        $result = mysql_query($query) or die(mysql_error());
      
        if (mysql_num_rows($result)>0){
          $this->parse(mysql_fetch_array($result));
        }
  }
  
  public function readAbility(){
    if (isset($this->ability_id)){
      $this->ability=new Ability(null,$this->ability_id);
      $this->ability->readFromDb();
      //$this->ability_id=$this->ability->getId();
    }
  }
  
  public function getAbility(){
    return $this->ability;
  }
  public function getId(){
    return $this->id;
  }
  public function getVisibility(){
    return $this->visible;
  }
  public function getLevel(){
    return $this->level;
  }
  
}

/**
 * 
 */
class Ability {
	
  private $id;
  private $type;
  private $name;
  private $description;
  private $binds_to_groupelement;
  private $available_at_subs;
  private $dependencies;
  private $cost;
  private $maxlevel;
  private $autoadd_groupjoin;
  private $autoremove_groupleave;
  private $replace;
  private $stats_bonus;
  private $stats_bind;
  private $money_bonus;
  
  private $stats_bonus_array=array();
  private $stats_bind_array=array();
  
  private $groups_element_array=array();
  private $dependencies_array=array();
  private $replace_array=array();
  
  /*
   * 0=soldi
   * 1=px
   * 2=talents
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
      if (isset($row['name'])) $this->name=$row['name'];
      if (isset($row['description'])) htmlspecialchars($this->description=$row['description']);
      if (isset($row['binds_to_groupelement'])){
        $this->binds_to_groupelement=trim($row['binds_to_groupelement']);
        if(strlen($this->binds_to_groupelement)>0)
          $this->groups_element_array=explode(',',$this->binds_to_groupelement);
      }      
      if (isset($row['available_at_subs'])) $this->available_at_subs=$row['available_at_subs'];
      if (isset($row['dependencies'])){
        $this->dependencies=trim($row['dependencies']);
        if(strlen($this->dependencies)>0)
          $this->dependencies_array=explode(',',$this->dependencies); 
      }
      if (isset($row['cost'])){
        $this->cost=$row['cost'];
        $this->cost_array=explode(' ',$this->cost);
      }
      if (isset($row['maxlevel'])){
      	  $this->maxlevel=$row['maxlevel'];
		  if ($this->maxlevel<1) $this->maxlevel=1;
	  }
      if (isset($row['autoadd_groupjoin'])) $this->autoadd_groupjoin=$row['autoadd_groupjoin']; 
      if (isset($row['autoremove_groupleave'])) $this->autoremove_groupleave=$row['autoremove_groupleave']; 
      if (isset($row['replace'])){
        $this->replace=trim($row['replace']);     
        if(strlen($this->replace)>0)
          $this->replace_array=explode(',',$this->replace);
      }
      if (isset($row['money_bonus'])){
        $this->money_bonus=$row['money_bonus'];
      }else{
        $this->money_bonus=0;
      } 
      if (isset($row['stats_bonus'])){
        $this->stats_bonus=$row['stats_bonus'];
      }else{
        $this->stats_bonus='';
      }
	  if (isset($row['stats_bind'])){
        $this->stats_bind=$row['stats_bind'];
      }else{
        $this->stats_bind='';
      }
      //in ogni caso creo l'array dei bonus, basato sulle stats definite nel config
      $tmpBonusArr=explode(' ',$this->stats_bonus);
	  $tmpBindArr=explode(' ',$this->stats_bind);
      $i=0;
      foreach($GLOBALS['name_carStatic'] as $k=>$v){
          
        if(!isset($tmpBonusArr[$i]) || !is_int(intval($tmpBonusArr[$i])))
          $tmpBonusArr[$i]=0; 
		
        $this->stats_bonus_array[$k]=intval($tmpBonusArr[$i]);
		
		if(isset($tmpBindArr[$i]) && intval($tmpBindArr[$i])>0){
         $this->stats_bind_array[$k]=1;
		}else{ $this->stats_bind_array[$k]=0; }
		
        $i++;
         
      }
        $this->totalStatsBonus[$k]=0;
    
      foreach($GLOBALS['name_car'] as $k=>$v){
        
        if(!isset($tmpBonusArr[$i]) || !is_int(intval($tmpBonusArr[$i])))
          $tmpBonusArr[$i]=0;  
          
        $this->stats_bonus_array[$k]=intval($tmpBonusArr[$i]);
		
		if(isset($tmpBindArr[$i]) && intval($tmpBindArr[$i])>0){
         $this->stats_bind_array[$k]=intval($tmpBindArr[$i]);
		}else{ $this->stats_bind_array[$k]=0; }
		
        $i++;
        
      }
      //infine riscrivo lo stats bonus in base all'array parsato e corretto
      $this->stats_bonus=implode(' ',$this->stats_bonus_array);
	  $this->stats_bind=implode(' ',$this->stats_bind_array);
      
      
    }
  }
  
  public function writeToDb(){
    
    if (isset($this->id)){ //l'id è settato, faccio l'update
      $query="UPDATE abilities SET
              type='{$this->type}',
              name='{$this->name}',
              description='{$this->description}',
              binds_to_groupelement='{$this->binds_to_groupelement}',
              available_at_subs='{$this->available_at_subs}',
              dependencies='{$this->dependencies}',
              cost='{$this->cost}',
              maxlevel='{$this->maxlevel}',
              autoadd_groupjoin='{$this->autoadd_groupjoin}',
              autoremove_groupleave='{$this->autoremove_groupleave}',
              stats_bonus='{$this->stats_bonus}',
              stats_bind='{$this->stats_bind}',
              money_bonus='{$this->money_bonus}',
              `replace`='{$this->replace}'
              WHERE id='{$this->id}'";      
    }else{
      $query="INSERT INTO abilities SET
              type='{$this->type}',
              name='{$this->name}',
              description='{$this->description}',
              binds_to_groupelement='{$this->binds_to_groupelement}',
              available_at_subs='{$this->available_at_subs}',
              dependencies='{$this->dependencies}',
              cost='{$this->cost}',
              maxlevel='{$this->maxlevel}',
              autoadd_groupjoin='{$this->autoadd_groupjoin}',
              autoremove_groupleave='{$this->autoremove_groupleave}',
              stats_bonus='{$this->stats_bonus}',
              stats_bind='{$this->stats_bind}',
              money_bonus='{$this->money_bonus}',
              `replace`='{$this->replace}'"; 
    }
    
    if (isset($query)){
          
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere
  }
  
  public function deleteAbility(){
    if(isset($this->id)){
      $query="DELETE FROM abilities WHERE id='{$this->id}'";
      mysql_query($query) or die(mysql_error());
      
      $query="DELETE FROM abilities_buyed WHERE ability_id='{$this->id}'";
      mysql_query($query) or die(mysql_error());
      
    }
  }
  
  public function readFromDb(){
    
    if(isset($this->id)){
        
        $query="SELECT * FROM abilities WHERE id='{$this->id}'";
      
        $result = mysql_query($query) or die(mysql_error());
      
        if (mysql_num_rows($result)>0){
          $this->parse(mysql_fetch_array($result));
        }
    }
  }
  
  public function isBuyable($charObj){
     
	if($this->id<=0)
		return false;	
	  
    
    $flagsArr= array('alreadygot_flag' => true,
    				 'depend_flag' => true,
					 'group_flag' => false,
					 'exp_flag' => false,
					 'money_flag' => false,
					 'talent_flag' => false
					 );
    
    if($this->available_at_subs==-1){
      $only_at_sub=false;
    }
       
    //controllo che non abbia già l'abilità
    foreach($charObj->getAbilities() as $k => $v){
        $v->readAbility();  
        if ($v->getAbility()->getId()==$this->id){
          $flagsArr['alreadygot_flag']=false;
          break;       
        }
    }  
    
    
    //controllo che appartenga ad ALMENO UN gruppo
    if(count($this->groups_element_array)>0){
      foreach($charObj->getGroups() as $k => $v){
        if (in_array($v->getId(),$this->groups_element_array)){
          $flagsArr['group_flag']=true;
          break;       
        }       
      }
    }else{
      $flagsArr['group_flag']=true;
    }
    
    
    //controllo che il char abbia TUTTE le abilità richieste
    
    //popolo l'array una volta sola
    foreach($charObj->getAbilities() as $k => $v){
        $v->readAbility();
    }
    
    //print_r($this->dependencies_array);
    foreach($this->dependencies_array as $key=>$value){
      
      
      $gotFlag=false;  
      foreach($charObj->getAbilities() as $k => $v){
          
        if ($v->getAbility()->getId()==$value){
          $gotFlag=true;
          break;
        }
      }
      //se arrivo qui e gotFlag=false, mi manca una dipendenza
      if (!$gotFlag){
        $flagsArr['depend_flag']=false;
        break;
      }
      
    }
    
    
    //controllo che abbia i soldi
    if ($charObj->getMoney()>=$this->cost_array[0]){
      $flagsArr['money_flag']=true;
    }
    
    //controllo che abbia i px
    if ($charObj->getPx()>=$this->cost_array[1]){
      $flagsArr['exp_flag']=true;
    }

	//controllo che abbia i talents
    if ($charObj->getTalents()>=$this->cost_array[2]){
      $flagsArr['talent_flag']=true;
    }
    
	
	foreach($flagsArr as $k=>$v){
		
		
		if(!$v){
			//echo "$k is false";
			return false;
		}
	}
	
      
      
    return true;
  }

  /**
   *
   * @return oggetto di tipo BuyedAbility
   * @author  
   */
  public function BuyAbility($charObj, $pay=true, $forceHideInDb=false){
    
    $row['ability_id']=$this->id;
    $row['char_id']=$charObj->getCharId();

    $newAbility=new BuyedAbility($row,null,$this);
    $newAbility->writeToDb();
	
	//scrivo i log
	$newLog=new Log();
	$newLog->newLog(null, $charObj->getCharId(), "Acquistata abilità: {$this->name} (id: {$this->id})", 12);
    
    //se $pay è true allora pago (px e soldi)
    if($pay){
      //$charObj=new character();  
      $charObj->addMoney(-($this->cost_array[0]));
      $charObj->spendPx(-($this->cost_array[1]));
	  $charObj->addTalents(-($this->cost_array[2]));
    }
    
    $rowR['char_id']=$charObj->getCharId();
    
    if($forceHideInDb){
      foreach($this->replace_array as $k=>$v){
        $rowR['ability_id']=$v;
        $tmpHide=new BuyedAbility($rowR);
        $tmpHide->Hide();
      }  
    }
	
	return $newAbility;
    
  }
    
  public function isOwnedBy($char_id){
  	
	if(isset($this->id)){
		$query="SELECT * FROM abilities_buyed WHERE char_id='$char_id' AND ability_id='{$this->id}'";
		
		$result=mysql_query($query) or die(mysql_error());
		
		if (mysql_num_rows($result)>0){
			
			$row=mysql_fetch_array($result);
			$buyedAb=new BuyedAbility($row);
			
			return $buyedAb;
		}
		
  	}
	return false;
	
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
  public function getDescription(){
    return $this->description;
  }
  public function getBindToGroups(){
    return $this->binds_to_groupelement;
  }
  public function getGroupsArray(){
    return $this->groups_element_array;
  }
  public function getAvailableSubscription(){
    return $this->available_at_subs;
  }
  public function getDependencies(){
    return $this->dependencies;
  }
  public function getDependenciesArray(){
    return $this->dependencies_array;
  }
  public function getCost(){

	  $retArr=array();
	  for($i=0;$i<4;$i++)
	    $retArr=isset($this->cost[$i]) ? $this->cost[$i] : 0 ;

	  return $retArr;
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
  public function getReplace(){
    return $this->replace;
  }
  public function getReplaceArray(){
    return $this->replace_array;
  }
  public function getStatsBonus(){
    return $this->stats_bonus;
  }
  public function getStatsBonusArray(){
    return $this->stats_bonus_array;
  }
  public function getStatsBind(){
    return $this->stats_bind;
  }
  public function getStatsBindArray(){
    return $this->stats_bind_array;
  }
  public function getMoneyBonus(){
    return $this->money_bonus;
  }
  public function getMaxlevel(){
    return $this->maxlevel;
  }
  
}

/**
 * 
 */
class AbilityList {
	
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
                               $availSubs=null,
                               $dependson=null,
                               $onlybuyable=false,
                               $charObj=null
                               ){
      
    $sel="";    
    if(isset($type)){
      $sel="WHERE type='$type'";
    }
    
    if(isset($availSubs)){
      if($availSubs==-1){
      	$sel="WHERE available_at_subs=0 OR available_at_subs=1";
	  }else{
      	$sel="WHERE available_at_subs>0";
	  }
    }
    if(isset($availSubs) && isset($type)){
      $sel="WHERE available_at_subs>0 AND type='$type'";
    }
    if(isset($dependson)){
      $sel="WHERE dependencies='$dependson'";
    }  
             
    $query="SELECT * FROM abilities ".$sel;    
    $result = mysql_query($query) or die(mysql_error());
      
    while ($row=mysql_fetch_array($result)){
      	
		if(isset($group)){
			$grB=explode(',',$row['binds_to_groupelement']);
			if(!in_array($group, $grB))
				continue;
			
    	}
		
      
      $tmpAb=new Ability($row);
      if ($onlybuyable && isset($charObj)){
          
        if(!$tmpAb->isBuyable($charObj))
          continue;
      }
      
      $this->list[$tmpAb->getId()]=$tmpAb;
      $this->sumBonus($tmpAb);
      $this->listsize++; 
    }
    
  }
                               
  protected function sumBonus($ab){
    $this->sumStatsBonus($ab->getStatsBonusArray());
    $this->sumMoneyBonus($ab->getMoneyBonus());
  }
  
  protected function sumStatsBonus($arr){
    
    foreach($this->totalStatsBonus as $k=>$v){
      
      $this->totalStatsBonus[$k]+=$arr[$k];
      
    }
  }
  
  protected function sumMoneyBonus($mon){
    $this->totalMoneyBonus+=$mon;
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
  public function getTotalMoneyBonus(){
    return $this->totalMoneyBonus;
  }
  
}

/**
 * 
 */
class BuyedAbilityList extends AbilityList{
  
  //private $list=array();
  
  private $replaced_arr=array();
  private $invisible_arr=array();
  
  public function __construct($char_id) {
      
    $this->init(); 
      
    $query="SELECT ab.*
            FROM abilities_buyed ab
            WHERE
            ab.char_id='$char_id'";    
      
    $result = mysql_query($query) or die(mysql_error());
      
    while ($row=mysql_fetch_array($result)){
      
      $tmpAb=new Ability(null,$row['ability_id']);
      $tmpAb->readFromDb();
      $this->sumBonus($tmpAb);
      
      
      
      $this->replaced_arr=array_merge($this->replaced_arr,$tmpAb->getReplaceArray()); 
      if($row['visible']==0)
        $this->invisible_arr[]= $tmpAb->getId();
      
      $tmpAbB=new BuyedAbility($row,null,$tmpAb);
      $this->list[$row['id']]=$tmpAbB;
      $this->listsize++;
    }
  }
  
  public function getAbilities($showall=false){
    
    if ($showall) 
      return $this->list;
      
    $visible_list=array();
    //se showall è false, mostro solo quelle VISIBILI e NON rimpiazzate
    foreach($this->list as $k=>$v){
          
      $ability_id=$v->getAbility()->getId();
          
      if(!in_array($ability_id,$this->replaced_arr) && !in_array($ability_id,$this->invisible_arr))
        $visible_list[$k]=$v;
    }
    
    return $visible_list;
    
    
  }
}


?>