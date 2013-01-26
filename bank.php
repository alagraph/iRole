<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");

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

$modlevel=$MyChar_obj->Account()->getModLevel();
$_SESSION['modlevel']=$modlevel;


$wtRslt="";
if((isset($_REQUEST['wt']))){
	
	if($_REQUEST['wt']=="all"){
		
		foreach($MyChar_obj->getGroups() as $k=>$v){
			$MyChar_obj->salaryWithdraw($v->getId());
		}
		
		$wtRslt= "Salari ritirati";
		
	}elseif (intval($_REQUEST['wt'])>0){
		
		if($MyChar_obj->salaryWithdraw(intval($_REQUEST['wt']))){
			$wtRslt= "Salario Ritirato";	
		}else{
			$wtRslt= "Impossibile Ritirare salario per carica id: {$_REQUEST['wt']}";	
		}
		
	}

	exit();
	
}

$salaryList="<ul>";
$totSalary=0;
foreach($MyChar_obj->getGroups() as $k=>$v){
		
		
	if($MyChar_obj->isEligibleSalaryWithdraw($v->getId()) >=0 ){
		$withdrawFlag="<a href=\"bank.php?wt={$v->getId()}\" class=\"stay\">Ritira ({$v->getSalary()} $valuta_plurale)</a>";
		$totSalary+=$v->getSalary();
	}else { $withdrawFlag=" (Salario già ritirato)"; }
	
	$salaryList.="<li>{$v->getName()} $withdrawFlag</li>";
	
}
if($salaryList=='<ul>') $salaryList.="<li>Non hai salari da ritirare</li>";
$salaryList.="</ul>";
if($totSalary>0) $salaryList.="<a href=\"bank.php?wt=all\" class=\"buttonify stay\" >Ritira Tutti ($totSalary $valuta_plurale) </a>";


if (isset($_REQUEST['recipient']) && isset($_REQUEST['money'])){
	
	$destChar=new Character(null,$_REQUEST['recipient']);
	$destChar->checkExistance();
	//se esiste il destinatario proseguo
	if ($destChar->exists()){
		
		$moneytrans=intval($_REQUEST['money']);
		
		if ($moneytrans>0){
			
			if($MyChar_obj->getMoney()>=$moneytrans){
				
				if($MyChar_obj->moveMoney($moneytrans,$destChar,true,true)){
					echo "Transazione eseguita";	
				}
				
			}else{
				echo "Non possiedi abbastanza $valuta_plurale";	
			}
			
		}else{
			echo "Non puoi trasferire una somma negativa.";	
		}
		
		
		
		
	}else{
		
		echo "Il Destinatario non esiste.";
	}
	
	exit();	
}

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<title>Banca</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">

$(function() {
	$( "#recipient" ).autocomplete({
			source: "char_list.php",
			minLength: 2,
			delay: 200
	});
	
	
	//catcho i form e i link
	$(".bank_table").on('submit', "#bankform", function(){
		rowArr = $(form).serializeArray(); 
    	$.post("bank.php",rowArr,function(data){ $(".transferResult").html(data); } );
    	return false;
		
    });
    
    $(".bank_table").on('click', "a", function(){
    	url=$(this).attr("href");
    	$.get(url,function(data){ $(".transferResult").html(data); } );
    	return false;
		
    });
	
	
	//$("table tr:even").addClass("even");
	//$("table tr:odd").addClass("odd");
	
	$( ".buttonify" ).button();
	
	
});
</script>

</head>

<body>
	<div id="online_list_table" class="bank_table">
		<h2>Banca</h2>
		<div><h3>Trasferimento Fondi</h3>
			<div class="transferResult"></div>
			<form action="bank.php" method="post" id="bankform">
			<table style="width:100%">
				<tr><td>Destinatario</td><td><input name="recipient" type="text" id="recipient"/></td></tr>
				<tr><td><?php echo $valuta_plurale;?></td><td><input name="money" type="text"/></td></tr>
				<tr><td colspan="2"><input type="submit" value="Conferma" class="buttonify" /></td></tr>
			</table>
			</form>
		</div>
		<div><h3>Ritiro Salari</h3>
			<div class="wtResult"><?php echo $wtRslt; ?></div>
			<table style="width:100%">
				<tr><td colspan="2"><?php echo $salaryList; ?></td></tr>
			</table>
		</div>
	
	</div>
</body>
</html>