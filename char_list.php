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




if (isset($_REQUEST['term'])){
		
	$charList= new CharacterList();
	
	$charList->readFromDb($_REQUEST['term']);	
	foreach($charList->getChars() as $k=>$v){
		$retarr[$k]=$v->getCharName();
	}
	
	echo json_encode($retarr);
	
	exit();	
}


if(isset($_REQUEST['character']) || isset($_REQUEST['account']) || isset($_REQUEST['linkList']) ){
  
  $charList= new CharacterList();
  
  if(isset($_REQUEST['character']) && $_REQUEST['character']!=''){
    $charList->readFromDb($_REQUEST['character']);
  }elseif(isset($_REQUEST['account']) && $_REQUEST['account']!='' && $modlevel>=$avatar_edit_level_required){
    $charList->readFromDb(null,null,$_REQUEST['account']);
  }elseif(isset($_REQUEST['linkList']) && $_REQUEST['linkList']=='lastSign'){
    $charList->readFromDb(null,null,null,20);
  }else{
    exit();
  }
  
  $i=0;
  
  if ($charList->CountChars()<=0){
      
    echo "Nessun risultato soddisfa i criteri di ricerca.\n";
    exit();
    
  }
  
  if($modlevel>=$avatar_edit_level_required){
  	$acc_td="<td>Account</td><td>Creazione Account</td>";
  }else{
  	$acc_td="";
  }
  	
  echo "<table id=\"char_list_table\">
          <tr class=\"".(($i++ % 2 == 0) ? 'even' : 'odd')."\">
            <td>Nome</td>
            <td>Creazione Pg</td>
            $acc_td
          </tr>\n";
          
  foreach($charList->getChars() as $k=>$v){
    
    //$v=new Character();
    if($modlevel>=$avatar_edit_level_required){
  		$acc_td="<td>{$v->Account()->getUsername()}</td><td>".itaTime($v->Account()->getJoinDate())."</td>";
  	}else{
  		$acc_td="";
  	}
    
    echo "<tr class=\"".(($i++ % 2 == 0) ? 'even' : 'odd')."\">
            <td>{$v->getCharNameLink()}</td>
            <td>".itaTime($v->getDate())."</td>
            $acc_td
          </tr>\n";
    
  }
  
  echo "</table>\n";
  
  exit();

}

  
?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<title>Lista Utenti</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">
$(function() {
          
  $(".linkList").click(function(){
       
       srcT=this.rel;
       
       $.post("char_list.php",
              { linkList: srcT },
              function(data) {
                $("#charResult").html(data);
                
                  $("#charResult a.popUp").click(function(){
                    parent.showAvatar(this.href);
                    return false;
                  });
              }
       );
       
       return false;  
  });
  
  $("#listform").submit(function(){
       
       charsrc=$("#srcChar").val();
       accsrc=$("#srcAcc").val();
       
       $.post("char_list.php",
              { character: charsrc, account: accsrc },
              function(data) {
                $("#charResult").html(data);
                
                  $("#charResult a.popUp").click(function(){
                    parent.showAvatar(this.href);
                    return false;
                  });
              }
       );
       
       return false;
  });
  
	$( "#srcChar" ).autocomplete({
			source: "char_list.php",
			minLength: 2,
			delay: 200
	});
  
  
});
</script>

</head>

<body>
<form action="#" method="post" id="listform">
<div id="online_list_container">
	<table id="online_list_table">
<tr><td><a href="#" class="linkList" rel="lastSign" >Ultimi 20 Iscritti</a></td><td></td></tr>
<tr><td>Cerca un Personaggio</td><td><input type="text" name="srcChar" id="srcChar" /></td></tr>
<?php
if($modlevel>=$avatar_edit_level_required){
	
?>
<tr><td>Cerca un Account</td><td><input type="text" name="srcAcc" id="srcAcc" /></td></tr>
<?php
}
?>
<tr><td><input type="submit" value="Cerca"/></td><td></td></tr>
</table>
</div>
</form>
<div id="charResult">
</div>

</body>
</html>