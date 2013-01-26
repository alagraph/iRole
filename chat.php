<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("libs/common.php");
require_once("libs/chat_lib.php");
require_once("libs/character_lib.php");
require_once("libs/ability_lib.php");


if(!isset($_SESSION))
{
session_start();
} 

logged();


if (!isset($_REQUEST['room'])){
	echo 'id stanza non presente.';
	return;
}

if(!empty($_REQUEST['chat_instance'])){
	$_SESSION['chat_instance']=$_REQUEST['chat_instance'];
}else{
	$_SESSION['chat_instance']='0';
}


//prendo dalla pagina chiamante l'id della stanza corrente
if ( (!isset($_SESSION['room_id']) || !isset($_SESSION['date_chat'])) ||
	($_REQUEST['room']!=$_SESSION['room_id'] && isset($_SESSION['date_chat'])) ){
		
		$room_obj= new Room($_REQUEST['room'],null,$_SESSION['char_id']);
		
		$_SESSION['room_id']=$room_obj->getId();
		$_SESSION['date_chat']=date("YmdHis",strtotime("-$chat_offset minute"));
		$_SESSION['room_name']=$room_obj->getName();
}else{
	
	$room_obj= new Room($_SESSION['room_id'],null,$_SESSION['char_id']);
}

//valido la stanza..potrebbe non esistere, o non essere autorizzata all'utente
    if (is_null($room_obj->getId())){
      echo "Stanza inesistente.";
      exit();
    }
    if (($room_obj->getPrivate()==1 && $room_obj->getUser_rights()==0) || ($room_obj->getChattable()<=0 && empty($_REQUEST['chat_instance'] ))){
      echo "Accesso alla stanza negato.";
      exit();
    }
	
	//se gli UserRights > 0 mostro i comandi per invitare la gente
	switch ($room_obj->getUser_rights()) {
	case 2: //moderator
	
		//può invitare guest
		$maxlevel=1; //possono promuovere fino a guest
		$allowDemote=1; //possono demotare le guest
		
		break;
	case 3: //admin
	
		//può invitare guest e mod e admin
		$maxlevel=3; //possono promuovere fino a guest
		$allowDemote=3; //possono demotare le guest
		
		break;
	default: //nessun diritto, ma accede (ha passato il controllo sopra, quindi è comunque abilitato)
		
		$maxlevel=0;
		$allowDemote=0;
		
		break;
	}
	
	$promSel="<select name=\"promote_level\" >\n";			
	for($i=0; $i<=$maxlevel; $i++){
		$promSel.="<option value=\"$i\">{$chatPrivilegesArr[$i]}</option>\n";
	}
	$promSel.="</select>";
	
	$cmdPromoteStr='';
	if($maxlevel>0){
		
		$cmdPromoteStr="Imposta Accesso
						<table>
							<tr>
								<td><input type=\"text\" name=\"promote_name\" id=\"recipient\"/></td>
								<td>$promSel</td>
							</tr>
							<tr>
								<td colspan=\"2\"><input type=\"submit\" value=\"Salva\"/></td>
							</tr>
						</table>";
		
	}


$abopt="";
	
if(isset($allow_roll_stats) && $allow_roll_stats>0){
	
	foreach($name_carStatic as $k=>$v)
		if($k!='undefined')
		$abopt .= "<option value=\"stat_{$k}\">$k</option>";
	    
	
	foreach($name_car as $k=>$v)
		$abopt .= "<option value=\"stat_{$k}\">$k</option>";
	
}

if ($allow_roll_abilityes>0){
//carico le abilità del personaggio e popolo le option del tiro dei dadi
	
	$ablList=new AbilityList();
	$ablList->populateList();
	
	foreach($ablList->getList() as $key=>$value){
		
		//$value=new Ability();
		$buyed=$value->isOwnedBy($_SESSION['char_id']);
		//$buyed=new BuyedAbility();
		
		//se false le faccio lanciare tutte
		if ($allow_roll_only_owned){
			if($buyed){	
			$curAbLev=$buyed->getLevel();
			if($value->getMaxlevel()<=1){
				$lvlString="";
			}else{
				$lvlString=" [lv. {$curAbLev}]";
			}
				
			$abopt .= "<option value=\"{$key}\">{$value->getName()}{$lvlString}</option>";
			}
		}else{
			if($buyed){
				$curAbLev=$buyed->getLevel();
				if($value->getMaxlevel()<=1){
					$lvlString="";
				}else{
					$lvlString=" [lv. {$curAbLev}]";
				}
			}else {$curAbLev=0;}
			$abopt .= "<option value=\"{$value->getId()}\">{$value->getName()}{$lvlString}</option>";
			
		}
		
	}
	
}

if ($abopt!=""){
	$abopt='<select name="roll_ab" id="roll_ab"><option value="">Dado Abilità</option>'.$abopt.'</select>';
}else{
	$abopt=" - ";
}


?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Room</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">
/**** CHAT ****/

function setCookie(c_name,value,expiredays){
	var exdate=new Date();
	exdate.setDate(exdate.getDate()+expiredays);
	document.cookie=c_name+ "=" +escape(value)+
	((expiredays==null) ? "" : ";expires="+exdate.toUTCString());
}

function getCookie(c_name){
	if (document.cookie.length>0){
	  c_start=document.cookie.indexOf(c_name + "=");
	  if (c_start!=-1){
		c_start=c_start + c_name.length+1;
		c_end=document.cookie.indexOf(";",c_start);
		if (c_end==-1) c_end=document.cookie.length;
		return unescape(document.cookie.substring(c_start,c_end));
	  }
	}
	return "";
}

function saveCookie(){ 

	
	setCookie("save_chat",$("#chat_message").val(),2)
	setCookie("save_chattag",$("#chat_tag").val(),2)
	
	//$("#chat_count").html(maxl-document.form1.chat_message.value.length);
	//if ($("#chat_count").html()=='') {$("#chat_count").html("0")}
	
	return;
} 

var current_id=-1;
var first_upd=true;
var timer_is_on = 0;
var timer;

function draw_chat(data){
  	
  
  last_id=$(data).filter("div").last().attr("id");
  
  if(current_id==-1)
  	current_id=last_id;
  	
  //alert(current_id+"< vecchio   nuovo >"+last_id);
  if (current_id!=last_id && !first_upd){
    $(data).filter("div").last().attr("z-index","0");
    $("#chat_board").html(data);
    scroll_bottom();
    $("#"+last_id).hide().attr("z-index","9").fadeIn("slow");
    current_id=last_id;
  }
  else{
    $("#chat_board").html(data);
  }
  
  scroll_bottom();
 
}

var scrollB=true;
var accList=false;

function scroll_bottom(){
  
  
  //alert("div: "+$("#chat_board").height()+"\ntop: "+$("#chat_board").scrollTop()+"scrollH: "+$("#chat_board").attr("scrollHeight"));
  
  if (scrollB){
    $("#chat_board").scrollTop($("#chat_board")[0].scrollHeight);
    scrollB=true;
    return;
  }
  
  return;

}

function chat_update(){
	$.post("chat_do.php", function(data){
		if(data.length > 4)
			draw_chat(data);
		first_upd=false;
	});	
	
	if (accList){
		accessListLoad();
	}
	
}

function accessListHide(){
	//$("#accessList").hide('slide',{direction: 'right'});
	
  	
  	var $marginLefty = $('#accessList');
    $marginLefty.animate({
      right: parseInt($marginLefty.css('right'),10) == 0 ?
        -$marginLefty.outerWidth() :
        0
    },function(){
    	
    	
    	if(parseInt($marginLefty.css('right'),10)==0){
    		accList=true;
    	}else{
    		accList=false;
    	}
    	
    });
    
  	
}

function accessListShow(){
	accList=true;
	$("#accessList").show('slide',{direction: 'right'});
}

function accessListLoad(){
	$('#accessListdiv').load('chat_do.php?accessList=1');
}
	 

$(function() {
	
	/*$("select").selectmenu({
			style:'dropdown',
			width:150
	});
	*/	
	
      
$('#chat_form form').submit(function() {
		
		var new_msg=$("#chat_message").val();
		var roll_ab=$("#roll_ab").val();
		
		if (new_msg=='' && roll_ab=='') //messaggio vuoto
			return false;
		
		
		rowArr = $(this).serializeArray(); 
    	$.post("chat_do.php",rowArr,function(data){ 
    		if(data.length > 4)
    			draw_chat(data);
    	});
    	
    	
    	
    	 if (new_msg.match(/^(\<?php echo $action_symbol;?>).*/)){
	  		new_msg=RegExp.$1;
	  	}else if (new_msg.match(/^(\<?php echo $master_symbol;?>).*/)){
	  		new_msg=RegExp.$1;
	  	}else {
	  		new_msg="";
	  	}
  		$("#roll_ab").val('');
 		$("#chat_message").val(new_msg);
 		setCookie("save_chat",$("#chat_message").val(),2);
 	
    	return false;
});
      $("#chat_board").scroll(function(){
        
        
        
        
        if ($("#chat_board")[0].scrollHeight - $("#chat_board").scrollTop() -20 <= $("#chat_board").height()){
          //alert("s");
          scrollB=true;
        }else{
          scrollB=false;
        }
      });
      
      $( "#recipient" ).autocomplete({
			source: "char_list.php",
			minLength: 2,
			delay: 200
	  });
      
      $('.hideI').click(function(){
      	accessListHide()
      	return false;
      });
      
      
       
       $('#accessList form').submit(function() {
		rowArr = $(this).serializeArray(); 
    	$.post("chat_do.php?accessList=1",rowArr,function(data){ $('#accessListdiv').html(data); } );
    	return false;
      });
      
     chat_update(); 	  
     accessListLoad();
 		  $("#chat_message").focus();
 		  $("#chat_message").val(getCookie("save_chat"));
 		  $("#chat_tag").val(getCookie("save_chattag"));
 		  
 
  chat_timer= setInterval(chat_update, <?php echo $chat_refresh_rate; ?>);
  

		  
});



</script>
</head>

<body>
<div id="chat_wrapper">
<div id="chat_board">
</div>
<?php
if (strlen($cmdPromoteStr)>0){
	echo '<div id="accessList">
			<a href="#" class="popUp hideI ui-icon ui-icon-transferthick-e-w"></a>
			<form name="accL" action="#">'.$cmdPromoteStr.'</form>
			<div id="accessListdiv"></div>
		  </div>';
}
?>
<div id="chat_form">
	<span id="debugs"></span>
  <form id="chatSend" name="chatSend" action="#" >
  <table width="70%" border="0" class="center" cellpadding="0" cellspacing="0">
    <tr>
      <td style="width: 10%;">Tag:</td>
      <?php if ($allow_roll_abilityes>0){ echo '<td width="10%">Dado Abilità</td>'; } ?>
      <td nowrap="nowrap" style="height:20px;vertical-align:bottom;width:70%;">Stanza: <? echo $room_obj->getName(); ?></td>
      <td style="height:20px;vertical-align:bottom;width:10%;">&nbsp;</td>
    </tr>
    <tr>
      <td><input name="tag" type="text" id="chat_tag" style="width:84%;" onkeyup="saveCookie()" /></td>
      <?php if ($allow_roll_abilityes>0){ echo '<td>'.$abopt.'</td>'; } ?>
      <td><input name="messaggio" type="text" id="chat_message" style="width:98%;" onkeyup="saveCookie()" placeholder="Digita <? echo $command_symbol; ?>cmd per la lista dei comandi."/></td>
      <td><input type="submit" name="Submit" value="Invia"/></td>
    </tr>
  </table>
  </form>
</div>
</div>
</body>
</html>
