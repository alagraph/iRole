<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/item_lib.php");
require_once("libs/group_lib.php");

if(!isset($_SESSION))
{
session_start();
} 

logged();

$MyChar_obj=new Character($_SESSION['char_id']);
$MyChar_obj->parseFromDb();

$buyRslt="";


$_SESSION['modlevel']=$MyChar_obj->Account()->getModLevel();
if ($admin_edit_items_required>$_SESSION['modlevel']){
	$onlypublic=true;
}else{
	$onlypublic=false;
}


if(isset($_REQUEST['buy'])){
	
	$buyItem=new Item(null,$_REQUEST['buy']);
	
	$buyItem->readFromDb();
	
	if($buyItem->isBuyable($MyChar_obj)){
		$buyItem->BuyItem($MyChar_obj);
		
		$buyRslt="Oggetto: {$buyItem->getName()} acquistato.";
	}else{
		$buyRslt="Impossibile acquistare: {$buyItem->getName()}";
	}
	
	
}




?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Forum</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">
$(document).ready(function(){
          
  $( ".buttonify" ).button();
  
});
</script>
</head>

<body>
<?php

echo "<p>$buyRslt</p>";


//se c'è la category e se è un tipo di oggetto valido, mostro l'elenco
if(isset($_REQUEST['cat']) && array_key_exists($_REQUEST['cat'],$items_types_array)){
	
	$itemList=new ItemList();
	
	
	$itemList->populateList($_REQUEST['cat'],null,null,null,$onlypublic);
	
	echo "<table class=\"itemshop\">
			<tr>
				<th>Oggetto</th>
				<th class=\"shopimg\">Immagine</th>
				<th>Costo</th>
			</tr>";
	
	foreach($itemList->getList() as $k=>$v){
		
		//$v=new Item();
		
		$costArr=$v->getCostArray();
		$moneyC="";
		$pxC="";
		
		if($costArr[0]>0)
			$moneyC=$costArr[0]." ".$valuta_plurale;
			
		if($costArr[1]>0)
			$pxC=$costArr[1]." px";
			
		if($v->isBuyable($MyChar_obj))
			$buyable="<a href=\"item_shop.php?buy={$v->getId()}\" class=\"buttonify\">Acquista</a>";
		else
			$buyable="";
		
		
		echo "<tr>
				<td class=\"objN\"><span>{$v->getName()}</span><p class=\"desc\">{$v->getDescription()}</p></td>
				<td><img src=\"{$v->getImage()}\" class=\"shopimg\"/></td>
				<td><div>$moneyC $pxC</div><div>$buyable</div></td>
			  </tr>";
		
	}
	echo "</table>";
	
}else{
//altrimenti mostro le categorie di oggetti acquistabili
	echo "<ul>";
	foreach($items_types_array as $k => $v){
		
		$itemList=new ItemList();
		$itemList->populateList($k,null,null,null,$onlypublic);
		
		echo "<li><a href=\"item_shop.php?cat=$k\">$v ({$itemList->getSize()})</a></li>";
		
	}
	echo "</ul>";
	
	
}



?>
	
</body>

</html>