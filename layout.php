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
<? include_headers('all'); ?>
<script >

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

var player = null;
var playlist=new Array();
var playlist_i=0;

function onYouTubePlayerReady(player_id) {
  
  if(player==null)
  	return;
  
  player.addEventListener('onStateChange', 'playerStateChanged');
  playVideo(playlist_i);

};

function playerStateChanged(state) {

  if(player==null)
  	return;
  
  if (state == 0) {
  	
  	playlist_i++;
  	
  	//se c'è solo un video mi fermo
  	if(playlist.length == 1)
  		return;
  	
  	//se sono all'ultima ricomincio
  	if(playlist_i==playlist.length)
  		playlist_i=0;
  		
  	playVideo(playlist_i);
  }
};

function playVideo(pl_pos){
	
	if(player==null)
  		return;
  	
  	if(pl_pos>=playlist.length)
  		return;
  		
  	playlist_i=pl_pos;
  	
  	//aggiungo l'attributo di selezione
  	$(".media_list li a").removeClass("currentvideo");
  	$(".media_list li a[pl_pos='"+pl_pos+"']").addClass("currentvideo");
	
	player.loadVideoById(playlist[playlist_i]);	
}


function youtubeid(url) {
	var ytid = url.match("[\\?&]v=([^&#]*)");
	ytid = ytid[1];
	return ytid;
};

function validYoutube(url){

	//check if the video is a valid youtube one

	var validMatch = "(http://)?(www\.)?(youtube|yimg|youtu)\.([A-Za-z]{2,4}|[A-Za-z]{2}\.[A-Za-z]{2})/(watch\?v=)?[A-Za-z0-9\-_]{6,12}(&[A-Za-z0-9\-_]{1,}=[A-Za-z0-9\-_]{1,})*";

	if(validMatch.test(url))
		return true;

	return false;


}

function add_media(url,nome){
	
	//check the integrity of the youtube url
	if(url==null || nome==null || url=="" || nome=="" || !validYoutube(url))
		return;
	
	videoID=youtubeid(url);
	
	//controllo che non ci sia già nella lista
	for(i=0;i<playlist.length;i++)
		if(playlist[i]==videoID) return;
		
	//se non c'è lo pusho
	playlist.push(videoID);
	
	if(player==null){
		
		 var params = { allowScriptAccess: "always"};
  		 var atts = { id: "ytplayer" };
         swfobject.embedSWF("http://www.youtube.com/v/"+videoID+"?enablejsapi=1&playerapiid=ytplayer&version=3",
	                       "ytvideocontent", "100%", "100%", "8", null, null, params, atts);
  
		player=document.getElementById("ytplayer");
		
	}
	
	p=(playlist.length)-1;
	var elem=$("<li><a href=\""+url+"\" pl_pos=\""+p+"\" >"+nome+"</a></li>");
	$(".media_list").append(elem);
	
	
	elem.find("a").click(function(){
		playVideo($(this).attr("pl_pos"));
		return false;
	});
	
	/*
	if($(".media_list li").size() <= 1){
		elem.find("a").click();
	}
	*/
	
	//faccio rimbalzare il div
	$("#mediaplayer_tab span").html($(".media_list li").size());
	
	
}

var show_media=0;

function toggle_media(){
	
	calW=($(".yt_holder").height())+1;
	
	if(show_media==1){ //nascondo
		
		$("#media_player").animate({
		    top: -(calW)+"px"
		  }, 500, function() {
		    // Animation complete.
			show_media=0;
		  });
	}else{ //lo mostro
		$("#media_player").animate({
		    top: 0
		  }, 500, function() {
		    // Animation complete.
			show_media=1;
		  });
	}
	
	
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
  
  //$('#scrollNews1').marquee();
  //$('#scrollNews2').marquee();
  
  $(window).delay(100).resize(function() {
    redraw();
  });
  
  //$("ul.media_list").ytplaylist();
  
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
