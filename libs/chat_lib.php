<?php
/*	I codici qui inseriti sono proprietà di
 *	Francesco Stasi, pertanto ne è vietata la
 *	vendita e la cessione a terzi senza previa
 *	autorizzazione dell' autore.
 */

require_once("config.php");
require_once("common.php");
require_once("libs/character_lib.php");
require_once("libs/ability_lib.php");
require_once("libs/bbcode_parser.php");

if(!isset($_SESSION))
{
session_start();
}

class chat_message {
  
  private $id;
  private $char_id;
  private $char_name;
  private $char_ip;
  private $char_id_dest;
  private $room_id;
  private $instance;
  private $datetime;
  private $message;
  private $chat_tag;
  private $special; //normal action whisper command
  
  
  public function __construct($row=null,$forceRead=null,$insertArray=null){

    //routine di parsing dalla stringa row
    if (isset($row)){
      $this->id=$row['id'];
      $this->char_id=$row['char_id'];
      $this->char_name=$row['sendername'];
      $this->char_ip=$row['char_ip'];
      $this->char_id_dest=$row['char_id_dest'];
      $this->room_id=$row['room_id'];
      $this->datetime=$row['datetime'];
      $this->message=$row['message'];
      $this->chat_tag=$row['chat_tag'];
      $this->special=$row['special']; 
	  $this->instance=$row['instance'];      
    }      
    
    //routine di lettura forzata (unused)
    if (isset($forceRead))
        $this->parseFromDb($forceRead);
    
    // routine di inserimento
    if (isset($insertArray)){
        
      if (isset($insertArray['char_id'])){
        $this->char_id=$insertArray['char_id'];
      }else{
        $this->char_id=$_SESSION['char_id'];
      }
      
      if (isset($insertArray['room_id'])){
        $this->room_id=$insertArray['room_id'];
      }else{
        $this->room_id=$_SESSION['room_id'];
      }
	  
	  if (isset($insertArray['instance'])){
        $this->instance=$insertArray['instance'];
      }else{
        $this->instance=0;
      }
	  
      if (isset($insertArray['datetime'])){
        $this->datetime=$insertArray['datetime'];
      }else{
        $this->datetime=date("YmdHis");
      }
      
      if (isset($insertArray['char_ip'])){
        $this->char_ip=$insertArray['char_ip'];
      }else{
        $this->char_ip=$_SERVER['REMOTE_ADDR'];
      }
      
      if (isset($insertArray['message'])){
        $this->message=$insertArray['message'];
      }else{
        $this->message='';
      }
      
      if (isset($insertArray['tag'])){
        $this->chat_tag=$insertArray['tag'];
      }else{
        $this->chat_tag='';
      }
      
      $senderChar_obj=new Character($this->char_id);
      $senderChar_obj->parseFromDb();
      if (!$senderChar_obj->exists()){
        echo "Sender Insesistente";
        return false;
      }
    
      //if (strlen($this->message)==0)
        
        
      $this->special=0;
      $this->char_id_dest=0;
	  
	  $word_count=false;
  
      //controllo se il messaggio è un comando  
      switch(substr($this->message, 0, 1)) {
        
        case "{$GLOBALS['action_symbol']}": //è un azione
              $text=substr($this->message, 1); //tolgo il primo carattere
              $this->special=1;
              $word_count=true;
              break;
          
        case "{$GLOBALS['whisper_symbol']}": //è un sussurro
              $this->special=2;
              $this->char_id_dest=$this->char_id;
              $text="Per inviare un sussurro: {$GLOBALS['whisper_symbol']}destinatario{$GLOBALS['whisper_symbol']}sussurro";
              
              if (preg_match("/^{$GLOBALS['whisper_symbol']}(?P<dst>[a-zA-Z0-9]+){$GLOBALS['whisper_symbol']}(?P<mex>.+)$/i",$this->message,$matches)){
                $receiverChar_obj=new Character(null,$matches['dst']);
                $receiverChar_obj->checkExistance();
                $this->char_id_dest=$receiverChar_obj->getCharId();
                if ($receiverChar_obj->exists()){
                  $text=$matches['mex'];
				  $word_count=true;
                }else{
                  $this->char_id_dest=$this->char_id;
                  $text="Impossibile sussurrare a {$matches['dst']}: personaggio inesistente";
                }
              }
              break;
    
        case "{$GLOBALS['master_symbol']}": //è un azione di un master
            //se il pg è un master, eseguo e poi faccio il return direttamente
            $master_level=$senderChar_obj->getMasterLevel();
			
			if($senderChar_obj->Account()->getModLevel() >= $GLOBALS['admin_add_px_required'])
				$master_level=count($GLOBALS['master_level_array'])-1;
            if ($master_level>0){
              $text=substr($this->message, 1); //tolgo il primo carattere
              $this->special=0 - $master_level; // imposto lo special allo stesso livello del masterlevel, ma negativo
			  $master_count=true;
              break;
            }
        
        case "{$GLOBALS['command_symbol']}": //è un comando
            //devo riconoscere un comando più lungo
            $this->char_id_dest=$this->char_id;
            $this->special=3;
            
            //comando di list
            if (substr($this->message, 1)=='list'){
              $text="Elenco dei presenti in chat:";
              $result=online_list($this->room_id);
              while ($row=mysql_fetch_array($result)){
                $text.="\r\n".$row['name'];
              }
              break;
            }
			
			//elenco dei comandi di chat
            if (substr($this->message, 1)=='cmd'){
              $text="Elenco dei comandi di chat:
              		 
              		 == Comandi Base ==
			  		 {$GLOBALS['action_symbol']}azione
			  		 {$GLOBALS['whisper_symbol']}destinatario{$GLOBALS['whisper_symbol']}sussuro
			  		 {$GLOBALS['command_symbol']}list (mostra i presenti nella chat)
			  		 {$GLOBALS['command_symbol']}cmd (mostra questo menu)
			  		 
			  		 == Comandi per i Dadi ==
					 {$GLOBALS['command_symbol']}roll x (dove x è il numero di facce del dado; es: {$GLOBALS['command_symbol']}roll 4 lancia un d4 )
			  		 {$GLOBALS['command_symbol']}rolls y x (lancia y dadi a x facce; es: {$GLOBALS['command_symbol']}rolls 3 4 lancia 3d4 )
			  		 
			  		 == Comandi per i Master Fati ==
			  		 {$GLOBALS['master_symbol']}testo del master fati
			  		 {$GLOBALS['command_symbol']}png NomePng Testo (es: .png Legolas Sorge un sole rosso. Stanotte è stato versato del sangue. )
			  		 
			  		 
			  		 ";
			  
			  
              break;
            }
            
            if (substr($this->message, 1,5)=='rolls'){
              $text="tira ";
              $min=1;
              $arr_rolls=explode(' ',trim(substr($this->message, 6)),2);
              
              if(isset($arr_rolls[0])){
                $num_rolls=intval($arr_rolls[0]);
              }else {$num_rolls=1;}
              
              if(isset($arr_rolls[1])){
                $max_rolls=intval($arr_rolls[1]);
              }else {$max_rolls=10;}
              
              for($n_roll=0;$n_roll<$num_rolls;$n_roll++){
                $text.=mt_rand($min,$max_rolls)."/".$max_rolls." ";
              }
              
              $this->char_id_dest=0;
              
              break;
            }
			
			
			if (substr($this->message, 1,3)=='png'){
              
			  
			  $expl=explode(" ", substr($this->message, 5) ,2);
			  
			  if(count($expl)>1){
				  $this->special=4;
				  $word_count=false;	
				  $text=$expl[0]."|~|".$expl[1];	
	            
	              $this->char_id_dest=0;
			  	
			  }
              
              break;
            }
			
			if (substr($this->message, 1,4)=='cast'){
              
			  
			  $text=trim(substr($this->message, 5)); //tolgo il primo carattere
              $this->special=5;
              $word_count=true;
              break;
              
              break;
            }
            
			if (substr($this->message, 1,7)=='roll_ab'){
              	
				
              $text="tira ";
              $min=1;
              $ab=trim(substr($this->message, 8));
              $this->char_id_dest=0;
			  
			  //seleziono se si tratta di una stat o di una abilità
			  if(substr($ab,0,5)=='stat_'){
				//è una statistica
			  	
				if(!isset($GLOBALS['allow_roll_stats']) || $GLOBALS['allow_roll_stats']<=0){
					$text="Non è possibile lanciare statistiche.";
					break;
				}
				
				$ab=substr($ab,5);
				
				$max=$GLOBALS['allow_roll_stats'];
				
				$charStArr=$senderChar_obj->getStats(true);
				
				if(array_key_exists($ab,$charStArr)){
					
					$minus= isset($GLOBALS['roll_stats_minus'])? $GLOBALS['roll_stats_minus'] : 0 ;
					$charbonus=$charStArr[$ab]-$minus;
				}else{
					$text="Statistica inesistente";
					break;
				}
				
				$randomNumber=mt_rand($min,$max);
				$totRoll=$randomNumber+$charbonus;
				
				$text.=$randomNumber."/".$max." + $charbonus(bonus {$ab}) = $totRoll";
              	break;
				
				
				
			  }else{
			  	//è l'abilita' normale
			  	
			  	if($GLOBALS['allow_roll_abilityes']<=0){
					$text="Non è possibile lanciare abilità.";
					break;
				}
				
			  	$max=$GLOBALS['allow_roll_abilityes'];
				
				$rollingAb=new Ability(null,$ab);
			  	$rollingAb->readFromDb();
			  	$rollOwnAb=$rollingAb->isOwnedBy($_SESSION['char_id']);
			  
			  	if($rollOwnAb!=false){
			  		$rollAbLv=$rollOwnAb->getLevel();
			  	}else {$rollAbLv=0;}
			  
			  	if ($GLOBALS['allow_roll_only_owned'] && $rollAbLv==0){
			  		$text="Abilità non posseduta";
					break;
			  	}else{
			  		
				  	$charbonus=0;
				  	$namestatbonus="";
				  	$charStArr=$senderChar_obj->getStats(true);
					$newLv_for_staticAb=0;
					
					//scorro il vettore dei bonus della abilità che sto lanciando
			  		$minus= isset($GLOBALS['roll_abilities_minus'])? $GLOBALS['roll_abilities_minus'] : 0 ;
					$abStatsBonus=$rollingAb->getStatsBonusArray();
					
					foreach($rollingAb->getStatsBindArray() as $k=>$v){
			  			
			  			if($v>0){ //se binda, ho due possibilità
			  				
			  				
			  				//se $abilitiesImproveStats è true sommo il la stat del pg
			  				if(isset($GLOBALS['abilitiesImproveStats']) && $GLOBALS['abilitiesImproveStats']==true ){
			  					$charbonus+=$charStArr[$k]-$minus;
			  				}else{//se è false sommo il bonus (fisso)
			  					$charbonus=$abStatsBonus[$k]-$minus;
			  				}
			  				
			  				
							$newLv_for_staticAb=$v;	
							$namestatbonus.=" $k";
						}
			  		}
				
			  	}
				
				$randomNumber=mt_rand($min,$max);
			  	
			  	if($rollingAb->getMaxlevel()<=1){
					//se il livello massimo dell'ablità è <=1 allora prendo come bonus di livello quello specificato dentro la carettristica	
					$rollAbLv=$newLv_for_staticAb;
				}
			  	
			  	$lvBonus=$rollAbLv;
			  
			  	$totRoll=$randomNumber+$lvBonus+$charbonus;
				
              	$text.=$randomNumber."/".$max." + $lvBonus(liv. {$rollingAb->getName()}) + $charbonus(bonus $namestatbonus) = $totRoll";
              
              	break;
			  
			  
			  }
			  
            }
			
            if (substr($this->message, 1,4)=='roll'){
              $text="tira ";
              
              $min=1;
              $max=trim(substr($this->message, 5));
              if (!isset($max) || empty($max)){
                $max=100;
              }elseif ($max<$min){
                $max=$min;
              }
              $max=intval($max);
              
              $this->char_id_dest=0;
              $res=mt_rand($min,$max);
              $text.=$res."/".$max;
              break;
            }
            
    
        default:
            $this->special=0;
            $this->char_id_dest=0;
            $text=$this->message;
			$word_count=true;
            break;
      }
  
      //ripulisco il messaggio e il tag
      $this->chat_tag=htmlspecialchars(trim($this->chat_tag));
      $this->message=htmlspecialchars(trim($text));
  
      if (strlen($this->message)==0)
        return false;
        
      if($word_count && $GLOBALS['giveExp_every']>0){
      	//conteggio le parole
      	
      	$num_words=str_word_count($this->message);
		$senderChar_obj->addWordCount($num_words,0);
		
      }
	  if($master_count && $GLOBALS['master_pts_wordcount']>0){
      	//conteggio le parole
      	
      	$num_words=str_word_count($this->message);
		$senderChar_obj->addWordCount($num_words,1);
		
      }
    
      //invio tutto sul database
      $this->parseToDb();
    
    }
  
  } 
  
  public function __destruct(){
    //non devo fare nulla
  }
  
  private function parseFromFb($id){
      
    // scrivo il messaggio su database  
    $query="SELECT rc.*, c.name AS sendername FROM room_chat rc LEFT OUTER JOIN `character` c
              ON rc.char_id=c.id
              WHERE rc.id='{$id}'";
    $result=mysql_query($query) or die(mysql_error());
      
    $row=mysql_fetch_array($result);
    
    $this->__construct($row);
    
    return;
  }
  
  private function parseToDb(){
    $query="INSERT INTO room_chat (char_id,char_id_dest,room_id,instance,datetime,message,chat_tag,special,char_ip)
            VALUES ('{$this->char_id}','{$this->char_id_dest}','{$this->room_id}','{$this->instance}','{$this->datetime}','{$this->message}','{$this->chat_tag}','{$this->special}','{$this->char_ip}')";
    mysql_query($query) or die(mysql_error());
  
    return mysql_affected_rows();
  }

  public function printMessage($xtend=false,$timeshow=true){
    $tag="";
    if ($this->chat_tag!="")
      $tag="<span class=\"chat_tag\">".$GLOBALS['tag_symbol'][0] . $this->chat_tag . $GLOBALS['tag_symbol'][1]."</span>";
    
    $time="";
    if($timeshow)
      $time="<span class=\"chat_time\">[".itaTime($this->datetime,true)."] </span>";
	  
	if($xtend)
	  $time="<span class=\"chat_time\">[".itaTime($this->datetime)."] </span>";
	
	$authorCharObj=new Character(null,$this->char_name);
	$authorLink=$authorCharObj->getCharNameLink();
	
	if($GLOBALS['show_groupsicons_inchat']){
		$authorCharObj->checkExistance();
		foreach($authorCharObj->getGroups() as $k=>$ge){
			$authorLogo.=$ge->getImage(true)." ";
		}
		$authorLogo.=$authorLink;
		$authorLink=$authorLogo;
	}
	
      
    switch($this->special) {
        
    case 0:
        $class="chat_say";
        $print="<div class=\"$class\">$time {$authorLink} $tag: ".BBCode2Html($this->message,true)."</div>";
        break;
    
    case 1: //è un azione
        $class="chat_action";
        $print="<div class=\"$class\">$time {$authorLink} $tag ".BBCode2Html($this->message,true)."</div>";
        break;
          
    case 2: //è un sussurro
        if ($this->char_id==$_SESSION['char_id']) {//l'ho inviato io
          $class="chat_outwhisp";
          $destCharObj=new Character($this->char_id_dest);
          $destCharObj->checkExistance();
          $to=$destCharObj->getCharName();
          $print="<div class=\"$class\">$time Hai sussurrato a $to: {$this->message}</div>";
        }
        elseif ($this->char_id_dest==$_SESSION['char_id']) { //sono il destinatario
          $class="chat_inwhisp";
          $print="<div class=\"$class\">$time {$this->char_name} ti sussurra: {$this->message}</div>";
        }
        elseif ($xtend){
          $class="chat_adminwhisp";
          $destCharObj=new Character($this->char_id_dest);
          $destCharObj->checkExistance();
          $to=$destCharObj->getCharNameLink();
          
          $print="<div class=\"$class\">$time {$authorLink} sussurra a {$to}: {$this->message}</div>";
        }
		else {
			return; //se è un sussurro, ma non per me, lo scarto
		}
        break;
          
    case 3: //è un comando
        $class="chat_command";
        if ($this->char_id_dest==$_SESSION['char_id']){ //è solo per me
          $print="<div class=\"$class\">$time {$this->message}</div>";
        }
        elseif ($this->char_id_dest==0){ //è per tutti
          $print="<div class=\"$class\">$time {$authorLink} {$this->message}</div>";
        }
        else{
        	return; //se non è per me, nè per tutti, lo butto
        }
        break;
		
	case 4: //è un png
        $class="chat_png"; 
        
        $arr=explode("|~|", $this->message,2);
        if (count($arr)>1){
          $print="<div class=\"$class\">$time {$arr[0]}(png) $tag: {$arr[1]}</div>";
        }
        break;
		
	case 5: //è un cast
        $class="chat_cast";
        $print="<div class=\"$class\">$time {$authorLink} $tag ".BBCode2Html($this->message,true)."</div>";
        break;

    default: //è l'azione di un master
        $class="chat_master".$this->special;
        if ($xtend){
          $printAuth="($authorLink)";
        }else{ $printAuth=""; }
          
        $print="<div class=\"$class\">$time $tag {$printAuth} ".BBCode2Html($this->message,true)."</div>";
    }
  
  
  $print="<div class=\"chat_s_message\" id=\"{$this->id}\">{$print}</div>";
  
  echo acapo($print)."\n";
  }

  public function getInstance(){
  	return $this->instance;
  }
  
}


class Room {
	
  private $id;
  private $name;
  private $private;
  private $description;
  private $map;
  private $chattable;
  private $default;
  private $thumb;
  private $meteo;
  private $instantiable;
	
  private $datetime=0;
  
  private $instance;
  
  public $chat_array=array();

  private $user_rights=1; //se > di zero garantisce l'accesso anche in caso di private=1
  /* 
   * values for $user_rights:
   * 
   * 0=normal;
   * 1=guest;
   * 2=mod;
   * 3=admin;
   * 
   */ 
   
  private $subChats_array=array();
  /*
   * Contiene le sottochat di mapPoi. vengono mostrate in lista o sulla mappa, a seconsa del valore di map
   */
   
  private $access_array=array();
  
  public function __construct($id=null,$name=null,$charid=null,$row=null,$date_chat=null,$room_instance=0){
  	
	
	//costruisco l'oggetto in base ai parametri ricevuti
    if (isset($id)){
	  $this->readFromDb($id);
    }elseif (isset($name)){
      $this->readFromDb(null,$name);
    }elseif (isset($row)){
		$this->populate($row);
	}
	
	if (isset($date_chat)){
      $this->SetDatetime($date_chat);
    }
	
	if ($this->private==1 && isset($charid)){
        $this->checkAccess($charid,true);
    }
	
	$this->instance=$room_instance;
  	
  }
  
  public function readFromDb($id=null,$name=null){
  	
	if (isset($id)){
      $query="SELECT r.* FROM room r
              WHERE r.id='$id'";
	}elseif (isset($name)){
      $query="SELECT r.* FROM room r
              WHERE r.name='$name'";
    }
    
	$result=mysql_query($query) or die(mysql_error());
	
	while($row=mysql_fetch_array($result))
		$this->populate($row);

	
  }
  
  public function populate($row){
        $this->id=$row['id'];
        $this->name=$row['name'];
        $this->private=$row['private'];
        $this->description=$row['description'];
		$this->map=$row['map'];
		$this->chattable=$row['chattable'];
		$this->default=$row['default'];
		$this->thumb=$row['thumb'];
		$this->meteo=$row['meteo'];
		
		$this->instantiable= empty($row['instantiable'])? 0 : intval($row['instantiable']) ;
  }
  
  public function writeToDb(){
  	
    if (isset($this->id)){ //l'id è settato, faccio l'update
      $query="UPDATE room SET
              name='{$this->name}',
              private='{$this->private}',
              description='{$this->description}',
              map='{$this->map}',
              chattable='{$this->chattable}',
              `default`='{$this->default}',
              thumb='{$this->thumb}',
              meteo='{$this->meteo}',
              instantiable='{$this->instantiable}'
              WHERE id='{$this->id}'";      
    }else{
      $query="INSERT INTO room SET
              name='{$this->name}',
              private='{$this->private}',
              description='{$this->description}',
              map='{$this->map}',
              chattable='{$this->chattable}',
              `default`='{$this->default}',
              meteo='{$this->meteo}',
              instantiable='{$this->instantiable}',
              thumb='{$this->thumb}'"; 
    }
    
    
    
    if (isset($query)){
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
          
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere
  }
  
  public function readSubchat(){
  	if (isset($this->id)){
			
		$query="SELECT mp.*, r.name AS roomname FROM map_poi mp, room r
              WHERE mp.map_id='{$this->id}' AND r.id=mp.poi_room_dest ORDER BY r.name";
      	$result=mysql_query($query) or die(mysql_error());
              
      	while($row=mysql_fetch_array($result)){
        	
        	$tmpPoi=new MapPoi($row);
        	$this->subChats_array[]=$tmpPoi;        
		
		}
  	}
  }
  
  public function addSubchat($d,$x=0,$y=0,$id=null,$roomN=null){
      
    if(isset($this->id)){
      $row=array();
      $row['id']=$id;
      $row['map_id']=$this->id;
      $row['poi_room_dest']=$d;
      $row['poi_X']=$x;
      $row['poi_Y']=$y;
	  $row['roomname']=$roomN;
      
      $tmp=new MapPoi($row);
      $tmp->writeToDb();
      
      $this->subChats_array[]=$tmp;
      
      return $tmp;
    }   
  }

  private function checkAccess($char_id,$setOwnField=true){
    
	if(!isset($this->id))
		return -1;
	
	$lvl=0;	
		
    $query="SELECT p.level
            FROM room_private p
            WHERE p.room_id='{$this->id}'
            AND p.char_id='{$char_id}'";
    
    $result = mysql_query($query) or die(mysql_error());
    
	if(mysql_num_rows($result)>0){
    	$row=mysql_fetch_array($result);
    	$lvl=$row['level'];

		
	}
	    if($setOwnField)
	    	$this->user_rights=$lvl;
	
    return $lvl;
  }
  
  public function readAccess(){
	if(!isset($this->id))
		return false;
	
  	$query="SELECT c.id,c.name,p.level
  			FROM room_private p
  			LEFT OUTER JOIN `character` c
  			ON p.char_id = c.id
  			WHERE p.room_id='{$this->id}'";
	
	$result=mysql_query($query) or die(mysql_error());
	
	$this->access_array=array();
	while($row=mysql_fetch_array($result)){
		
		$this->access_array[$row['id']]=array(
									'char' => new Character($row['id'],$row['name']),
									'level'=> $row['level']
									);
		
		
	}
	
	
	return $this->access_array;
	
	
  }
  
  public function grantAccess($char_id,$new_level,$force=false){
  	
	if(!isset($this->id))
		return false;
		
	switch ($this->user_rights) {
	case 2: //i mod possono creare guest
	
		$maxlevel=1; //possono promuovere fino a guest
		$allowDemote=1; //possono demotare le guest
		
		break;
	case 3: //gli admin possono creare anche admin
	
		$maxlevel=3;
		$allowDemote=3; //possono demotare guest mod e admin
		
		break;
	default:
		
		$maxlevel=0;
		$allowDemote=0;
		
		break;
	}
	
	//se force è true, allora non controllo il livello (mi considero admin)
	if($force){
		$maxlevel=3;
		$allowDemote=3;
	}
	
	//leggo il livello del bersaglio
	$destLvl=$this->checkAccess($char_id,false);

	if($new_level>$maxlevel || $maxlevel==0 || $destLvl>$allowDemote)
		return false;
		
	//se il maxlevel va bene, allora setto il diritto
	$query="DELETE FROM room_private
			WHERE room_id='{$this->id}'
			AND char_id='{$char_id}'";
	mysql_query($query) or die(mysql_error());
	
	unset($this->access_array[$char_id]);
	
	if ($new_level>0){
		
		$query="INSERT INTO room_private
			(room_id,char_id,level) VALUES
			('{$this->id}','{$char_id}','{$new_level}')";
		mysql_query($query) or die(mysql_error());
		
		$this->access_array[$char_id]=array(
											'char' => new Character(null,$char_id),
											'level'=> $new_level
											);
		
	}
	
	return true;		
	
  }
	
  private function parseChat(){
    //apro la chat solo se posso effettivamente vedere la stanza in questione  
    if ($this->getPrivate()==0 || $this->getUser_rights() >0){
	  
	  $ist="";
	  if($this->instance != -1) $ist="AND rc.instance='{$this->instance}'";	
		
      $query="SELECT rc.*, c.name AS sendername FROM room_chat rc LEFT OUTER JOIN `character` c
              ON rc.char_id=c.id
              WHERE rc.room_id='{$this->getId()}'
              $ist
              AND datetime >= '{$this->datetime}' ORDER BY rc.instance, rc.id";
      $result=mysql_query($query) or die(mysql_error());
      while ($row=mysql_fetch_array($result)){
        $tempMsg=new chat_message($row);
        $this->chat_array[]=$tempMsg;   
      }
      
    }
    
    return;
  }
  
  public function showChat($xtend=false){
    $this->parseChat();
	$prevInstance=0;
	
    foreach($this->chat_array as $key => $value) {
      	
      if(($value->getInstance()) != strval($prevInstance) ){
      	echo "<hr/>";
		$prevInstance=$value->getInstance();
      }	
		
      $value->printMessage($xtend);
    }
  }
  
  public function addMessage($message,$tag=null){
    
    //popolo l'array da passare alla funzione per la creazione del messaggio
    $createArray=array(); 
    $createArray['message']=$message;
    if (isset($tag))
      $createArray['tag']=$tag;
    $createArray['room_id']=$this->getId();
	$createArray['instance']=$this->instance;
      
    $tempMsg=new chat_message(null,null,$createArray);
    
    //lo posso inserire dentro il mio array al volo, ma è più sicuro rielggere da db per l'ordinamento corretto
    //$this->chat_array[]=$tempMsg;
  }
  
  /* SETTERS */
  public function SetDatetime($datetime){
    $this->datetime=$datetime;
  }
  public function setInstantiable($val){
  	$this->instantiable=$val;
	
	if(!isset($this->id))
		return false;
		
	$query="UPDATE room SET instantiable='{$this->instantiable}' WHERE id='{$this->id}'";
	mysql_query($query) or die(mysql_error());
	return true;
  }
  
  public function setDescription($dsc){
  	$this->description=$dsc;
	
	if(!isset($this->id))
		return false;
		
	$query="UPDATE room SET description='{$this->description}' WHERE id='{$this->id}'";
	mysql_query($query) or die(mysql_error());
	return true;
	
  }
  public function setMap($map){
  	$this->map=$map;
	
	if(!isset($this->id))
		return false;
		
	$query="UPDATE room SET map='{$this->map}' WHERE id='{$this->id}'";
	mysql_query($query) or die(mysql_error());
	return true;
	
  }
  public function setThumb($thumb){
  	$this->thumb=$thumb;
	
	if(!isset($this->id))
		return false;
		
	$query="UPDATE room SET thumb='{$this->thumb}' WHERE id='{$this->id}'";
	mysql_query($query) or die(mysql_error());
	return true;
	
  }
  
  public function storeImage($img,$ext,$type){
      
    if(isset($this->id) && isset($img) && $img!=''){
      
	  if ($type=='map'){
		$folder=$GLOBALS['maps_img_dir'];
	  }else{
	  	$folder=$GLOBALS['rooms_img_dir'];
	  }
	  
	  
	  $imgName=	$this->id.".".$ext;
		
      $upload=new Upload($img,$folder,$imgName);
      $this->image=$folder.$upload->GetFileName();
      @unlink($this->image);
      $upload->UploadFile();
	  if ($type=='map'){
        $this->setMap($this->image);
	  }else{
	  	$this->setThumb($this->image);
	  }
    }
    
  }
  
  public function setName($name){
  	$this->name=$name;
	
	if(!isset($this->id))
		return false;
		
	$query="UPDATE room SET name='{$this->name}' WHERE id='{$this->id}'";
	mysql_query($query) or die(mysql_error());
	return true;
	
  }
  public function setMeteo($m){
  	$this->meteo=$m;
	
	if(!isset($this->id))
		return false;
		
	$query="UPDATE room SET meteo='{$this->meteo}' WHERE id='{$this->id}'";
	mysql_query($query) or die(mysql_error());
	return true;
	
  }
  public function setPlayable($ply){
  	$this->chattable=intval($ply);
	
	if(!isset($this->id))
		return false;
		
	$query="UPDATE room SET chattable='{$this->chattable}' WHERE id='{$this->id}'";
	mysql_query($query) or die(mysql_error());
	return true;
	
  }
  public function setPrivate($pvt,$adminList=''){
  	
	$this->private=intval($pvt);
	
	if(!isset($this->id))
		return false;
		
	$query="UPDATE room SET private='{$this->private}'
			WHERE id='{$this->id}'";
	mysql_query($query) or die(mysql_error());	
	
	if($this->private==0){
		
		//svuoto la tabella
		$query="DELETE FROM room_private
				WHERE room_id='{$this->id}'";
		mysql_query($query) or die(mysql_error());
		
	}elseif($this->private==1){
				
		//svuoto da tutti i vecchi admin
		$query="DELETE FROM room_private
				WHERE room_id='{$this->id}' AND level='3'";
		mysql_query($query) or die(mysql_error());
		
		
		//inserisco i primi admin
		
		$adminArr=explode(',',$adminList);
		foreach($adminArr as $k=>$name){
			$tmpChar=new Character(null,$name);
			$tmpChar->checkExistance();
			if($tmpChar->exists())
				$this->grantAccess($tmpChar->getCharId(),3,true);
		}
		
	}
	
  }
  

  /* GETTERS */
  public function getDatetime(){
    return $this->datetime;
  }
  public function getInstantiable(){
    return $this->instantiable;
  }
  public function getId(){
    return $this->id;
  }
  public function getName(){
    return $this->name;
  }
  public function getDescription(){
    return $this->description;
  }
  public function getUser_rights(){
    return $this->user_rights;
  }
  public function getPrivate(){
    return $this->private;
  }
  public function getAdmins(){
    $retArr=array();
    
    foreach($this->access_array as $k=>$v){
    	
		if($v['level']==3){
			
			$retArr[]=$v['char']->getCharName();
		}
    }
	return implode(',',$retArr);	
	
  }
  public function getMap(){
    return $this->map;
  }
  public function getThumb(){
    return $this->thumb;
  }
  public function getMeteoCode(){
    return $this->meteo;
  }
  public function getSubchat(){
  	return $this->subChats_array;
  }
  public function getChattable(){
  	return $this->chattable;
  }
  public function getDefault(){
  	return $this->default;
  }
}

/**
 * 
 */
class MapPoi {
  
  private $id;
  private $map_id;
  private $poi_X;
  private $poi_Y;
  private $poi_W;
  private $poi_H;
  private $poi_shape;
  private $poi_room_dest;
  private $poi_special_url;
  
  private $roomname;
   
  
  public function __construct($row=null,$id=null) {
      
    if (isset($row)){
      $this->parse($row);
    }
    if (isset($id)){
      $this->id=$id;
    }  
    
  }
  
  public function parse($row){
      
    if(isset($row)){
      
      if (isset($row['id'])) $this->id=$row['id'];
      if (isset($row['map_id'])) $this->map_id=$row['map_id'];
      if (isset($row['poi_X'])) $this->poi_X=$row['poi_X'];
      if (isset($row['poi_Y'])) $this->poi_Y=$row['poi_Y'];
      if (isset($row['poi_W'])) $this->poi_W=$row['poi_W'];
      if (isset($row['poi_H'])) $this->poi_H=$row['poi_H'];
      if (isset($row['poi_shape'])) $this->poi_shape=$row['poi_shape'];
      if (isset($row['poi_room_dest'])) $this->poi_room_dest=$row['poi_room_dest'];
      if (isset($row['poi_special_url'])) $this->poi_special_url=$row['poi_special_url'];
      
      if (isset($row['roomname'])) $this->roomname=$row['roomname'];
      
    }
  }
  
  public function readFromDb(){
    if(isset($this->id)){
        $query="SELECT * FROM map_poi WHERE id='{$this->id}'";
        $result = mysql_query($query) or die(mysql_error());
      
        if (mysql_num_rows($result)>0){
          $this->parse(mysql_fetch_array($result));
        }
      }    
  }
  
  public function writeToDb(){
    
    if (isset($this->id)){ //l'id è settato, faccio l'update
      $query="UPDATE map_poi SET
              map_id='{$this->map_id}',
              poi_X='{$this->poi_X}',
              poi_Y='{$this->poi_Y}',
              poi_W='{$this->poi_W}',
              poi_H='{$this->poi_H}',
              poi_shape='{$this->poi_shape}',
              poi_room_dest='{$this->poi_room_dest}',
              poi_special_url='{$this->poi_special_url}'
              WHERE id='{$this->id}'";      
    }else{
      $query="INSERT INTO map_poi SET
              map_id='{$this->map_id}',
              poi_X='{$this->poi_X}',
              poi_Y='{$this->poi_Y}',
              poi_W='{$this->poi_W}',
              poi_H='{$this->poi_H}',
              poi_shape='{$this->poi_shape}',
              poi_room_dest='{$this->poi_room_dest}',
              poi_special_url='{$this->poi_special_url}'"; 
    }
    
    //echo $query;
    
    if (isset($query)){
        $result = mysql_query($query) or die(mysql_error());
        if (!isset($this->id))
          $this->id=mysql_insert_id();
          
        return 0;
      }
    
    return -1; //ritorno -1 quando non son riuscito a scrivere
  }

  public function poiDelete(){
    if(isset($this->id)){
      
      $query="DELETE FROM map_poi WHERE id='{$this->id}'";
      mysql_query($query) or die(mysql_error());
      
    }
  }
  
  public function getId(){
    return $this->id;
  }
  public function getMapid(){
    return $this->map_id;
  }
  public function getX(){
    return $this->poi_X;
  }
  public function getY(){
    return $this->poi_Y;
  }
  public function getRoomDest(){
    return $this->poi_room_dest;
  }
  public function getSpecialUrl(){
    return $this->poi_special_url;
  }
  public function getRoomName(){
    return $this->roomname;
  }
  
}

/**
 * 
 */
class ChatList {
	  
  private $rooms_array=array();  
	
	public function __construct() {
	}
  
  public function readFromDb($onlydefault=false, $childrenof=0, $alsoPrivate=true) {
    
	if($onlydefault)
		$sel=" WHERE `default`='1' ";
		
	if($childrenof>0)
		$sel=" WHERE room_father='{$childrenof}'";
		
	
    $query="SELECT r.* FROM room r".$sel;
    
    $result=mysql_query($query) or die(mysql_error());

    while($row=mysql_fetch_array($result)){
      //scorro tutte le stanze e popolo l'array
      $tmpRoom= new Room(null,null,null,$row);
      
      $this->rooms_array[$tmpRoom->getId()]=$tmpRoom;
      
    }
    
  }
  
 public function getRooms(){
   return $this->rooms_array;
 }
 
 public function getFirstRoom(){
 	
	foreach ($this->rooms_array as $k=>$v)
		return $v;
	
 }
 
 public function getPvtRooms($pvtRooms=0,$onlyifIcanAccess=false){
		
	$retArr=array();	
 	
	foreach($this->rooms_array as $k=>$v){
		
		//  deve essere pvt    &&  
		if($v->getPrivate()==$pvtRooms)
			if( ($onlyifIcanAccess && $v->getUser_rights()>=$pvtRooms) || !$onlyifIcanAccess )
				$retArr[$k]=$v;
	}
	
	return $retArr;
	
 }
  
  
}


?>
