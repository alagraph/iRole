<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

 
require_once("config.php");
require_once("libs/common.php");
require_once("libs/pm_lib.php");

if(!isset($_SESSION))
{
session_start();
}

logged();

$ref_id=$_SESSION['id'];
if ($bind_pm_char)
	$ref_id=$_SESSION['char_id'];
	
$reply_to=0;

if (isset($_REQUEST['rply']) && intval($_REQUEST['rply'])>0){
	$reply_to=intval($_REQUEST['rply']);
	
	$PM_obj=new PrivateMessage(null,$_REQUEST['rply']);
    $PM_obj->pm_superread($ref_id);
	
	$page_title="$missive: ".$PM_obj->getSubject();
	
}

if (isset($_REQUEST['mailto'])){
	
	$rcp_val=$_REQUEST['mailto'];
}


?>


<script>
$(function() {
	
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast( term ) {
		return split( term ).pop();
	}
	
	
	$( "#recipient" )
			// don't navigate away from the field on tab when selecting an item
			.bind( "keydown", function( event ) {
				if ( event.keyCode === $.ui.keyCode.TAB &&
						$( this ).data( "autocomplete" ).menu.active ) {
					event.preventDefault();
				}
			})
			.autocomplete({
				source: function( request, response ) {
					$.getJSON( "char_list.php", {
						term: extractLast( request.term )
					}, response );
				},
				search: function() {
					// custom minLength
					var term = extractLast( this.value );
					if ( term.length < 2 ) {
						return false;
					}
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui ) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push( "" );
					this.value = terms.join( ", " );
					return false;
				}
			});
	
	
	$( ".buttonify" ).button();
	$( ".buttonset" ).buttonset();
	$('textarea#message').autoResize({
	    extraSpace: 13
	});
	
});
</script>

<div class="center">
<?php

if (isset($_REQUEST['s']) && $_REQUEST['s']==1) {
	if ( isset($_REQUEST['recipient']) && isset($_REQUEST['message'])){
	
	//splitto i destinatari
	$dest=explode(',',$_REQUEST['recipient']);
	foreach($dest as $k=>$v){
			
		$v=trim($v);
		if(strlen($v)<1)
			continue;	
			
		echo "<br />Destinatario: $v - ";	
	    $newPM_obj=new PrivateMessage(null,null);
	    echo $newPM_obj->sendNew($ref_id,$v,$_REQUEST['subject'],$_REQUEST['message'],$reply_to);
		
		unset($newPM_obj);
	}
	
	
	  
		exit();
	}
	echo "Devi inserire il destinatario.";
}

if (isset($_REQUEST['del'])) {
    
  $delId=$_REQUEST['del'];
  $delCmd=substr($delId,0,1);
  if ($delCmd=='0' || $delCmd=='x')
    $delId=substr($_REQUEST['del'],1);
  
  $delMsg=new PrivateMessage(null,$delId);
  $delMsg->pm_superread();
  
  
  if($delMsg->delete($ref_id,true)){
	echo $GLOBALS['missive']." Cancellato.</div>";
	exit();
  }
	echo "Impossibile cancellare il {$GLOBALS['missive']}.</div>";
	exit();	
}

?></div>
<form id="form1" name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">

  	<?php
	  $answers='';
  	if (isset($_REQUEST['rply'])){
  		// se è una reply apro il mesaggio e guardo tutte le reply precedenti

      
      if (!$PM_obj->Exists() || !$PM_obj->canAccess()){ //se il messaggio non esiste, o non è mio mi fermo
        echo "Errore.";
        exit();
      }
  		
  		
  		$pm_arr_rply=explode(',',$PM_obj->getReplyTo());
      
      $subject=$PM_obj->getSubject();
      if ($subject=="") $subject="Nessun Oggetto";
      
  		//lo imposto come letto (ma solo se sono io il recipient, altrimenti imposterei come letti anche i messaggi nell' outbox)
  		if($PM_obj->getRecipient()==$ref_id){
  			$PM_obj->setViewed();
  			$answDest=$PM_obj->getSender();
  		}else{
  			$answDest=$PM_obj->getRecipient();	
  		}
		
		$answObj=new Character($answDest);
		$answObj->checkExistance();
	  
	  
	  
	  
      for($i=0;$i<count($pm_arr_rply);$i++){
        if ($pm_arr_rply[$i]==0)
          continue;
        $PM_subobj=new PrivateMessage(null,$pm_arr_rply[$i]);
        $PM_subobj->pm_superread($ref_id);
        
        if(!$PM_subobj->canAccess())
          continue;
		
		
		if($PM_subobj)
		
		if($PM_subobj->getRecipient()==$ref_id) $PM_subobj->setViewed();
		  
		if($PM_subobj->getSender()==$ref_id){
			$asw="answerBox2";
		}else {$asw="answerBox1";}

		  $answers.='<div class="roundcorner clearborder answerBox clearboth '.$asw.'">
		  				<div class="answerBoxDate">'.itaTime($PM_subobj->getSentDate()).'</div>
		  				<div class="answerBoxAuth">'.$PM_subobj->getSenderNameLink().'</div>
		  				<div class="answerBoxMsg">'.acapo($PM_subobj->getMessage()).'</div>
		  			 </div>';

      }
  		
		if($PM_obj->getSender()==$ref_id){
			$asw="answerBox2";
		}else {$asw="answerBox1";}
		
  		//e infine stampo il messaggio in questione
  		$answers.='<div class="roundcorner clearborder answerBox clearboth '.$asw.'">
		  				<div class="answerBoxDate">'.itaTime($PM_obj->getSentDate()).'</div>
		  				<div class="answerBoxAuth">'.$PM_obj->getSenderNameLink().'</div>
		  				<div class="answerBoxMsg">'.acapo($PM_obj->getMessage()).'</div>
		  			 </div>';
		
		$canAttachOriginal="<input type=\"hidden\" name=\"rply\" value=\"".$_REQUEST['rply']."\" />";
		
		$objRow=$PM_obj->getSubject() . "<input type=\"hidden\" name=\"subject\" value=\"{$PM_obj->getSubject()}\" />";
		$recipientRow=$answObj->getCharName().'<input type="hidden" name="recipient" value="'.$answObj->getCharName().'" />';
		
		  			 
  	}
  	else{
  		
		$noObj=true;
		$canAttachOriginal='';
		$objRow='<input type="text" name="subject" placeholder="Oggetto..." />';
		$recipientRow='<input type="text" name="recipient" id="recipient" value="'.$rcp_val.'" placeholder="Destinatario..."/>';
		
  	}
  	?>
  		
  			<?php
  			
  			
  			if($answers!=''){
  				echo "<div class=\"panel_bg center centertxt roundcorner clearborder pm_cronologia\">Cronologia della discussione <strong>$objRow</strong></div>
  					  <div style=\"margin: 0 auto; margin-top: 20px; width: 90%;\">$answers
  					  
  					  $canAttachOriginal
  					  </div>";
		    }
  			
			echo "<table class=\"message_table\"><tr><td>$recipientRow</td></tr>";
  			
			if($noObj) echo "<tr><td>$objRow</td></tr>";
			
			?>
  			<tr>
  				<td><input type="hidden" name="s" value="1" /><textarea name="message" style="width:60%;height:20px;padding: 7px 15px;" id="message" placeholder="Corpo del messaggio..."></textarea></td>
  			</tr>
  			<tr>
  				<td><input type="submit" name="Submit" value="Invia" class="buttonify" /></td>
  			</tr>
  		</table>
</form>