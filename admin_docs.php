<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/docs_lib.php");

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

$list=new DocList();


?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Gestione Documenti</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>
<?php

if (isset($_REQUEST['s'])){
  //salvo
  
 if(isset($_REQUEST['id']) && !empty($_REQUEST['id']))
 	$lookId=$_REQUEST['id'];
 else
 	$lookId=null;
  
  $addDoc=new Document(null,$lookId);
  $addDoc->readFromDb();
  
  if($addDoc->getEditLevel()>$_SESSION['modlevel']){
  	echo "Spiacente, permessi insufficienti per modificare il documento.";
  }elseif($_REQUEST['delete']==1){
    $addDoc->deleteDoc($MyChar_obj->Account());
    echo "Documento Cancellato";
  }else{
    	
    if($addDoc->editDoc($_REQUEST['nome'], $_REQUEST['content'], $MyChar_obj,$_REQUEST['edit_level']) ){
    	echo "Documento Creato/Modificato";
    	
    }
	
  }
  
}


if (isset($_REQUEST['n'])){
  //creazione nuovo documento

  $doc_name="";
  $doc_content="";
  $doc_editTime="";
  $doc_editBy="";
  $doc_editLevel="";
  
  $flag=true; 

}
elseif (isset($_REQUEST['e']) && $_REQUEST['e']>0){
  //modifica Documento		
  	
  $old_doc=new Document(null,$_REQUEST['e']);
  $old_doc->readFromDb();
  
  $doc_id=$old_doc->getId();
  $doc_name=$old_doc->getName();
  $doc_content=$old_doc->getContent();
  $doc_editTime=$old_doc->getLastEditTime();
  $doc_editBy=$old_doc->getLastEditBy();
  $doc_editLevel=$old_doc->getEditLevel();
  
  $flag=true;
}

if($flag){
  
  
  
  foreach ($acc_level_array as $key=>$value){
    
    $select="";
    if($doc_editLevel==$key) $select="selected=\"select\" ";
      
    $accLst.="<option value=\"$key\" $select >$value</option>\n";
  }
  


?>
<h1>Modifica/Creazione Documento</h1>
<form action="admin_docs.php?s=1" method="post">
<input type="hidden" value="<?php echo $doc_id; ?>" name="id" id="id" />
<div class="width90 center">
<table>
<tr><td width="10%">Nome del Documento</td><td><input type="text" name="nome" value="<?php echo $doc_name; ?>" /></td></tr>
<tr><td>Contenuto</td><td><textarea name="content" style="width:90%" rows="30" ><?php echo $doc_content; ?></textarea></td></tr>
<tr><td>Livello richiesto per modificare</td><td><select name="edit_level"><?php echo $accLst; ?></select></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td>!!Cancella permanentemente questo Documento</td><td><input type="checkbox" name="delete" value="1" /></td></tr>
<tr><td><a href="admin_docs.php">Indietro</a></td><td><input type="submit" value="Salva"/></td><td></td><td></td></tr>
</table>
</div>
</form>
<?php
}
else {
	$list->populateList();
	
?>
<h1>Gestione della Documentazione</h1>
<ul>
<li><a href="admin_docs.php?n=1">Crea nuovo Documento</a></li>
<li>Modifica esistente
  <ul>
  <?php
  foreach($list->getList() as $k=>$v){
  	
	if($v->getEditLevel()>$_SESSION['modlevel'])
		continue;
    
    echo "<li><a href=\"admin_docs.php?e={$v->getId()}\">{$v->getName()} (id: {$v->getId()} )</a></li>\n";
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