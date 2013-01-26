<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/ability_lib.php");

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

if ($admin_edit_ability_required>$_SESSION['modlevel']){
  echo "Accesso negato, permessi insufficienti.";
  exit();
}

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Gestione Abilità</title>
<? include_headers(null); ?>
<script>
$(document).ready(function(){
	
	//helper dei popup
	$(".help").each(function(){
		$(this).CreateBubblePopup({
			width: 200,			
			innerHtml: $(this).attr('help'),
			themeName: 'all-black',
			themePath: 'js/jquerybubblepopup-themes'
			
		}); 
	});
	
});
</script>
</head>
<body>
<?php

if (isset($_REQUEST['s'])){
  //salvo
  
  
  $arr=array();
  
  $arr['id']=$_REQUEST['id'];
  $arr['type']=$_REQUEST['tipo'];
  $arr['name']=$_REQUEST['nome'];
  $arr['description']=$_REQUEST['descrizione'];
  $arr['binds_to_groupelement']='';
  if (isset($_REQUEST['gruppo']))
    $arr['binds_to_groupelement']=implode(",",$_REQUEST['gruppo']);
  $arr['available_at_subs']=$_REQUEST['iscrizione'];
  $arr['dependencies']='';
  if (isset($_REQUEST['dipendeda']))
    $arr['dependencies']=implode(",",$_REQUEST['dipendeda']);
  if (isset($_REQUEST['rimpiazza']))
    $arr['replace']=implode(",",$_REQUEST['rimpiazza']);
  $arr['autoadd_groupjoin']=$_REQUEST['autoadd'];
  $arr['autoremove_groupleave']=$_REQUEST['autoremove'];
  $arr['maxlevel']=$_REQUEST['maxlevel'];  
  $arr['cost']= strval(0 + intval($_REQUEST['costosoldi'])) ." ". strval(0 + intval($_REQUEST['costopx'])) ." ". strval(0 + intval($_REQUEST['costotalents'])) ." ". strval(0 + intval($_REQUEST['multiplier']));
  $arr['money_bonus']=strval(0 + intval($_REQUEST['moneyBonus']));
  $arr['stats_bonus']=implode(' ',$_REQUEST['statBonus']);
  $arr['stats_bind']=implode(' ',$_REQUEST['statBind']);
  
  
  $addAbility=new Ability($arr);
  
  if($_REQUEST['delete']==1){
    $addAbility->deleteAbility();
    echo "Abilità Cancellata";
  }else{
    $addAbility->writeToDb();
    echo "Abilità Aggiunta";
  }
  
}

$list=new AbilityList(null);
$list->populateList();

$flag=false;
if (isset($_REQUEST['n'])){
  //creazione nuova abilità
  $flag=true;
  $old_dep=array();
  $old_grp=array();
  $old_rpl=array();
  $dspIscr=0;
  $px_cost="";
  $money_cost="";
  $talent_cost="";
  $multiplier="0";
  $descript="";
  $name="";
  $old_id="";
  $old_type=0;
  $autoadd=0;
  $autoremove=0;
  $moneybonus=0;
  
  $oldStatB=array();
  $oldStatBind=array();
  
}
elseif (isset($_REQUEST['e']) && $_REQUEST['e']>0){
  $flag=true;
  
  $old_ability=new Ability(null,$_REQUEST['e']);
  $old_ability->readFromDb();
  
  $old_dep=explode(',',$old_ability->getDependencies());
  $old_grp=explode(',',$old_ability->getBindToGroups());
  $old_rpl=explode(',',$old_ability->getReplace());
  
  $dspIscr=$old_ability->getAvailableSubscription();

  $oldAb_cost=$old_ability->getCostArray();
   
  $px_cost=$oldAb_cost[1];
  $money_cost=$oldAb_cost[0];
  $talent_cost=$oldAb_cost[2];
  
  $multiplier=$oldAb_cost[3];
  $descript=$old_ability->getDescription();
  $name=$old_ability->getName();
  $maxlevel=$old_ability->getMaxlevel();
  $old_id=$old_ability->getId();
  $old_type=$old_ability->getType();
  $autoadd=$old_ability->getAutoAdd();
  $autoremove=$old_ability->getAutoRemove();
  $moneybonus=$old_ability->getMoneyBonus();
  $oldStatB=$old_ability->getStatsBonusArray();
  $oldStatBind=$old_ability->getStatsBindArray();
  
} 

if ($flag){

  $grp_bind=new GroupList();
  foreach ($grp_bind->GetList() as $key=>$value){
    
    //$value=new Group();
    $value->loadElements();
    foreach($value->getElements() as $k=>$v){
      
      //$v=new GroupElement();      
      $selct="";
      if(in_array($v->getId(),$old_grp)) $selct="selected=\"selected\" ";
    
      $grpLst.="<option value=\"{$v->getId()}\" $selct>{$v->getGroup()} - {$v->getName()}</option>\n";
            
    }  
    
  }
  
  foreach ($list->getList() as $k=>$v){
      
    $selct="";
    if(in_array($v->getId(),$old_dep)) $selct="selected=\"selected\" ";
    
    $ablLst.="<option value=\"{$v->getId()}\" $selct>{$v->getName()}</option>\n";
    
  }
  
  foreach ($list->getList() as $k=>$v){
      
    $selct="";
    if(in_array($v->getId(),$old_rpl)) $selct="selected=\"selected\" ";
    
    $rplLst.="<option value=\"{$v->getId()}\" $selct>{$v->getName()}</option>\n";
    
  }
  
  for ($i=0;$i<3;$i++){
      
    $selct="";
    if($dspIscr==$i) $selct="selected=\"selected\" ";
    
    if($i==0) $wr="No";
    if($i==1) $wr="Si";
    if($i==2) $wr="Solo all'iscrizione";
      
    $dspLst.="<option value=\"$i\" $selct>$wr</option>\n";
  }
  
  foreach ($abilities_types_array as $key=>$value){
    
    $select="";
    if($old_type==$key) $select="selected=\"select\" ";
      
    $typLst.="<option value=\"$key\" $select>$value</option>\n";
  }
  
  $ckdAdd="";
  if($autoadd==1)
    $ckdAdd="checked=\"checked\"";
    
  $ckdRmv="";
  if($autoremove==1)
    $ckdRmv="checked=\"checked\"";

?>
<h2>Modifica Abilità</h2>
<a href="admin_ability.php">« Back</a>
<form action="admin_ability.php?s=1" method="post">
<input type="hidden" value="<?php echo $old_id; ?>" name="id" id="id" />
<div class="floatleft roundcorner clearborder panel_bg" style="width:55%;">
<table>
<tr><td style="width:50%">Tipo</td><td><select name="tipo"><?php echo $typLst; ?></select></td></tr>
<tr><td>Nome</td><td><input type="text" value="<?php echo $name; ?>" name="nome" id="nome" style="width: 95%;" /></td></tr>
<tr><td>Descrizione</td><td><textarea rows="10" name="descrizione" style="width: 95%;" ><?php echo $descript; ?></textarea></td></tr>
<tr><td>Livello massimo</td><td><input type="text" value="<?php echo $maxlevel; ?>" name="maxlevel" id="maxlevel" /></td></tr>
<tr><td>Disponibile all'iscrizione</td><td><select name="iscrizione"><?php echo $dspLst; ?></select></td></tr>
<tr><td>Vincolata a gruppo</td><td><select style="width:200px;" multiple="multiple" size="7" name="gruppo[]" ><?php echo $grpLst; ?></select></td></tr>
<tr><td>Assegna automaticamente all'entrata nel gruppo</td><td><input type="checkbox" name="autoadd" <?php echo $ckdAdd; ?> value="1" /></td></tr>
<tr><td>Rimuovi all'uscita dal gruppo</td><td><input type="checkbox" name="autoremove"  <?php echo $ckdRmv; ?> value="1" /></td></tr>
<tr><td>Dipende da altre abilità</td><td><select style="width:200px;" multiple="multiple" size="7" name="dipendeda[]"><?php echo $ablLst; ?></select></td></tr>
<tr><td>Rimpiazza le abilità</td><td><select style="width:200px;" multiple="multiple" size="7" name="rimpiazza[]"><?php echo $rplLst; ?></select></td></tr>
<tr><td>Costo px</td><td><input type="text" value="<?php echo $px_cost; ?>" name="costopx" id="costopx" /></td></tr>
<tr><td>Costo soldi</td><td><input type="text" value="<?php echo $money_cost; ?>" name="costosoldi" id="costosoldi" /></td></tr>
<tr><td>Costo talenti</td><td><input type="text" value="<?php echo $talent_cost; ?>" name="costotalents" id="costotalents" /></td></tr>
<tr><td>Moltiplicatore costi all'upgrade</td><td><input type="text" value="<?php echo $multiplier; ?>" name="multiplier" id="multiplier" /></td></tr>
<tr><td>!!Cancella permanentemente questa abilità</td><td><input type="checkbox" name="delete" value="1" /></td></tr>
<tr><td>&nbsp;</td><td><input type="submit" value="Salva"/></td><td></td><td></td></tr>
</table>
</div>
<div class="floatleft roundcorner clearborder panel_bg" style="padding:10px;margin-left:20px;width:37%;">
<table>
<tr><td>Bonus soldi (vale solo all'iscrizione)</td><td><input type="text" value="<?php echo $moneybonus; ?>" name="moneyBonus" style="width:70px" /></td><td>&nbsp;</td></tr>
<tr><td>Stat</td><td><span class="help" help="Indica il bonus da sommare alla statistica del Pg">Bonus fornito</span></td><td><span class="help" help="Se selezionato, aggiunge al tiro del dado realtivo a questa abilità il corrispettivo valore di statistica del Pg">Bonus Stat.</span></td></tr>
<?php
$i=0;
foreach($name_carStatic as $k=>$v){
  
  if(isset($oldStatB[$k])){
    $valS=$oldStatB[$k];
  }else{ $valS=0; }
  
  if(isset($oldStatBind[$k]) && intval($oldStatBind[$k])>0){
  	$stBind="selected=\"selected\"";
  }else{
	$stBind=""; 
  } 
  
  $i++;
    
  echo "<tr><td>{$k}</td><td class=\"centertxt\"><input type=\"text\" value=\"$valS\" name=\"statBonus[]\" style=\"width:20px\" /></td><td class=\"centertxt\"><select name=\"statBind[]\"><option value=\"0\">No</option><option value=\"1\" $stBind >Si</option></select></td></tr>";
}
foreach($name_car as $k=>$v){
    
  if(isset($oldStatB[$k])){
    $valS=$oldStatB[$k];
  }else{ $valS=0; }
  
  if(isset($oldStatBind[$k]) && intval($oldStatBind[$k])>0){
  	$stBind="selected=\"selected\"";
  }else{
	$stBind=""; 
  } 
  
  $i++;
    
  echo "<tr><td>{$k}</td><td class=\"centertxt\"><input type=\"text\" value=\"$valS\" name=\"statBonus[]\" style=\"width:20px\" /></td><td class=\"centertxt\"><select name=\"statBind[]\"><option value=\"0\">No</option><option value=\"1\" $stBind >Si</option></select></td></tr>";
}

?>
</table>
</div>
</form>
<?php
}
else {
?>
<h2>Gestione delle Abilità</h2>
<ul>
<li><a href="admin_ability.php?n=1">Crea nuova</a></li>
<li>Modifica esistente
  <ul>
  <?php
  
  
  foreach($list->getList() as $k=>$v){
    
    //$v=new Ability();
    
    echo "<li><a href=\"admin_ability.php?e={$v->getId()}\"> » {$v->getName()}</a></li>\n";
    
  }
  ?>
  </ul>
</li>
</ul>
<?php
}
?>
</body>
</html>