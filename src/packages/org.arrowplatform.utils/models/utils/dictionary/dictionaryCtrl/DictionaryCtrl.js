function ctrlInitDictionaryCtrl(context,selector, data){
	var context = $(selector, context);
	$(".ctrl-dictionary-_add", context).click(function(event){
		
		
		
		var err = [];
		var name = $( ".dictionary-input") ;
		var value = $( ".dictionary-input-val" ) ;
		if( name.val() == "" ) err[0] = "Wprowadź poprawną nazwę" ;
	    if( value.length != 0 && value.val() == "" ) err[1] = "Wprowadź poprawną wartość" ;
		
	    if( err.length > 0 ) {
	    	err = err.join( "\n" );
	
	    	ArrowResponseReader.read( { response: {errormsg: err }, successCallback: function(response) {
				CtrlAjax.changeStateVars( link,	{'state' : 'select'});
				}
			});
	    } else {
	    	var valdict = "" ;
	    	if( value.length != 0 ) {
	    		valdict = "&data[value]=" + value.val() ;
	    	}
			
	    	var url = $(this).attr("href") + "&action=save&data[_state]=0&data[name]="+ name.val() + valdict;
	    	
	    	
			var link = this ;
			$.post( url, {}, function(response){
				ArrowResponseReader.read( { response: response, successCallback: function(response) {
					CtrlAjax.changeStateVars( link,	{'state' : 'select'});
					}
				});
				return false;
			}, "json" );
		
	    }
		return false ;
	});

}