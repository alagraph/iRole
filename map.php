<?php
/*  I codici qui inseriti sono proprietà di
 *  Francesco Stasi, pertanto ne è vietata la
 *  vendita e la cessione a terzi senza previa
 *  autorizzazione dell' autore.
 */
 
require_once("config.php");
require_once("libs/common.php");
require_once("upload_lib.php");
require_once("libs/character_lib.php");
require_once("libs/chat_lib.php");



if(!isset($_SESSION))
{
session_start();
}

logged();

$MyChar=new Character($_SESSION['char_id']);

$canEditMap=false;
if ($_SESSION['modlevel'] >= $acc_admin_editmap_required)
  $canEditMap=true;

function addChat($name){
	
	$rowN=array();
	$rowN['name']=$name;
    $rowN['private']=0;
    $rowN['description']='';
	$rowN['map']='0';
	$rowN['chattable']=1;
	$rowN['default']=0;
	$rowN['instantiable']=0;
	
  	$newChat= new Room(null,null,null,$rowN);
	$newChat->writeToDb();
	
	echo "<span id=\"newChat\"><option value=\"{$newChat->getId()}\">{$newChat->getName()}</option></span>";
	
	return $newChat;
	
}

if(!isset($_REQUEST['roomid'])){
	//se non è impostato l'id della stanza che voglio aprire, apro quella di default
	
	$roomList= new ChatList();
	$roomList->readFromDb(true);
	$roomObj=$roomList->getFirstRoom();

}else{
	
	$roomObj= new Room($_REQUEST['roomid']);
}

// scrivo la location
$MyChar->writeLocation($roomObj->getId());

if ($canEditMap && isset($_REQUEST['s'])){
  	
  if( isset($_REQUEST['mod_desc']) ){
  	if($roomObj->setDescription($_REQUEST['mod_desc']))
		echo acapo($roomObj->getDescription());
	else
		echo "Impossibile Salvare Descrizione";
	exit();
  }	
  
  if (isset($_REQUEST['displaytype'])){
  	
	switch ($_REQUEST['displaytype']) {
	case 'list':
		$map='0';
		$roomObj->setMap($map);
		
		break;
	case 'map':
		$map=$_REQUEST['map_img'];
		if(strlen($map)>4)
			$roomObj->setMap($map);
		break;
		
	case 'newmap':
		//controllo se è stata caricata una nuova mappa
	  	$map_file=$_FILES['map_img']['tmp_name'];
	  	$pp = pathinfo($_FILES['map_img']['name']);
	  	$map_file_ext=$pp['extension'];
	  	
	  	if(isset($map_file) && isset($map_file_ext) && $map_file!='' && $map_file_ext!='')
	  		$roomObj->storeImage($map_file, $map_file_ext,'map');
		
		break;
	
	default:
		
		break;
	}
	
  }
  
  if (isset($_REQUEST['roomlogo'])){
  	
	switch ($_REQUEST['roomlogo']) {
		
	case 'newlogo':
		//controllo se è stata caricata una nuova immagine per la stanza
  		$room_file=$_FILES['room_img']['tmp_name'];
  		$px = pathinfo($_FILES['room_img']['name']);
  		$room_file_ext=$px['extension'];
  		
  		if(isset($room_file) && isset($room_file_ext) && $room_file!='' && $room_file_ext!='')
  			$roomObj->storeImage($room_file, $room_file_ext,'thumb');
		
		break;
	
	default:
		
		break;
	}
	
  }
  
  if (isset($_REQUEST['private'])){
  	
	$roomObj->setPrivate($_REQUEST['private'],$_REQUEST['pvtAdmin']);
	
  	//exit();
  }
  
  if (isset($_REQUEST['playable'])){
  	
	switch ($_REQUEST['playable']) {
	case '1':
		$playable=1;
		break;
	
	default:
		$playable=0;
		break;
	}
	
	$roomObj->setPlayable($playable);
	
  	//exit();
  }
  
  if (isset($_REQUEST['hotel']) ){
  	
	$roomObj->setInstantiable($_REQUEST['hotel']);
	if(intval($_REQUEST['hotel'])>0)
		$roomObj->setPlayable(0);
  	//exit();
  }
  
  if (isset($_REQUEST['newname']) && strlen($_REQUEST['newname'])>0 && $_REQUEST['newname']!=$roomObj->getName()){
  	$roomObj->setName($_REQUEST['newname']);
  }
  
  if (isset($_REQUEST['newchat']) && strlen($_REQUEST['newchat'])>0){
  	
	addChat($_REQUEST['newchat']);
	
	exit();
  	
  }
  
  
  $action=$_REQUEST['action'];
  $x_coord=$_REQUEST['x_c'];
  $y_coord=$_REQUEST['y_c'];
  $point_dest=$_REQUEST['poi_dest'];
  $point_id=$_REQUEST['id'];
  $newChat=$_REQUEST['chat_new'];
  
  
  
  
  switch ($action) {
      case 'save':
		  
		  
		  
		  if(isset($newChat) && strlen(trim($newChat))>0){
		  	$addedChat=addChat($newChat);
			
			$point_dest=$addedChat->getId();  
			$point_destName=$addedChat->getName();
			
		  }else{
		  		
		  	$chatDestObj=new Room();
			$chatDestObj->readFromDb($point_dest);
			
			$point_destName=$chatDestObj->getName();
			
		  }
		  
            
          if (isset($point_id) && $point_id>0 && $point_id!=""){
				
			echo "Modifico un PoI";	
			
            $ret=$roomObj->addSubchat($point_dest,$x_coord,$y_coord,$point_id,$point_destName);
            
			
          }else{
          	echo "Creo un PoI";
            $ret=$roomObj->addSubchat($point_dest,$x_coord,$y_coord,null,$point_destName);	
          }
		  
			
			if($roomObj->getMap()=='0'){
					
				$poiDel="<a href=\"map.php?s=1&roomid={$roomObj->getId()}&action=delete&id={$ret->getId()}\"><img src=\"images/icons/delete.png\" border=\"0\" alt=\"delete\" /></a>";
				$point_s="<span id=\"PointAddResult\"><li><a href=\"map.php?roomid={$ret->getRoomDest()}\">{$ret->getRoomName()}</a>$poiDel</li></span>";
				
				
			}else{
				$point_s="<span id=\"point_chat\">{$ret->getRoomDest()}</span>";
				$point_s.="<span id=\"point_id\">{$ret->getId()}</span>";
				$point_s.="<span id=\"point_title\">{$ret->getRoomName()}</span>";
				
			}
			
			
			
					
			echo $point_s;
			  
			exit();
		  
	  	break;
      
      case 'delete':
        if(isset($point_id)){
          $rmv=new MapPoi(null,$point_id);
          $rmv->poiDelete();
        }
        
        if($roomObj->getMap()!='0')
        	exit();
      	break;
	  
      default:
        //if($roomObj->getMap()!='0')
        //	exit();
        break;
        
  }
  
  
  
}

//caricata la stanza, carico le sottchat
$roomObj->readSubchat();

$ChatL=new ChatList();
$ChatL->readFromDb();
foreach ($ChatL->getRooms() as $k=>$v){
  
  $optL.="<option value=\"{$v->getId()}\">{$v->getName()}</option>";
  
}

$chat_list=("<select name=\"poi_dest\" id=\"poi_dest\" class=\"poi_dest\">$optL</select>");

?>
<!DOCTYPE html>
<html> 
<head>
<meta charset="UTF-8" />
<meta name="author" content="Francesco Stasi"/>
<title>Map</title>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">

//inizializzo le variabili globali al js
var portionToScroll=0;

//var chat_sel='<?php echo addslashes($chat_list); ?>';


function drawPoint(x,y,id,chat_id,edit_mode,roomname){
  
  //prendo le coordinate
  
  var styl='style="display:none;"'
  if (edit_mode) styl='';
  
  
  /*mapBox <div>{ //contiene tutto
  		
  		mapPoi <a></a> //il box contienente il link
  		editBox <div> </div>
  	
  }</div>
  
  */
	var chat_selDOM= $('<select name="poi_dest" class="poi_dest"><?php echo addslashes($optL); ?></select>');
  
  
  var editBox= $('<div class="editBox generalborder ui-corner-all" '+styl+'>'+
  					 '<div class="generalborder ui-corner-all" style="border:1px solid;">Trascinami!</div>'+
	  				 '<label for="newRoomC">Crea nuova stanza:</label><input type="checkbox" name="newRoomC" id="newRoomC" value="NC" /></div>')
	  				 .append(chat_selDOM)
	  				 .append('<input type="text" name="newRoom" class="newRoom" placeholder="Nome nuova stanza" style="display:none;"/>'+
	  				 '<input type="hidden" class="xy_val" xval="'+x+'" yval="'+y+'" readonly="readonly" />'+
	                 '<input class="canc" type="button" value="Cancella" />&nbsp;'+
	                 '<input class="save" type="button" value="Salva" />');
  
  var box=$('<div class="mapBox">'+ //il mapbox contiene tutto: il punto, il drag, e l'edit
              
            '</div>');
  
  var id_s='';
  if (id>0){
    id_s='id="'+id+'"';
  }
  
  var p_title='';
  if (roomname!=''){
    p_title=' title="'+roomname+'" ';
  }
  
  
  var point=$('<div class="mapPoiWrapper"><a href="map.php" class="mapPoi" chat_id="0" '+ id_s + p_title+'></a></div>');
  
  if (chat_id>0){
    $(editBox).find(".poi_dest").val(chat_id);
    $(point).find(".mapPoi").attr("chat_id",chat_id);
    $(point).find(".mapPoi").attr('href','map.php?roomid='+chat_id);
    //alert( $(point).attr("chat_id") );
  }
  
  $(box).append(point).append(editBox);
  
  $("#poiWrapper").append(box);
  
  $(box).css({left: x+'px', top: y+'px'});
  
  $(box).find(".editBox").click(function(eventObj){
  	eventObj.stopPropagation();
  });
  
  $(box).find("#newRoomC").change(function(){
  	
    if ( $(box).find("input[name='newRoomC']:checked").val() == 'NC'){
       $(box).find('.newRoom').show();
       $(box).find('.poi_dest').hide();
    }else{
    	$(box).find('.newRoom').val('');
    	$(box).find('.newRoom').hide();
    	$(box).find('.poi_dest').show();
    }
  });
  
   $(box).find(".canc").click(function(event){
  	
     $.post("map.php?s=1&roomid=<? echo $roomObj->getId() ?>",{id:$(box).find(".mapPoi").attr("id"),
                          action:'delete'
                          },
                          function(data){
                            $(box).fadeOut("slow", function() {
                              //quando termina l'effetto grafico li rimuovo
                              $(box).remove();
                            });
                          }
            );
     event.stopPropagation();
   });
   
   $(box).find(".save").click(function(event){
     
     //la post mi da come risultato un id..che assegno al punto
     $.post("map.php?s=1&roomid=<? echo $roomObj->getId() ?>",{id:$(box).find(".mapPoi").attr("id"),
                          x_c:$(box).find(".xy_val").attr("xval"),
                          y_c:$(box).find(".xy_val").attr("yval"),
                          poi_dest:$(box).find(".poi_dest option:selected").val(),
                          chat_new: $(box).find(".newRoom").val(),
                          action:'save'
                          },
                          function(data){
                            alert("Salvataggio completato");
                            $(box).find(".mapPoi").attr("id",$(data).filter("#point_id").html());
                            $(box).find(".mapPoi").attr("chat_id",$(data).filter("#point_chat").html());
                            $(box).find(".mapPoi").attr("title",$(data).filter("#point_title").html());
                            
                            $(box).find(".mapPoi").attr('href','map.php?roomid='+$(data).filter("#point_chat").html());
                            
                            $(data).filter('span#newChat').each(function(){
        						$(".poi_dest").append($(this).html());
        					});
                          }
            );
     
     event.stopPropagation();
   });
        
  //rendo il punto draggabile
  $(box).draggable({ 
          containment: '#poiWrapper',
          cursor: 'move',
          handle: '.editBox',
          stack: "#mapViewport",
          drag: function(event, ui){
             x_cn=$(this).offset().left - $("#poiWrapper").offset().left;
             y_cn=$(this).offset().top - $("#poiWrapper").offset().top;
             $(this).find(".xy_val").attr({xval: x_cn, yval:y_cn});
          }
        });
  
  //lo mostro con effetto di fade-in
  $(box).fadeIn('slow');
}

function loadPoi(){
  //alert("carico");
    $(".stored_poi").each(function(index,Element){
      //alert("entro");
      var c_x=$(this).attr("x_coord");
      var c_y=$(this).attr("y_coord");
      var c_id=$(this).attr("id");
      var r_id=$(this).attr("chat_id");
      var r_name=$(this).attr("chat_name");
      //alert(c_x + c_y + c_id);
      drawPoint(c_x,c_y,c_id,r_id,false,r_name);
      
    });
    
    //$(".mapPoi").tooltip();

}

function scroll_right(val) {
  
  px_to_scroll=3;
  
  $("#mapViewport").scrollLeft(px_to_scroll + $("#mapViewport").scrollLeft());
  val=val+px_to_scroll;
  
  //ricorro fintanto che non mi sono spostato di 1/3 del div
  if( val < portionToScroll )
    setTimeout("scroll_right("+val+")",10);  
}
  
function scroll_left(val) {
  
  px_to_scroll=-3;
  
  $("#mapViewport").scrollLeft($("#mapViewport").scrollLeft() + px_to_scroll);
  val=val+px_to_scroll;
  
  //ricorro fintanto che non mi sono spostato di 1/3 del div
  if( val > -portionToScroll )
    setTimeout("scroll_left("+val+")",10);  
}

function edit_mode(val){
  if(val){
     
     $('.editBox').fadeIn();
     $('.mapPoi').draggable( "enable" );
     //$('.mapPoi').unbind('click');
     
     $("#mapViewport").click(function(eventObj){
        
        var location = $("#mapImg").offset();
		var x_coord = eventObj.pageX - location.left;
		var y_coord = eventObj.pageY - location.top;
        
        drawPoint(x_coord,y_coord,0,0,true);
        
     });  
  }else{
     $('#mapViewport').unbind('click');
     $('.mapPoi').draggable( "disable" );
     $('.editBox').fadeOut();
     
  }
}

function toggledit(){
	
	$("#legendPanel").toggle('fast',function(){
    
      if($("#legendPanel").is(":visible")){
        edit_mode(true);
      }else{
        edit_mode(false);
      }
    });	
}

$(document).ready(function(){
   
  parent.show_room_thumb('<?php echo $roomObj->getThumb(); ?>');
  
  $("input[name='displaytype']:radio").change(function(){
    if ( $("input[name='displaytype']:checked").val() == 'newmap'){
        $('#newmap').show();
    }else{
    	$('#newmap').hide();
    }
  });
  
  $("input[name='roomlogo']:radio").change(function(){
    if ( $("input[name='roomlogo']:checked").val() == 'newlogo'){
        $('#newlogo').show();
    }else{
    	$('#newlogo').hide();
    }
  });
  
  $("input[name='private']:radio").change(function(){
    if ( $("input[name='private']:checked").val() == '1'){
        $('#newpvt').show();
    }else{
    	$('#newpvt').hide();
    }
  });
  
  $( ".buttonify" ).button();
  $(".buttonset").buttonset();
  
  $('.chat_description').editable('map.php?s=1&roomid=<? echo $roomObj->getId(); ?>', { 
                         id        : 'modify_desc',
                         name      : 'mod_desc',
                         type      : 'textarea',
                         cancel    : 'Annulla',
                         submit    : 'Salva',
                         indicator : '<img src="images/icons/loadinfo.net.gif">',
                         tooltip   : 'Click per modificare...',
                         onblur    : 'ignore',
                         cssclass  : 'edit_areaX',
                         data      : function(value, settings) {
                            // Convert <br> to newline. 
                            var retval = value.replace(/<br[\s\/]?>/gi, '\n').replace(/^\s\s*/, '').replace(/\s\s*$/, '');
                            return retval;
                            }
                         }
  );
  
  $("a.edit_link").click(function(){
                $('.'+this.rel).trigger('click');
                return false;
        });
  
  $("#mapViewport").bind('wheel',function(event,delta){
    if(delta < 0) {
      //scroll_left(0);
    }else{
      //scroll_right(0);
    }
  });
   

   loadPoi();
   edit_mode(false);
   
   
   var FormOptions = { 
        success:    function(data) { 
        	//alert('Stanza Aggiornata,\n ricarica per vedere le modifiche');
        	
        	
        	//prendo i feedback e li aggiungo
        	$(data).filter('span#newChat').each(function(){
        		$(".poi_dest").append($(this).html());
        	});
        	$(data).filter('span#PointAddResult').each(function(){
        		$('#subcp').html('Sottochat Presenti:');
        		$("#subchat_list").append($(this).html());
        	});
        	
        	
        	
    	} 
    }; 
   $('form.special').ajaxForm(FormOptions);
   
   $('#legendPanel').gearPanel(toggledit);
   
   
   portionToScroll=$("#mapViewport").width()/3;
   
   $(window).focus();
   
});

</script>
</head>
<body>
  <div id="container">
    <?php
    
    
	if($canEditMap){
		$editclass='class="chat_description"';
		$edit_field="<a href=\"#\" class=\"edit_link\" rel=\"chat_description\"><img src=\"images/icons/pencil_16.png\" border=\"0\" /></a>";
	}
	
	if($roomObj->getDefault()==0 || $map_showdefault_description)
		echo "<div class=\"chatDescriptionBox\"><div class=\"enterLink\" >{$roomObj->getName()} $edit_field</div><div $editclass>".acapo($roomObj->getDescription())."</div>";
	
	
	if($roomObj->getInstantiable()>0){
		echo '<div class="roundcorner panel_bg center centertxt" style="width: 400px; padding: 10px;">
		<form action="chat.php">
			<input type="hidden" name="room" value="'.$roomObj->getId().'" />
			<table>
				<tr>
					<td><p>Inserisci il codice per accedere alla stanza privata.</p>
						<p>Ricorda: chiunque inserirà lo stesso codice potrà accedere alla tua stanza, quindi sceglilo con cura!</p>
					</td>
				</tr>
				<tr>
					<td><input type="password" placeholder="codice accesso" name="chat_instance" /></td>
				</tr>
				<tr>
					<td><input type="submit" value="Entra" class="buttonify" /></td>
				</tr>
				
			</table>
		</form>
    	</div>';
	}elseif($roomObj->getChattable()>0){
		
		$playY="checked=\"checked\" ";
		echo "<div class=\"enterLink\"><a href=\"chat.php?room={$roomObj->getId()}\"  class=\"buttonify\">Entra</a></div>";
		
	}else{
		$playN="checked=\"checked\" ";
	}
	echo "</div>";    
    
    
    
	//apro il roomObj e vedo se Map è =0 o se è un immagine
	
	switch ($roomObj->getMap()) {
	case '0':
		//se è 0 mostro le sottochat come lista
		
		
		
		$ckdList="checked=\"checked\" ";
		$dscString='<p><input type="checkbox" name="action" value="save" id="ckb_add" /><label for="ckb_add">Aggiungi una stanza all\'elenco:</label>
						'.$chat_list.'
					</p>';
		
		
		
		$subChLI="";
		$i=0;
		foreach($roomObj->getSubChat() as $k=>$v){
			
			if($canEditMap)
				$poiDel="<a href=\"map.php?s=1&roomid={$roomObj->getId()}&action=delete&id={$v->getId()}\"><img src=\"images/icons/delete.png\" border=\"0\" alt=\"delete\" /></a>";
			
			$subChLI.="<li><a href=\"map.php?roomid={$v->getRoomDest()}\">{$v->getRoomName()}</a>$poiDel</li>";
			$i++;
		}
		
		$strH3="";
		if($i>0) $strH3="Sottochat Presenti:";
		
		echo "<h3 id=\"subcp\">$strH3</h3><ul id=\"subchat_list\"> $subChLI </ul>";
		
		
		break;
	
	default:
		
		$ckdMap="checked=\"checked\" ";
		$dscString='<p>Clicka sulla mappa per aggiungere un nuovo Punto di Interesse<br/>
      Puoi spostare tutti i punti e salvare le modifiche, o cancellare il punto definitivamente.</p>';
		
		$size = getimagesize($roomObj->getMap());
		
		echo '<div id="mapViewport" class="mViewport" style="width:'.$size[0].'px;height:'.$size[1].'px">
				<div id="poiWrapper" style="width:'.$size[0].'px;height:'.$size[1].'px">
					<img src="'.$roomObj->getMap().'" id="mapImg" width="'.$size[0].'" height="'.$size[1].'" border="0" alt=""/>
          		</div>
          	  </div>
				';
		
		foreach($roomObj->getSubChat() as $k=>$v){
			echo "<span class=\"stored_poi\" id=\"{$v->getId()}\" x_coord=\"{$v->getX()}\" y_coord=\"{$v->getY()}\" chat_id=\"{$v->getRoomDest()}\" chat_name=\"".addslashes($v->getRoomName())."\"></span>\n";
		}
		
		break;
	}
	
	
	
	if($roomObj->getPrivate()>0){
	 $pvtY="checked=\"checked\" ";
	 $roomObj->readAccess();
	 $pvtAdmin=$roomObj->getAdmins();
	}
	else{
	 $pvtN="checked=\"checked\" ";
	 $pvtAdmin="";
	}
	
	if($roomObj->getInstantiable()>0){
	 $hotelY="checked=\"checked\" ";
	}
	else{
	 $hotelN="checked=\"checked\" ";
	}
	
    if($canEditMap){
    ?>
    
    <div id="legendPanel">
      <form action="map.php?s=1&roomid=<?php echo $roomObj->getId(); ?>" enctype="multipart/form-data" method="post" class="special">
      <div style="width:65%;float:left;border: 1px dashed #cccccc;">
      	<h3>Modifica di questa stanza</h3>
      <p>Nome della stanza: <input type="text" name="newname" value="<? echo $roomObj->getName(); ?>" /></p>
      <p class="buttonset">Stanza giocabile:
      		<input type="radio" id="playN" name="playable" value="0" <?php echo $playN; ?>/><label for="playN">No</label>
      		<input type="radio" id="playY" name="playable" value="1" <?php echo $playY; ?>/><label for="playY">Si</label>
      </p>
      <p class="buttonset">Stanza Privata
      		<input type="radio" id="pvtN" name="private" value="0" <?php echo $pvtN; ?>/><label for="pvtN">No</label>
      		<input type="radio" id="pvtY" name="private" value="1" <?php echo $pvtY; ?>/><label for="pvtY">Si</label>
      			<div id="newpvt" style="display:none;">Gestori (sparati da virgola):<input type="text" name="pvtAdmin" value="<?php echo $pvtAdmin; ?>" /></div>
      </p>
      <p class="buttonset">Stanza Hotel
      		<input type="radio" id="hotelN" name="hotel" value="0" <?php echo $hotelN; ?>/><label for="hotelN">No</label>
      		<input type="radio" id="hotelY" name="hotel" value="1" <?php echo $hotelY; ?>/><label for="hotelY">Si</label>
      </p>
      <p class="buttonset">Mostra le sottochat sotto forma di<br />
      		<input type="radio" id="mapTL" name="displaytype" value="list" <?php echo $ckdList; ?>/><label for="mapTL">Elenco</label>
      		<input type="radio" id="mapTM" name="displaytype" value="map" <?php echo $ckdMap; ?>/><label for="mapTM">Mappa corrente</label>
      		<input type="radio" name="displaytype" value="newmap" id="newmapR"/><label for="newmapR">Nuova Mappa</label>
      				<div id="newmap" style="display:none;"><input type="file" name="map_img" id="map_img" /></div>
      </p>
      <p class="buttonset">Desideri cambiare l'immagine relativa a questa stanza?<br />
      		<input type="radio" name="roomlogo" value="current" id="oldL" checked="checked"/><label for="oldL">No</label>
      		<input type="radio" name="roomlogo" value="newlogo" id="newL" /><label for="newL">Si</label>
      				<div id="newlogo" style="display:none;"><input type="file" name="room_img" id="room_img" /></div>
      </p>
      
      <?php
      	echo $dscString;
      ?>
      </div>
      <div style="width:30%;float:right;border: 1px dashed #cccccc;">
      	<h3>Creazione di una nuova Stanza</h3>
      	<input type="text" name="newchat" />
      </div>
      	<div class="centertxt clearboth" style="margin-top:10px;"><input type="submit" value="Salva" class="buttonify" /></div>
      </form>
      </div>
    </div>
    <?php
    }
	?>
  </div>
</body>
</html>