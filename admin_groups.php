<?php
/*  I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("upload_lib.php");
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

$_SESSION['modlevel']=$MyChar_obj->Account()->getModLevel();

if ($admin_groups_level_required>$_SESSION['modlevel']){
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
<script type="text/javascript">


$(document).ready(function(){
	
	
	var FormOptions = { 
				success:    function(data) { 
					
					alert('Gruppo Aggiornato');
					
			} 
		}; 
	 
	$('form.special').ajaxForm(FormOptions);
	 
	 
	$(".toggle_newSyBox").click(function(){
				$(this).next(".Sy_upload").toggle();
	});

	$(".cancElement").click(function(){
		if(!confirm('Cancellare la carica e rimuoverla da tutti i pg a cui è assegnata.\nSei sicuro di voler cancellare?')){
			return false;
		}else{

			//post the ajax request
			$.post("admin_groups.php",  { element_delete_nG: $(this).attr("ref_elem") },function(data){
				//remove the element
				$(this).remove();

				//show the message

			});
		
		}
	});
	 
	
	$('#nelem_nG').change(function() {
				$(".added_row").remove();  
	$("select option:selected").each(function () {
		
		for(i=0;i<parseInt($(this).text());i++){
			$('#elements_nG').append('<div class="added_row">nome carica: <input type="text" name="element_name_nG[]" /> salario giorn.: <input type="text" name="element_salary_nG[]" style="width:50px;" /> group admin: <select name="element_admin_nG[]"><option value="0">No</option><option value="1">Si</option></select> simbolo: <input type="file" name="element_symbol_nG[]" /></div>');
		}
		});
	
	});
	

			
					
});
</script>

</head>
<body>
<?php

if(isset($_REQUEST['element_delete_nG'])){

	//get the chars having that group element and remove them that element
	$delElem = new GroupElement(null,$_REQUEST['element_delete_nG']);

	if($delElem->deleteElement()){
		echo "Elemento cancellato correttamente";
	}else{
		echo "Impossibile cancellare Elemento";
	}


	exit();
}

if (isset($_REQUEST['del'])){
	
	$delG=new Group(null,$_REQUEST['del']);
	$delG->readFromDb();
	
	if($delG->deleteGroup($MyChar_obj->Account())){
		echo "Gruppo {$delG->getName} Cancellato!";
	}else{
		echo "Impossibile Cancellare il gruppo";
	}
		
	
}elseif(isset($_REQUEST['undel'])){
		
	$delG=new Group(null,$_REQUEST['undel']);
	$delG->readFromDb();
	
	if($delG->undeleteGroup($MyChar_obj->Account())){
		echo "Gruppo {$delG->getName} Ripristinato!";
	}else{
		echo "Impossibile Ripristinare il gruppo";
	}
	
}elseif (isset($_REQUEST["save"])){ //se mi arriva il save sto creando o editando.

	//per prima cosa lavoro sul gruppo
	
	$group_id=$_REQUEST['id_nG'];
	$group_name=$_REQUEST['name_nG'];
	$group_type=$_REQUEST['type_nG'];
	$group_website=$_REQUEST['site_nG'];
	$tfile=$_FILES['logo_nG']['tmp_name'];
	$pp = pathinfo($_FILES['logo_nG']['name']);
	$tfilext=$pp['extension'];
	
	$savArr=array();
	$savArr['name']=$group_name;
	$savArr['type']=$group_type;
	$savArr['website']=$group_website;
	$savArr['statute']=$_REQUEST['statute_nG'];
	
	if(isset($group_id) && $group_id>0){ //se è settato l'id lo carico
		$newG=new Group(null,$group_id);
		$newG->readFromDb();
		$newG->parse($savArr);
	}
	else{
		$newG= new Group($savArr);
	}
	
	//in ogni caso salvo il gruppo
	$newG->writeToDb();
	
	if (isset($tfile) && $tfile!=''){ //se sto caricando un file devo salvarlo
		$newG->storeImage($tfile,$tfilext);
	}
	
	//arrivato qui ho di sicuro un id del gruppo...se non ce l'ho mi fermo
	if ($newG->getId()<1){
		echo "Errore sul gruppo.";
		exit();
	}
	
	//scorro tutto l'array delle richieste.
	if (isset($_REQUEST['element_name_nG'])){
		foreach ($_REQUEST['element_name_nG'] as $key => $value) {
			
			$element_id=$_REQUEST["element_id_nG"][$key];
			$element_name=$_REQUEST["element_name_nG"][$key];
		$element_salary=$_REQUEST["element_salary_nG"][$key];
			$element_admin=$_REQUEST["element_admin_nG"][$key];
		$element_old_img=$_REQUEST["element_oldsymbol_nG"][$key];
			$element_image=$_FILES['element_symbol_nG']['tmp_name'][$key];
			$ppe = pathinfo($_FILES['element_symbol_nG']['name'][$key]);
			$element_imageext=$ppe['extension'];
			
			$arrE=array();
			$arrE['element_name']=$element_name;
		$arrE['element_image']=$element_old_img;
		$arrE['salary']=$element_salary;
			$arrE['group_admin']=$element_admin;
			$arrE['id_group']=$newG->getId();
			
			
			
			if(isset($element_id) && $element_id>0){ //se l'id è settato è una modifica
				$newE=new GroupElement($arrE,$element_id); 
			}else{                                   //altrimenti è una creazione
				
				if(!isset($element_name) || $element_name=='')
			continue;
				
				//in tal caso devo forzare l'immagine alla stessa del gruppo
				$arrE['element_image']=$newG->getLogo();
				$newE=new GroupElement($arrE);
				
			}
			
			//in ogni caso salvo l'elemento
			$newE->writeToDb();
			
			if (isset($element_image) && $element_image!=''){ //se sto caricando un file devo salvarlo
				echo "carico file: $element_image";
				$newE->storeImage($element_image,$element_imageext);
			}
			
		}
	}
	
	echo "Salvataggio Completato.<br /><a href=\"admin_groups.php\">Torna alla gestione gruppi</a>";
	exit();
}




if (isset($_REQUEST["new"])){ //voglio creare
	
?>
<h2>Crezione nuovo gruppo</h2>
<form id="create_nG" name="create_nG" action="admin_groups.php?new=1&save=1" enctype="multipart/form-data" method="post" class="special">
<div style="float:left;width:50%;">
<div>nome: <input type="text" name="name_nG" id="name_nG" /></div>
<div>tipo: <select name="type_nG" id="type_nG">
<?php
foreach ($groups_name_array as $key => $value) {
	echo "<option value=\"$key\">$value</option>\n";
}
?></select>
</div>
<div>sito: <input type="text" name="site_nG" id="site_nG" /></div>
<div>logo: <input type="file" name="logo_nG" id="logo_nG" /></div>
</div>
<div style="float:right;width:50%;">
	Statuto:<br />
	<textarea placeholder="Inserisci lo statuto per il gruppo" name="statute_nG" style="width:300px; height: 100px;"></textarea>
</div>
<div class="clearboth">numero cariche: 
	<select id="nelem_nG">
	<?php
	for($i=0;$i<30;$i++)
		echo "<option>$i</option>\n";
	?>
	</select>
</div>
<div id="elements_nG"></div>

<input type="submit" value="Salva" />
</form>  
<?php  
}elseif(isset($_REQUEST['edit'])){
	
	$editG=new Group(null,$_REQUEST['edit']);
	$editG->readFromDb();
	$editG->loadElements();
?>
<h2>Modifica gruppo</h2>
<form id="mod_nG" name="mod_nG" action="admin_groups.php?edit=1&save=1" enctype="multipart/form-data" method="post" class="special">
<div style="float:left;width:50%;">
<input type="hidden" name="id_nG" value="<?php echo $editG->getId(); ?>" />
<div>nome: <input type="text" name="name_nG" id="name_nG" value="<?php echo $editG->getName(); ?>" /></div>
<div>tipo: <select name="type_nG" id="type_nG">
<?php

foreach ($groups_name_array as $key => $value) {
	
	$sel=null;
	
	if ($editG->getTypeN()==$key)
		$sel="selected=\"selected\" ";
		
	echo "<option value=\"$key\" $sel >$value</option>\n";
}
?></select>
</div>
<div>sito: <input type="text" name="site_nG" id="site_nG" value="<?php echo $editG->getSite(); ?>" /></div>
<div>logo: <?echo $editG->getLogo(true); ?> <label for="changeLogo">Cambia Logo</label><input type="checkbox" id="changeLogo" class="toggle_newSyBox" value="1"/><span class="Sy_upload" style="display:none;" ><input type="file" name="logo_nG" id="logo_nG" /></span></div>
</div>
<div style="float:right;width:50%;">
	Statuto:<br />
	<textarea placeholder="Inserisci lo statuto per il gruppo" name="statute_nG" style="width:300px; height: 100px;"><?php echo $editG->getStatute(); ?></textarea>
</div>
<div class="clearboth">cariche:
<?php

$elemList=$editG->getElements();

foreach ($elemList as $key => $value) {
	$isAdm=null;
	if($value->getAdmin()==1)
		$isAdm="selected=\"selected\" ";
		
	echo "<div class=\"element\">
					<input type=\"hidden\" name=\"element_id_nG[]\" value=\"{$value->getId()}\" />
					nome carica: <input type=\"text\" name=\"element_name_nG[]\" value=\"{$value->getName()}\" />
					salario giorn.: <input type=\"text\" name=\"element_salary_nG[]\" style=\"width:50px;\" value=\"{$value->getSalary()}\" />
					group admin: <select name=\"element_admin_nG[]\"><option value=\"0\">No</option><option value=\"1\" $isAdm >Si</option></select>
					elimina: <a class=\"cancElement\" href=\"#\" ref_elem=\"{$value->getId()}\"><img src=\"images/icons/delete.png\" border=\"0\" title=\"Cancella\" alt=\"Cancella\" /></a>
					{$value->getImage(true)}<input type=\"hidden\" name=\"element_oldsymbol_nG[]\" value=\"{$value->getImage()}\" /> <label for=\"changeSy_{$value->getId()}\">Cambia Simbolo</label><input type=\"checkbox\" id=\"changeSy_{$value->getId()}\" class=\"toggle_newSyBox\" value=\"1\"/><span class=\"Sy_upload\" style=\"display:none;\" ><input type=\"file\" name=\"element_symbol_nG[]\"/></span>
				</div>";
	
}
?>
</div>

<div>Aggiungi cariche: 
	<select id="nelem_nG">
	<?php
	for($i=0;$i<30;$i++)
		echo "<option>$i</option>\n";
	?>
	</select>
</div>
<div id="elements_nG"></div>

<input type="submit" value="Salva" />
</form>  
<?php
}
else{
?>
<h2>Gestione dei gruppi</h2>
<p><a href="admin_groups.php?new=1">CREA GRUPPO</a></p>

<p>MODIFICA GRUPPI</p>
<ul>
<?php
foreach ($groups_name_array as $key => $value) {
	echo "<li>$value:</li>";
	
	$groupList=new GroupList($key);
	$glisted=$groupList->GetList();
	
	foreach ($glisted as $k => $v){
		
		echo "{$glisted[$k]->getName()} <a href=\"admin_groups.php?edit={$v->getId()}\">[modifica]</a> <a href=\"admin_groups.php?del={$v->getId()}\">[cancella]</a><br />";
		
	}
	
		
}

?>
</ul>

<p>RIPRISTINA CANCELLATI</p>
<ul>
<?php
foreach ($groups_name_array as $key => $value) {
	echo "<li>$value:</li>";
	
	$groupList=new GroupList($key,1);
	$glisted=$groupList->GetList();
	
	foreach ($glisted as $k => $v){
		
		echo "{$glisted[$k]->getName()} <a href=\"admin_groups.php?undel={$v->getId()}\">[ripristina]</a><br />";
		
	}
	
		
}

?>
</ul>

<?php
}
?>
</body>
</html>
