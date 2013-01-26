<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/chat_lib.php");
require_once("libs/quest_lib.php");

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

if ($admin_add_px_required>$_SESSION['modlevel'] && $MyChar_obj->getMasterLevel() < $master_add_px_required){
  echo "Accesso negato, permessi insufficienti.";
  exit();
}


$ptsAvb="";
for ($i=-15; $i <= 15; $i++) {
		
	
	$sel= $i==0 ? "selected=\"selected\"" : "";
	
	$ptsAvb.="<option $sel>$i</option>";	
}



if (isset($_REQUEST["save"])){ //se mi arriva il save sto creando o editando.

  //per prima cosa leggo la quest
   
  $quest_id=$_REQUEST['id_Q'];
  $quest_name=$_REQUEST['name_Q'];
  $quest_location=$_REQUEST['location_Q'];
  $quest_duration=$_REQUEST['duration_Q'];
  
  $savArr=array();
  $savArr['name']=$quest_name;
  $savArr['duration']=$quest_duration;
  $savArr['location']=$quest_location;
  
  if(isset($quest_id) && $quest_id>0){ //se è settato l'id lo carico
    $newQ=new Quest(null,$quest_id);
    $newQ->readFromDb();
    $newQ->parse($savArr);
    
    //controllo se posso editare la quest (devo averla fatta io, o devo essere admin)
    if($newQ->getMaster()!=$MyChar_obj->getCharId() && $admin_add_px_required>$_SESSION['modlevel'] )
		exit("Non puoi editare le quest altrui.");
    
    if(isset($_REQUEST['delQ'])){
    	$newQ->delete();
    	exit("Quest Cancellata");    	
    }
	
	if(isset($_REQUEST['delE'])){
		$newQ->loadElements();
		foreach($newQ->getElements() as $k=>$v){
			if($v->getId()==$_REQUEST['delE']){
				$v->delete();	
				exit("Elemento Cancellato");
			} 
		}
	}
	
  }
  else{
    $newQ= new Quest($savArr);
  }
  
  //in ogni caso salvo la quest (a patto che abbia un nome)
  if ($savArr['name']!=''){
    $newQ->writeToDb();
  }else{
    exit("Devi specificare un nome valido per la quest.");
  }
  
  
  //arrivato qui ho di sicuro un id del gruppo...se non ce l'ho mi fermo
  if ($newQ->getId()<1){
    exit("Errore nell'accesso alla quest.");
  }
  
  //scorro tutto l'array delle richieste.
  if (isset($_REQUEST['element_name_Q'])){
    foreach ($_REQUEST['element_name_Q'] as $key => $value) {
      
      $element_id=$_REQUEST["element_id_Q"][$key];
      
      $px_toSubb=0;
      if(isset($element_id) && $element_id!=""){
        $oldElem=new QuestElement(null,$element_id);
        $oldElem->readFromDb();
        
        $px_toSubb=$oldElem->getPx();
        
      }
      
      $element_name=$_REQUEST["element_name_Q"][$key];
      $element_px=$_REQUEST["element_px_Q"][$key];
      $element_note=$_REQUEST['element_note_Q'][$key];
      
      $char=new Character(null,$element_name);
      $char->checkExistance();
      if(!$char->exists()) {
        $rsltMsg =  "Personaggio $element_name Inesistente";
        continue;        
      }
      
      $arrE=array();
      $arrE['element_id']=$char->getCharId();
      $arrE['element_px']=$element_px;
      $arrE['element_note']=$element_note;
      $arrE['id_quest']=$newQ->getId();
      
      
      
      if(isset($element_id) && $element_id>0){ //se l'id è settato è una modifica
        $newE=new QuestElement($arrE,$element_id);  
      }else{
         $newE=new QuestElement($arrE); 
      }

      $newE->setExpDiff($element_px-$px_toSubb);

      //in ogni caso salvo l'elemento
      $newE->writeToDb();
      
    }
  }
  
  exit("Salvataggio Completato.<br /><a href=\"admin_exp.php\">Torna alla gestione quest</a>");
}

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title><?php echo $nome_land;?></title>
<? include_headers(null); ?>
<script type="text/javascript">

$(document).ready(function(){

  $('#nelem_Q').change(function() {
        $(".added_row").remove();  
	$("#nelem_Q option:selected").each(function () {
    
	    for(i=0;i<parseInt($(this).text());i++){
	      $('#elements_Q').append('<div class="added_row">nome: <input type="text" name="element_name_Q[]" class="element_name_Q" />punti: <select name="element_px_Q[]"><? echo $ptsAvb; ?></select>note: <input type="text" name="element_note_Q[]" /></div>');
	    }
	     
		$(".element_name_Q").autocomplete({
				source: "char_list.php",
				minLength: 2,
				delay: 200
		});
  
    });
  });

   $(".del").click(function(){
   	
   	var elem=$(this).attr("elem");
   	var quest=$(this).attr("quest");
   	
   	if(typeof quest == 'undefined' || quest=="")
   		return false;
   	
   	if(typeof elem != 'undefined' && elem!=""){
   		if(!confirm('Sei sicuro di voler rimuovere questo partecipante dalla quest?'))
      		return false;
	   	 $.post("admin_exp.php?save=1",{delE:elem,id_Q:quest},function(data){
	   	 	alert(data);
	   	 	$("#elemId_"+elem).hide();
	   	 });
	
	}else{
		if(!confirm('Sei sicuro di voler rimuovere la quest e tutti i partecipanti?'))
      		return false;
   		$.post("admin_exp.php?save=1",{delQ:'1',id_Q:quest},function(data){
	   	 	alert(data);
	   	 	$("#questId_"+quest).hide();
	   	 });
   	}
   	
   	return false;
   	
   }); 
          
});
</script>

</head>
<body>
<?php

echo $rsltMsg;

if (isset($_REQUEST["new"])){ //voglio creare
?>
<h1>Creazione Quest</h1>
<a href="admin_exp.php">« Back</a>
<div class="width90 roundcorner clearborder panel_bg center">
<form id="create_Q" name="create_Q" action="admin_exp.php?save=1" method="post">
	<table>
		<tr><td>Nome Quest</td><td><input type="text" name="name_Q" id="name_Q" /></td></tr>
		<tr><td>Locazione</td><td><select name="location_Q" id="location_Q">
<?php
$roomsList=new ChatList();
$roomsList->readFromDb();

foreach ($roomsList->getRooms() as $key => $value) {

  echo "<option value=\"$key\" $sel >{$value->getName()}</option>\n";
}
?>
</select></td></tr>
		<tr><td>Durata Quest</td><td><input type="text" name="duration_Q" id="duration_Q" /></td></tr>
		<tr><td>Numero Presenti</td><td><select id="nelem_Q">
  <?php
  for($i=0;$i<30;$i++)
    echo "<option>$i</option>\n";
  ?>
  </select></td></tr>
	</table>

<div id="elements_Q"></div>
<div class="centertxt">
	<input type="submit" value="Salva" />
</div>
</form> 
</div> 
<?php  
}elseif(isset($_REQUEST['edit'])){
  
  $editQ=new Quest(null,$_REQUEST['edit']);
  $editQ->readFromDb();
  $editQ->loadElements();
?>
<h1>Modifica Quest</h1>
<a href="admin_exp.php">« Back</a>
<div class="width90 roundcorner clearborder panel_bg center">
<form id="edit_Q" name="edit_Q" action="admin_exp.php?save=1" method="post">
<input type="hidden" name="id_Q" value="<?php echo $editQ->getId() ?>" />

<table>
		<tr><td>Nome Quest</td><td><input type="text" name="name_Q" id="name_Q" value="<?php echo $editQ->getName(); ?>" /> (id: <?php echo $editQ->getId() ?>)</td></tr>
		<tr><td>Locazione</td><td>
			<select name="location_Q" id="location_Q" >
			<?php
			$roomsList=new ChatList();
			$roomsList->readFromDb();
			
			foreach ($roomsList->getRooms() as $key => $value) {
			  
			  $sel="";
			  if($editQ->getLocation()==$key)
			    $sel="selected=\"selected\"";
			  
			  echo "<option value=\"$key\" $sel >{$value->getName()}</option>\n";
			}
			?>
			</select>
		</td></tr>
		<tr><td>Durata Quest</td><td><input type="text" name="duration_Q" id="duration_Q" value="<?php echo $editQ->getDuration(); ?>" /></td></tr>
	</table>

<div>Lista Presenti
<?php

$elemList=$editQ->getElements();

foreach ($elemList as $key => $value) {
    
  echo "<div class=\"element\" id=\"elemId_{$value->getId()}\">
          <a href='#' class=\"del\" elem=\"{$value->getId()}\" quest=\"{$editQ->getId()}\"><img src=\"images/icons/delete.png\" alt='cancella' title='cancella'/></a> 
          <input type=\"hidden\" name=\"element_id_Q[]\" value=\"{$value->getId()}\" />
          nome : {$value->getName()}<input type=\"hidden\" name=\"element_name_Q[]\" value=\"{$value->getName()}\" />
          punti: <input type=\"text\" name=\"element_px_Q[]\" value=\"{$value->getPx()}\" />
          note: <input type=\"text\" name=\"element_note_Q[]\" value=\"{$value->getNote()}\" />
        </div>";
  
}
?>
</div>
<div>Aggiungi presenti: 
  <select id="nelem_Q">
  <?php
  for($i=0;$i<30;$i++)
    echo "<option>$i</option>\n";
  ?>
  </select>
</div>
<div id="elements_Q"></div>
<div class="centertxt">
<input type="submit" value="Salva" />
</div>
</form>
</div>
<?php
}
else{
?>
<h2>Gestione delle Quest</h2>
<ul>
<li><a href="admin_exp.php?new=1">Crea Nuova Quest</a></li>
<li>Vecchie quest:</li>
<?php
  
  $questList=new QuestList($_SESSION['char_id']);
  $questList->readList();
  $glisted=$questList->GetList();
  
  foreach ($glisted as $k => $v){
    
    echo "<div id=\"questId_{$v->getId()}\">{$v->getName()} <a href=\"admin_exp.php?edit={$v->getId()}\"><img src=\"images/icons/pencil_16.png\" alt='modifica' title='modifica' /></a> <a href='#' class=\"del\" quest=\"{$v->getId()}\"><img src='images/icons/delete.png' alt='cancella' title='cancella'/></a></div>";
    
  }

?>
</ul>
<?php
}
?>
</body>
</html>
