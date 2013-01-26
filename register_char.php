<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
require_once("libs/character_lib.php");
require_once("libs/ability_lib.php");
require_once("libs/group_lib.php");		
	

if(!isset($_SESSION))
{
session_start();
} 

//se l'account non è autenticato, fermo tutto
if (!isset($_SESSION['id']) || $_SESSION['id']<=0 || empty($_SESSION['id']) ) {
  header("location: expired.php");   
}

$myAcc=new Account(null,$_SESSION['id']);
$myAcc->parseFromDb();

//se l'account è autenticato carico la lista dei pg
$charList=new CharacterList();

$charList->readFromDb(null,$_SESSION['id']);


	
?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Creazione Nuovo Personaggio</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/custom-theme/jquery-ui-1.8.13.custom.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">

function SumCar() {
	
	var max = <?php echo $max_value_fields_sum_at_signup; ?>;
	
	$(".CarVal").each(function(index){
			max=max-(parseInt($(this).val()));
	});
	$(".CarTot").val(max);
	
	return max;
	
}

function SumAbl(){
	
	var max = <?php echo $abilities_avail_subs_maxnum; ?>;
	
	$(".AblVal").each(function(index){
			max=max-(parseInt($(this).val()))*(parseInt($(this).attr('step')));
	});
	$(".AblTot").val(max);
	
	return max;
	
}

function plusminus(sign,maxv,target,callback){
	
	step=1;
	
	var newvalue=null;
	
	if(sign=='+' && $(target).val()+step<=maxv){
		retval=step;
		if(callback!=null)
			retval=eval(callback);
		if(retval-step>=0)	
			newvalue=parseInt($(target).val())+step;
	}
	if(sign=='-' && $(target).val()-step>=maxv){
		newvalue=parseInt($(target).val())-step;
	}
	
	if(newvalue!=null)
		$(target).val(newvalue);
		
	if(callback!=null)
		eval(callback);

}

$(document).ready(function(){
          
  $(".plusminus").click(function(){
        
        sign=$(this).attr('sign');
        maxv=$(this).attr('maxv');
        target=$(this).attr('tgt');
        callback=$(this).attr('callback');
        
        plusminus(sign,maxv,$("#"+target),callback);
               
  });
  
  $(".grp_selection").change(function(){
  	var d=$(this).attr("description");
  	$("#groupDescr").html(d);
  });
  
  $( ".buttonify" ).button();
  $( ".buttonset" ).buttonset();
  
  SumCar();
  SumAbl();
  
});

</script>
</head>

<body>
  <h1 class="centertxt">Creazione nuovo personaggio</h1>

<div class="centertxt">
  <?php

//controllo che possa creare altri char  
if ($myAcc->getModLevel()<$admin_always_allow_multichar){
	if ($charList->CountChars()>=$max_multichar){
		echo "Hai Già raggiunto il numero massimo di personaggi.";
		exit();	
	}
	if (!$allow_multichar && $charList->CountChars()>0){
		echo "Multichar disabilitato";
		exit();	
	}
}

$i=0;
foreach ($name_car as $key => $value){
	${"defC$i"}=$value;
	$i++;
}

//problema: leggere tutte le abilità, non sono numerate ordinatamente, ma in base all'id


//eseguo le operazioni di registrazione
if (isset($_REQUEST['r']) && $_REQUEST['r']==1){
	
	
	$flag=0;
	if (!isset($_REQUEST['username'])) {
		echo 'Inserire lo username<br />';
		$flag=1;
	}else {
		if (!validate_username($_REQUEST['username'])){
			echo 'L\'username deve essere lunga da 2 a 15 caratteri alfanumerici';
			$flag=1;
		}else{ 
			//controllo se lo username c'è già
			$char= new Character(null,$_REQUEST['username']);
      		$char->checkExistance();
			if ($char->exists()){
				echo 'Username in uso, scegline un altro<br />';
				$flag=1;
			}
			
		}
	}
	
	
	
	if (!isset($_REQUEST['radiobutton']) || ($_REQUEST['radiobutton']!='1' && $_REQUEST['radiobutton']!='2')) {
		echo 'Inserire il sesso<br />';
		$flag=1;
	}
	
	$summ_car=0;
	$stats_string="";
	
	foreach ($name_carStatic as $key => $defaultvalue)
		$stats_string.="$defaultvalue ";
	
	$i=0;
	foreach ($name_car as $key => $defaultvalue){
		if($max_value_fields_sum_at_signup>0){
			
			if (!isset($_REQUEST["Car$i"]) || $_REQUEST["Car$i"] < $min_value_single_field_at_signup || $_REQUEST["Car$i"] > $max_value_single_field_at_signup) {
				echo "Caratteristica ".$key." non corretta<br />";
				$flag=1;
			}else{
				$summ_car+=$_REQUEST["Car$i"];
				$stats_string.=$_REQUEST["Car$i"]." ";
			}
			
		}else{
			$summ_car+=$defaultvalue;
			$stats_string.=$defaultvalue." ";
		}	
			
		
		$i++;
	}
	
	
	if ($summ_car!=$max_value_fields_sum_at_signup) {
		echo 'Devi spendere tutti i punti tra le caratteristiche<br />';
		$flag=1;
	}
  
  //controllo sulle abilità
  $type_clone_count=array();
  $addAbArr=array();
  foreach ($abilities_types_array as $type=>$value){//scorro l'array dei tipi di abilità
    $type_clone_count[$type]=0;
  }
  
  if(!isset($_REQUEST['Abl'])) //se l'array non esiste lo inizializzo per sicurezza
    $_REQUEST['Abl']=array();
  
  foreach($_REQUEST['Abl'] as $abk=>$abv){
    
	$tmpAb=new Ability(null,$abk);
	
    $tmpAb->readFromDb();
    
    //se è disponibile all'iscrizione allora la aggiungo
    if ($tmpAb->getAvailableSubscription()>0){
      
	  $AbLvl=intval($abv);	
      if ($abv>$tmpAb->getMaxlevel())
	  	$AbLvl=$tmpAb->getMaxlevel();
	  if ($abv<0)
	  	$AbLvl=0;
	  $curCostAb=$tmpAb->getCostArray();
      $type_clone_count[$tmpAb->getType()]+=$AbLvl*$curCostAb[1];
      if($AbLvl>0)
      	$addAbArr[$tmpAb->getId()]=$AbLvl;
    }    
    
  }
  
  //controllo i punti spesi in "tipi di abilità"
  foreach ($abilities_avail_subs_numtype as $key=>$value){
	  $cur_abl_maxN=$abilities_avail_subs_numtype[$key];
	  	if(substr($value, 0, 1)=='x'){
	      $exact_match=true;
	      $cur_abl_maxN=0;
	      $cur_arr=explode('+',substr($value, 1));
	      foreach($cur_arr as $kk=>$vv)
	        $cur_abl_maxN+=$type_clone_count[$vv];
	    }elseif($cur_abl_maxN=='-1'){
			$cur_abl_maxN=-1;
			$exact_match=false;   	
		}else{
		    //$cur_abl_maxN=$cur_abl_maxN;
		    $exact_match=false;
		}
		
		//ora che ho caricato il maxN relativo al tipo key, controllo che sia accettabile
		
		//se è -1, skippo, perchè accetto qualsiasi numero di abilità
		if($cur_abl_maxN!=$type_clone_count[$key] && $exact_match){ //devo avere l'esatto numero di punti spesi
			echo "Devi spendere {$cur_abl_exactN} punti in: {$abilities_types_array[$key]}<br />";
      		$flag=1;
		}elseif($cur_abl_maxN>=0 && $cur_abl_maxN<$type_clone_count[$key]){
			echo "Puoi spendere al massimo {$cur_abl_maxN} punti in: {$abilities_types_array[$key]}<br />";
      		$flag=1;
		}
  }
  
  //controllo i punti spesi in totale nelle abilità
  $abLvlTot=0;
  foreach($type_clone_count as $type=>$pxSpent)
  	$abLvlTot+=$pxSpent;
	
	if($abLvlTot>$abilities_avail_subs_maxnum){
		echo "Puoi spendere al massimo $abilities_avail_subs_maxnum punti nelle Abilità";	
		$flag=1;	
	}
	

	if ($flag==0) { //tutto è ok, procedo alla scrittura dal database e all'invio dell'email
	
			
      $rowChar=array();
      $rowChar['account']=$myAcc->getId();
      $rowChar['name']=$_REQUEST['username'];
      $rowChar['sex']=$_REQUEST['radiobutton'];
      $rowChar['stats']=trim($stats_string);
	  $rowChar['px']=10;
	  $rowChar['money']=0;
	  
      
      $newChar= new Character();
      $newChar->populateClass($rowChar);
      $newChar->writeToDb();
      
     

$found_grp=false;
		foreach($groups_name_array as $key => $value) {
				
				$groupList = new GroupList($key); //carico la lista dei gruppi, per il dato tipo.
				foreach($groupList->GetList() as $k => $v) {
					
					//$v = new Group();
					
					if( $v->loadElements(1) > 0){ //carico solo i default, e mostro la selezione solo se ci sono alternative
						
						foreach($v->getElements() as $e){
								
							if(isset($_REQUEST["grp_".$key]) && $_REQUEST["grp_".$key]==$e->getId()){
								$found_grp=true;
								$e->JoinGroup($newChar);
							}
							
								
						}	
					}
				}
		}

	  //aggiungo i groups elements di default
	  if(!$found_grp){
		  $newGroupElementList=new GroupElementList();
		  $newGroupElementList->readList(1);
		  foreach($newGroupElementList->GetList() as $elemK=>$elemV){
		  	$elemV->JoinGroup($newChar);
		  }
	  }
      
      //aggiungo al char le abilità scelte all'iscrizione
      $moneyB=$char_money_at_signup;
      foreach($addAbArr as $k=>$v){
         $tmpAb=new Ability(null,$k);
         $tmpAb->readFromDb();
         
         $buyedAb=$tmpAb->BuyAbility($newChar,false);
         //$buyedAb=new BuyedAbility();
		 if($v>1){
			 if(!$buyedAb->upgrade($newChar,$v-1,false)){
			 	echo "Errore nell'impostare il livello dell'abilità: {$tmpAb->getName()}<br/>\r\n";
			 }
		 }
         
         if ($tmpAb->getMoneyBonus()>0)
          $moneyB+=$tmpAb->getMoneyBonus();
      }
      
	  
      if ($moneyB>0){
        $newChar->addMoney($moneyB);
      }
			
			echo "Il tuo nuovo personaggio è pronto!<br/><a href=\"character_select.php\">Procedi</a>";
			exit();
			
	}

}


?>
  
</div>








<form id="form1" name="form1" method="post" action="register_char.php?r=1">

<div class="roundcorner panel_bg clearborder width90 center">
	<div class="rightCol floatright" style="width:60%;">
		<?php
  //mostro le sezione di distribuzione delle abilità solo se ce n'è almeno una
  if (count($name_car)>0 && $max_value_fields_sum_at_signup>0){
  
  ?>
  <div class="justified">Puoi distribuire un totale di <?php echo $max_value_fields_sum_at_signup; ?> punti tra le <?php echo $n_car; ?> caratteristiche che determineranno le qualit&agrave; del tuo personaggio. I valori di ogni caratteristica devono essere compresi tra <?php echo $min_value_single_field_at_signup; ?> e <?php echo $max_value_single_field_at_signup; ?>. </div>
  <div class="centertxt">Punti Caratteristica restanti 
      <input name="CarTot" type="text" class="formtrasp CarTot" onfocus="this.blur()" value="" size="3" readonly="readonly"/>
    </div>
  <table>
  <?php
  
  $i=0;
  foreach ($name_car as $key => $defaultvalue){
  	echo "<tr>
  			<td><div align=\"right\">$key</div></td>
    		<td>
		    	<input class=\"plusminus buttonify form2\" type=\"button\" name=\"Submit0$i\" value=\"-\" sign=\"-\" maxv=\"{$min_value_single_field_at_signup}\" tgt=\"Car$i\" callback=\"SumCar()\" />
		    	<input class=\"formtrasp CarVal\" type=\"text\" name=\"Car$i\" id=\"Car$i\" value=\"".${"defC$i"}."\" onfocus=\"this.blur()\" size=\"2\" readonly=\"readonly\" />
		    	<input class=\"plusminus buttonify form2\" type=\"button\" name=\"Submit$i\" value=\"+\" sign=\"+\" maxv=\"{$max_value_single_field_at_signup}\" tgt=\"Car$i\" callback=\"SumCar()\" />
		    </td>
  		</tr>\n";
  	$i++;
  }
  echo "</table>";
  }

  ?>
  <h3>Descrizione Gruppo</h3>
 <div id="groupDescr"></div>
		
	</div>
	
	
	<div class="leftCol clearborder" style="padding: 10px; width:30%;">
		<div>
			<label for="username">Nome Personaggio</label><br />
	        <input name="username" type="text" id="username" value="<?=$_REQUEST['username'] ?>" maxlength="50" />
        </div>
        <div class="buttonset" style="margin-top:10px;">
			<label for="male">maschio</label>
			<label for="female">femmina</label>
			<input id="male" name="radiobutton" type="radio" value="1" <? if ($_REQUEST['radiobutton']=='1') echo 'checked="checked"'; ?> />
			<input id="female" name="radiobutton" type="radio" value="2" <? if ($_REQUEST['radiobutton']=='2') echo 'checked="checked"'; ?> />
		</div>
		<div>
			<?php
  if ($abilities_avail_subs_maxnum>0){
      echo "<table>
      		<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr>
			    <td colspan=\"2\"><div align=\"centertxt\">Punti Abilità restanti 
      			<input name=\"AblTot\" type=\"text\" class=\"formtrasp AblTot\" onfocus=\"this.blur()\" value=\"\" size=\"3\" readonly=\"readonly\"/>
    			</div></td>
  			</tr>";
      
  }
  
  // sezione di scelta delle abilità selezionabili all'iscrizione
  foreach ($abilities_types_array as $key=>$value){//scorro l'array dei tipi di abilità
  
    
    
    $cur_abl_maxN=$abilities_avail_subs_numtype[$key];
    if(substr($abilities_avail_subs_numtype[$key], 0, 1)=='x'){
      $cur_abl_maxN=0;
      $cur_arr=explode('+',substr($abilities_avail_subs_numtype[$key], 1));
      $strAb="Devi selezionare un numero di {$abilities_types_array[$key]} pari al numero di ";
      foreach($cur_arr as $kk=>$vv){
        $cur_abl_maxN+=$abilities_avail_subs_numtype[$vv];
        $strAb.="{$abilities_types_array[$vv]}";
        if ($kk+1<count($cur_arr))
          $strAb.="+";
      }
      $strAb.=" da te scelte.";
    }elseif($cur_abl_maxN=='-1'){
		$strAb="{$abilities_types_array[$key]} Selezionabili";    	
	}else{
	    $strAb="Seleziona {$cur_abl_maxN} {$abilities_types_array[$key]}";
	}
    
    //faccio scegliere un numero di abilità pari a quelle definite nel config data la chiave
    if(abs($cur_abl_maxN)>0){ //se posso sceglierne almeno una popolo la lista
      
      $ablLst=new AbilityList();
      $ablLst->populateList($key,null,true);
      
      //se c'è almeno una abilità comprabile procedo
      if ($ablLst->getSize()>0){
        
        echo "<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
              <tr>
                <td colspan=\"2\"><div align=\"centertxt\">{$strAb}</div></td>
              </tr>";
        
        foreach($ablLst->getList() as $k=>$v){
          
          //$v=new Ability();
          
		  $stepAblCost=$v->getCostArray();
		  $minAbLv=0;
		  $maxAbLv=$v->getMaxlevel();
		  if($maxAbLv==0)
		  	$maxAbLv=1;
		  
		  echo "<tr>\r\n    <td><div align=\"right\">{$v->getName()}</div></td>
		  			<td>
		  				<input class=\"plusminus buttonify form2\" type=\"button\" name=\"Submit0$i\" value=\"-\" sign=\"-\" maxv=\"{$minAbLv}\" tgt=\"Abl{$v->getId()}\" callback=\"SumAbl()\" />
    					<input class=\"formtrasp AblVal\" type=\"text\" name=\"Abl[{$v->getId()}]\" id=\"Abl{$v->getId()}\" value=\"0\" onfocus=\"this.blur()\" size=\"2\" step=\"{$stepAblCost[1]}\" readonly=\"readonly\" />
    					<input class=\"plusminus buttonify form2\" type=\"button\" name=\"Submit$i\" value=\"+\" sign=\"+\" maxv=\"{$maxAbLv}\" tgt=\"Abl{$v->getId()}\" callback=\"SumAbl()\" />
		  			</td>
		  		</tr>";	
		  
		  
          
        }
      }
    }

	echo "</table>";
    
  }
  ?>
		</div>
		<div>
			  <?php
  	
  	$type_sel='radio';
	//in questa sezione, per ogni tipologia di gruppi, cerco se ci sono dei default. Se ce n'è + di uno per gruppo, faccio scegliere
	if($allow_multigroup_sametype){
		//$type_sel='checkbox';
	}else{
		$type_sel='radio';
	}
  
  foreach($groups_name_array as $key => $value) {
		
		
		$foundGroups=0;
		$selector="";
		
		$groupList = new GroupList($key); //carico la lista dei gruppi, per il dato tipo.
		foreach($groupList->GetList() as $k => $v) {
			
			//$v = new Group();
			
			if( $v->loadElements(1) > 0){ //carico solo i default, e mostro la selezione solo se ci sono alternative
				
				foreach($v->getElements() as $e){
					$foundGroups++;
					$selector.="<input type=\"$type_sel\" id=\"grpsel_{$e->getId()}\" name=\"grp_{$key}\" value=\"{$e->getId()}\" description=\"".htmlspecialchars($v->getStatute())."\" class=\"grp_selection\" /><label for=\"grpsel_{$e->getId()}\">{$e->getName()}</label><br/>\n";
				}	
				
			}
					
		}
		
		// per questo tipo, se ci sono Gruppi con dei default, li scrivo
		if($foundGroups>1){ //se è 1 lo applico senzo farlo scegliere
		
			
			echo "<h3>Seleziona $value</h3>
						$selector
					";
		
		}
  }
  
  ?>
</table>
		</div>
	</div>
	
	<div class="clearboth centertxt"><input type="submit" name="Submit" value="Prosegui" class="buttonify" /></div>
</div>
  
</form>
</body>
</html>