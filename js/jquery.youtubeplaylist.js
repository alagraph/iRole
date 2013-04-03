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

	//var validMatch = "(http://)?(www\.)?(youtube|yimg|youtu)\.([A-Za-z]{2,4}|[A-Za-z]{2}\.[A-Za-z]{2})/(watch\?v=)?[A-Za-z0-9\-_]{6,12}(&[A-Za-z0-9\-_]{1,}=[A-Za-z0-9\-_]{1,})*";

	//if(validMatch.test(url))
		return true;

	return false;


}

function youtubeid(url) {
	var ytid = url.match("[\\?&]v=([^&#]*)");

	if ( ytid==null) return 0;

	ytid = ytid[1];
	return ytid;
};

function add_media(url,nome){
	
	//check the integrity of the youtube url
	if(url==null || nome==null || url=="" || nome=="" || !validYoutube(url))
		return;
	
	videoID=youtubeid(url)
	if( videoID <= 0) return;
	
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