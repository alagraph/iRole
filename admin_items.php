<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("upload_lib.php");
require_once("libs/character_lib.php");
require_once("libs/item_lib.php");

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

if ($admin_edit_items_required>$_SESSION['modlevel']){
  echo "Accesso negato, permessi insufficienti.";
  exit();
}

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Gestione Oggetti</title>
<? include_headers(null); ?>
<script type="text/javascript">
$(function() {
  
  $(".buttonset").buttonset();

          
  var FormOptions = { 
        target: '#content',
        success:    function() { 
        	//alert('Stanza Aggiornata,\n ricarica per vedere le modifiche');
    	} 
    }; 
   $('form.special').ajaxForm(FormOptions);
   
   
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
  
  $tfile=$_FILES['item_img']['tmp_name'];
  $pp = pathinfo($_FILES['item_img']['name']);
  $tfilext=$pp['extension'];
  
  $arr['id']=$_REQUEST['id'];
  $arr['type']=$_REQUEST['tipo'];
  $arr['public']=$_REQUEST['pubblico'];
  $arr['name']=$_REQUEST['nome'];
  $arr['creator']=$_SESSION['char_id'];
  $arr['description']=$_REQUEST['descrizione'];
  $arr['binds_to_groupelement']='';
  if (isset($_REQUEST['gruppo']))
    $arr['binds_to_groupelement']=implode(",",$_REQUEST['gruppo']);
  $arr['autoadd_groupjoin']=$_REQUEST['autoadd'];
  $arr['autoremove_groupleave']=$_REQUEST['autoremove'];
  $arr['cost']= strval(0 + intval($_REQUEST['costosoldi'])) ." ". strval(0 + intval($_REQUEST['costopx']));
  $arr['stats_bonus']=implode(' ',$_REQUEST['statBonus']);
  
  
  $addItem=new Item($arr);
  
  
  if($_REQUEST['delete']==1){
    $addItem->deleteItem();
    echo "Oggetto Cancellato";
  }else{
    $addItem->writeToDb();
	if(isset($tfile) && isset($tfilext) && $tfile!='' && $tfilext!='')
  		$addItem->storeImage($tfile, $tfilext);
    echo "Oggetto Creato";
  }
  
}

$list=new ItemList(null);
$list->populateList();

$flag=false;
if (isset($_REQUEST['n'])){
  //creazione nuovo oggetto
  $flag=true;
  $old_grp=array();
  $px_cost="";
  $money_cost="";
  $descript="";
  $name="";
  $old_id="";
  $old_type=0;
  $old_public=1;
  $autoadd=0;
  $autoremove=0;
  $old_img="";
  
  $oldStatB=array();
  
}
elseif (isset($_REQUEST['e']) && $_REQUEST['e']>0){
  $flag=true;
  
  $old_item=new Item(null,$_REQUEST['e']);
  $old_item->readFromDb();
  
  $old_grp=explode(',',$old_item->getBindToGroups());
  
  $oldIt_cost=explode(' ',$old_item->getCost());
  $px_cost=$oldIt_cost[1];
  $money_cost=$oldIt_cost[0];
  $descript=$old_item->getDescription();
  $name=$old_item->getName();
  $old_id=$old_item->getId();
  $old_type=$old_item->getType();
  $old_public=$old_item->getPublic();
  $autoadd=$old_item->getAutoAdd();
  $autoremove=$old_item->getAutoRemove();
  $oldStatB=$old_item->getStatsBonusArray();
  $old_img="<img src=\"{$old_item->getImage()}\" style=\"width:200px\"/>";
  
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
  
  
  
  foreach ($items_types_array as $key=>$value){
    
    $select="";
    if($old_type==$key) $select="selected=\"select\" ";
      
    $typLst.="<option value=\"$key\" $select >$value</option>\n";
  }

  $ckdAdd="";
  if($autoadd==1)
    $ckdAdd="checked=\"checked\"";
    
  $ckdRmv="";
  if($autoremove==1)
    $ckdRmv="checked=\"checked\"";
  
  $ckdPbl="";
  if($old_public==1){
    	$ckdPblY="checked=\"checked\"";
	  	$ckdPblN="";  
  }else{
    	$ckdPblN="checked=\"checked\"";
	  	$ckdPblY="";  
  }
  	
?>
<h2>Modifica Oggetto</h2>
<a href="admin_items.php">« Back</a>
<form action="admin_items.php?s=1" enctype="multipart/form-data" method="post" class="special">
<input type="hidden" value="<?php echo $old_id; ?>" name="id" id="id" />
<div class="floatleft roundcorner clearborder panel_bg" style="width:55%;">
<table>
<tr><td style="width:50%">Tipo</td><td><select name="tipo"><?php echo $typLst; ?></select></td></tr>
<tr><td>Disponibile al pubblico</td><td class="buttonset"><input type="radio" id="pubY" name="pubblico"  <? echo $ckdPblY; ?> value="1"/><input type="radio" id="pubN" name="pubblico"  <? echo $ckdPblN; ?> value="0"/><label for="pubY">Si</label><label for="pubN">No</label></td></tr>
<tr><td>Nome</td><td><input type="text" value="<?php echo $name; ?>" name="nome" id="nome" style="width: 95%;" /></td></tr>
<tr><td>Descrizione</td><td><textarea rows="10" name="descrizione" style="width: 95%;" ><?php echo $descript; ?></textarea></td></tr>
<tr><td>Immagine</td><td><?php echo $old_img; ?><input type="file" name="item_img" id="item_img" /></td></tr>
<tr><td>Vincolata a gruppo</td><td><select style="width:200px;" multiple="multiple" size="7" name="gruppo[]" ><?php echo $grpLst; ?></select></td></tr>
<tr><td>Assegna automaticamente all'entrata nel gruppo</td><td><input type="checkbox" name="autoadd" <?php echo $ckdAdd; ?> value="1" /></td></tr>
<tr><td>Rimuovi all'uscita dal gruppo</td><td><input type="checkbox" name="autoremove"  <?php echo $ckdRmv; ?> value="1" /></td></tr>
<tr><td>Costo px</td><td><input type="text" value="<?php echo $px_cost; ?>" name="costopx" id="costopx" /></td></tr>
<tr><td>Costo soldi</td><td><input type="text" value="<?php echo $money_cost; ?>" name="costosoldi" id="costosoldi" /></td></tr>
<tr><td>!!Cancella permanentemente questo oggetto</td><td><input type="checkbox" name="delete" value="1" /></td></tr>
<tr><td>&nbsp;</td><td><input type="submit" value="Salva"/></td><td></td><td></td></tr>
</table>
</div>
<div class="floatleft roundcorner clearborder panel_bg" style="padding:10px;margin-left:20px;width:37%;">
<table>
<tr><td>Stat</td><td><span class="help" help="Indica il bonus da sommare alla statistica del Pg">Bonus fornito</span></td></tr>
<?php
$i=0;
foreach($name_carStatic as $k=>$v){
  
  if(isset($oldStatB[$k])){
    $valS=$oldStatB[$k];
  }else{ $valS=0; }
  $i++;
    
  echo "<tr><td>{$k}</td><td><input type=\"text\" value=\"$valS\" name=\"statBonus[]\" style=\"width:20px\" /></td></tr>";
}
foreach($name_car as $k=>$v){
    
  if(isset($oldStatB[$k])){
    $valS=$oldStatB[$k];
  }else{ $valS=0; }
  $i++;
    
  echo "<tr><td>{$k}</td><td><input type=\"text\" value=\"$valS\" name=\"statBonus[]\" style=\"width:20px\" /></td></tr>";
}

?>
</table>
</div>
</form>
<?php
}
else {
?>
<h2>Gestione degli Oggetti</h2>
<ul>
<li><a href="admin_items.php?n=1">Crea nuovo</a></li>
<li>Modifica esistente
  <ul>
  <?php
  
  
  foreach($list->getList() as $k=>$v){
    
    //$v=new Ability();
    
    echo "<li><a href=\"admin_items.php?e={$v->getId()}\"> » {$v->getName()}</a></li>\n";
    
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