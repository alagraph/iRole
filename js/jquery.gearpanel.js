(function( $ ){
  $.fn.gearPanel = function(callback) {
  
    
    
    
    
    var $this=this;
    var wid=$this.width();
    this.hide();
    
    //creo il gear
    var gear=$('<div style="margin:0 auto;width:'+wid+'px;"><img src="images/icons/gear-gold.png" id="edit"/></div>');
    $this.before(gear);
    
    //this.prepend(gear);
    $this.addClass('ui-corner-all');
	
	
	gear.click(function(){
	    	
	   	if (typeof callback == 'function') { // make sure the callback is a function
       		callback.call(this); // brings the scope to the callback
    	}else{
	   		$this.toggle('fast');
	   	}
	});
	
	
	
  };
  
})( jQuery );