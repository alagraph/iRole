<?php
/*  I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/group_lib.php");

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

$groupId=$_REQUEST['id'];

$_SESSION['modlevel']=$MyChar_obj->Account()->getModLevel();
$MyChar_obj->readGroupsAdmin();

$canAdmin=$MyChar_obj->isGroupAdmin($groupId);

if ($admin_groups_level_required>$_SESSION['modlevel'] && !$canAdmin){
  echo "Accesso negato, permessi insufficienti.";
  exit();
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
<script>
$(function() {
	
	$( "#add_gJ" ).autocomplete({
			source: "char_list.php",
			minLength: 2,
			delay: 200
	});
});
</script>
<body>
<?php
$group=new Group(null,$groupId);
$group->readFromDb();
$group->loadElements();

if (isset($_REQUEST["save"])){ //se mi arriva il save devo salvare il nuovo stato dei membri del gruppo.
  //prima salvo il membro nuovo
  
  if(isset($_REQUEST['add_gJ']) && $_REQUEST['add_gJ']!=""){
    
    $addChar=new Character(null,$_REQUEST['add_gJ']);
    $addChar->checkExistance();
    
    if(!$addChar->exists()){
      echo "<div>Personaggio {$_REQUEST['add_gJ']} inesistente</div>";
    }else{
      //se esiste allora lo aggiungo
      foreach($group->getElements() as $key => $value){
          
        $objToSave=$value;
        if($objToSave->getDefault()==1)
          break;
        
      }
      //$objToSave=new GroupElement();
      if(!$objToSave->JoinGroup($addChar)){
	  	echo $addChar->getCharName()." fa già parte di un gruppo di tipo {$group->getTypeName()}";
	  }else {echo "<div>Personaggio {$_REQUEST['add_gJ']} aggiunto con carica: {$objToSave->getName()}</div>";}
    }
    
  }
  
  //salvo gli altri ch_e[] e ch_n[]
  
  if (count($_REQUEST['ch_n'])==count($_REQUEST['ch_e']) && count($_REQUEST['ch_n'])==count($_REQUEST['ch_o']) && count($_REQUEST['ch_n'])>0){
  
	  foreach ($_REQUEST['ch_n'] as $key => $value) {
	      
		  
	      $element_id=intval($_REQUEST["ch_e"][$key]);
	      
	      $editChar=new Character($value);
	      $editChar->checkExistance();
	      
	      if($editChar->exists() && intval($_REQUEST["ch_o"][$key])!=$element_id){
	        	
	        
		        foreach($group->getElements() as $k => $v){
		            
		          if ($v->getId()==$element_id){
		              //$v=new GroupElement();
		              //$v->
		            if(!$v->JoinGroup($editChar))
						echo "Impossibile modificare il gruppo al personaggio {$editChar->getCharName()}";
		            break;
		          }elseif($v->getId()==abs($element_id)){
		          	$v->LeaveGroup($editChar->getCharId());
					break;
				  }
		          
		        }
	      }
	    
	  }
	  
  }
  
}
  
?>
<div class="width90 center">
<h2>Gestione del gruppo <?php echo $group->getName(); ?></h2>
<form id="admin_groupjoin" name="admin_groupjoin" action="admin_groupjoin.php?id=<?php echo $groupId; ?>&save=1" method="post">
<div class="clearborder panel_bg roundcorner">
<div>Aggiungi membro: <input type="text" name="add_gJ" id="add_gJ" placeholder="Nome" /></div>

<div>Membri Attuali:
<?php



foreach($group->getMembers() as $key => $value){
    
  //$value=new GroupMember();
  $carName="<select name=\"ch_e[]\">";
  
  foreach($group->getElements() as $k=>$v){
    //$v=new GroupElement();
    
    if($v->getId()==$value->getElemObj()->getId()){
      $carName.="<option selected=\"selected\" value=\"{$v->getId()}\">{$v->getName()}</option>";
    }else{
      $carName.="<option value=\"{$v->getId()}\">{$v->getName()}</option>";
    }
    
  }
  $carName.="<option value=\"-{$value->getElemObj()->getId()}\">[Rimuovi]</option>
  			</select>";
  
  echo "<div><input type=\"hidden\" name=\"ch_o[]\" value=\"{$value->getElemObj()->getId()}\"/><input type=\"hidden\" name=\"ch_n[]\" value=\"{$value->getCharObj()->getCharId()}\"/>".$value->getCharObj()->getCharName()." - {$carName}</div>"; 
}

?>
</div>
<input type="submit" value="Salva" />
</div>
</form>  
</div>
</body>
</html>
