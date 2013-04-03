<?php
/*  I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
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

$_SESSION['modlevel']=$MyChar_obj->Account()->getModLevel();

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title><?php echo $nome_land;?></title>
<?	include_headers('all');
	$arr=array(0=>'youtube');
	include_headers($arr); ?>
<script>

var chat_timer;
var pm_timer;
var online_timer;
var xhr=null;

var count_lostreq=0;

var $AVTdialog;
var $MSGdialog;
var $ALERTdialog;

function stop_timers(){
	
	clearInterval(chat_timer);
	clearInterval(pm_timer); 
	clearInterval(online_timer);
	
}

function irpg_service(){
	
	$.ajax({
        type: "POST",
        url: "irpg_service.php",
        timeout: 5000, // in milliseconds
        success: function(data) {
            
            count_lostreq=0;
            
            $(data).filter('span#newPM').each(function(){
	        	num=parseInt($(this).html())
	        	if(num>0){
	        		//$("#pm_link").effect("bounce", { times:4 }, 300);
	        		$("#navigation .pm_counter").html(num);
	        		$("#navigation .pm_counterW").fadeIn();
	        	}else{
	        		$("#navigation .pm_counterW").fadeOut();
	        	}
        	});
        	$(data).filter('span#news1').each(function(){
        		$("#scrollNews1").html($(this).html());
        	});
        	$(data).filter('span#news2').each(function(){
        		$("#scrollNews2").html($(this).html());
        	});
        	$(data).filter('span#roomthumb').each(function(){
        		show_room_thumb($(this).html());
        	});
        	$(data).filter('div#onlineResult').each(function(){
        		$("#online_list_containerPane").html($(this).html());
        	});

        },
        error: function(request, status, err) {
            
            count_lostreq++;
            
            if(count_lostreq>3){
            	$ALERTdialog.html("<p>Abbiamo riscontrato un problema di comunicazione verso il nostro server.</p><p>Problema riscontrato: "+status+"</p>");
            	//$ALERTdialog.dialog('open');
            }
        }
    });

}

function show_room_thumb(img_url){
	
	$("#room_image img").attr('src',img_url);
	
}

function loaded_avatar(){
  
  
  Content=$("#iavatar").contents().find(".avatar_container");
  maxH=$('#header').height()+$('#wrapper').height()+$('#footer').height()-10;
  namechar= Content.attr("charname");
  
  if(Content.length){
  
	  $AVTdialog.dialog( "option", "title", "Scheda del Personaggio "+namechar );
	  
	  $AVTdialog.dialog('open');
	  
	  
	  if (Content.height()>maxH){
			newH=maxH;
	  }else{
			newH=Content.height();
	  }
  
  }
  return false;

}

function loaded_message(){
  
  Content=$("#imessage").contents().find(".pm_subpage");
  
  if(Content.length){
  
	  $MSGdialog.dialog( "option", "title", $("#imessage").contents().find( "title").html( ) );
	  
	  $MSGdialog.dialog('open');
	  
	  
	  if (Content.height()>maxH){
			newH=maxH;
	  }else{
			newH=Content.height();
	  }
  
  }
  return false;

}


function redraw(){
  $("#icontent").width($('#content').width());
  //$dialog.dialog( "option", "height", $('#header').height()+$('#wrapper').height()+$('#footer').height()-10 );
}



$(document).ready(function(){
      	  
   var wWidth = $(window).height();
   var dWidth = wWidth * 0.95; //this will make the dialog 80% of the 
      	  
  $AVTdialog=$('#avatar_divContent');
  $AVTdialog.dialog({
    	show: "fade",
    	hide: "fade",
    	resizable: false,
    	title: "Scheda del Personaggio",
    	width: 820,
    	height: dWidth,
    	position: ['center','top'],
    	dialogClass: 'dialogWithDropShadow',
    	autoOpen: false
  });
  
  $MSGdialog=$('#message_divContent');
  $MSGdialog.dialog({
    	show: "fade",
    	hide: "fade",
    	resizable: true,
    	title: "Nuovo <?php echo $missive; ?>",
    	width: 820,
    	height: 680,
    	position: ['center','top'],
    	autoOpen: false
  });
  
  $ALERTdialog=$('<div class="alertdialog"></div>');
  $ALERTdialog.dialog({
    	show: "fade",
    	hide: "fade",
    	resizable: false,
    	title: "Errore",
    	width: 300,
    	height: 'auto',
    	position: ['center','center'],
    	dialogClass: 'alertui',
    	autoOpen: false
  });
  	
  	
	$('a:not(.popUp,.ui-datepicker-calendar a,.deletePm,.ui-autocomplete a)').live('click', function() {
	  // Live handler called.
	  
	  stop_timers();
	  
	  if(xhr)  xhr.abort();
	  xhr = $.ajax({
		    url : $(this).attr('href'),
		    success : function(data2) {
		        $("#content").stop(true,true).fadeTo(400,0,function(){
					$('#content').scrollTop(0).html(data2).fadeTo(400,1);
				});
		    }
	  });
	  
	  return false;
	  
	});
	
	$(".deletePm").live('click',function(){
			
			deletePm(this.href,$(this).parents(".personal_message").attr("id"));
			return false;
	});	
	
	$('form:not(.special)').live('submit', function() {
	  // Live handler called.
	  
	  stop_timers();
	  
	  rowArr = $(this).serializeArray(); 
      $.post($(this).attr("action"),rowArr,function(data2){
    	$('#content').html(data2);
	  });
		return false;
	});
	
	$( ".logday" ).datepicker({
	    changeMonth: true,
	    changeYear: true,
	    dateFormat: 'yy-mm-dd',
  	});
 
  
  $('#defaultClick').click();
  
  
  redraw();
  
  irpg_service();
  var irpg_daemon = setInterval(irpg_service, 5000);

  
  $(window).delay(100).resize(function() {
    redraw();
  });
  
  $("#mediaplayer_tab a").click(function(){ toggle_media(); return false; });
  
		  		  
});


</script>
</head>
<body class="mainLayout">

<div id="media_player">
	<div class="yt_holder roundcorner clearborder">
		<div id="ytvideo"><p id="ytvideocontent">In questo pannello verranno eseguiti i video musicali inseriti nelle schede dei personaggi.</p></div>
		<div class="ml_holder">
			<ul class="media_list"></ul>
		</div>
	</div>
	<div id="mediaplayer_tab"><a href="#" class="popUp">Player (<span>0</span>)</a></div>
</div>

<div id="avatar_divContent" class="ui-corner-all"><iframe id="iavatar" name="iavatar" frameborder="0" onload="loaded_avatar();" src=""  transparency="true" allowTransparency="true" bgcolor="transparent" ></iframe></div>
<div id="message_divContent" class="ui-corner-all"><iframe id="imessage" name="imessage" frameborder="0" onload="loaded_message();" src=""  transparency="true" allowTransparency="true" bgcolor="transparent" ></iframe></div>

<div id="navigation" class="dark_bg">
	<div style="text-align:center;font-size:17px;"><?php echo $MyChar_obj->getCharNameLink(); ?></div>
	<div id="room_image" class="generalborder">
		<img />
	</div>
	<ul class="roundcorner clearborder panel_bg">
		<li><a href="map.php" class="mainMenu" id="defaultClick">Mappa</a></li>
		<li><a href="forum_boards.php" class="mainMenu"><?php echo $forum; ?></a></li>
		<li><span class="pm_counterW hidden floatright" style="margin-right:35px;"><div class="pm_counter"></div></span><a href="pm_list.php" class="mainMenu" ><?php echo $missive_plurali; ?></a></li>
		<li><a href="online_list.php" class="mainMenu" >Presenti</a></li>
		<li><a href="group_list.php" class="mainMenu" >Gruppi</a></li>
		<li><a href="char_list.php" class="mainMenu" >Iscritti</a></li>
		<li><a href="meteo_small.php" class="mainMenu" >Meteo</a></li>
		<?php
		//if($MyChar_obj->Account()->getModLevel()>0 || $MyChar_obj->getMasterLevel()>0){
		
		?>
		<li><a href="admin_tools.php" class="mainMenu" >Gestione</a></li>
		<?php  
		//}
		?>
		
		<li><a href="logout.php" target="_top" class="popUp">Esci</a></li>
		
			
		
	</ul>
</div>

<div id="wrapper">
	<div id="header"></div>
	<div id="content"></div>
</div>

</body>
</html>
