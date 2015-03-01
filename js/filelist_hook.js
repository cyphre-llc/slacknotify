$(document).ready(function() {
  if (typeof FileActions !== 'undefined') {
    
	    FileActions.register('all', t('mailnotify','Notify'), OC.PERMISSION_READ, 
	    	function (file) {
	    		var filepath = $('#dir').val() + '/' + file;
	    		$('tr').filterAttr('data-file', String(file)).hover(function(){
	    			if($(this).data('state')==undefined || $(this).data('state')=='err' ){   
	    				$.post(OC.filePath('mailnotify', 'ajax','action.php'), {action:'get_status',action_gid:filepath}, function(actual_status) {
			    			
			    			if(actual_status == 'disable'){	
								path_img = 'mail2.png';
								state = 'disable';
								
							}else if(actual_status == 'enable'){
								path_img = 'mail.png';
								state = 'enable';
			
							}else if(actual_status == 'notShared'){
								var path_img = 'mail3.png';
			    				var state='notShared';
							}else{
								var path_img = 'err.png';
			    				var state='err';								
							}
							
							var row = $('tr').filterAttr('data-file', String(decodeURIComponent(file)));
							$(row).attr('data-state', state);
							$(row).find('a').filterAttr('data-action', t('mailnotify','Notify')).find('img').attr('src', OC.imagePath('mailnotify',path_img));
			    		});
	    			} 
		    	});   		
	    			
				return OC.imagePath('mailnotify', 'loading.png');	      
		    }, 
		    function (file) {	    	
			    var row = $('tr').filterAttr('data-file', String(file));
		  		var ele_fileNotify = $(row).find('a').filterAttr('data-action',t('mailnotify','Notify'));
		  		var currentstate = $(row).attr("data-state");
		  		var dir = $('#dir').val();
		  		if(dir != '/'){
		  			folder = dir+"/"+file;
		  		}else{
		  			folder = "/"+file;
		  		}
		  		ChangeState(folder, currentstate, ele_fileNotify);	  
	   	 });
	}
});
function ChangeState(folder, currentstate, that){
				
		  	if(currentstate == 'notShared'){
		  		return 0;
		  	}
	  		else if(currentstate == 'enable'){
				$.ajax({
				  type: "POST",
				  url: OC.filePath('mailnotify', 'ajax','action.php'),
				  data: { action:'do_disable',action_gid:folder},
				  success: function(retour){
				  	if(retour=='1'){
						$(that).find('img').attr('src', OC.imagePath('mailnotify', 'mail2.png'));
			  			$(that).parent().parent().parent().parent().attr('data-state', 'disable');
		  			}
				  }
				});

	  		}else if(currentstate == 'disable'){
				$.ajax({
				  type: "POST",
				  url: OC.filePath('mailnotify', 'ajax','action.php'),
				  data: { action:'do_enable',action_gid:folder},
				  success: function(retour){
				  	if(retour=='1'){
				  		$(that).children('img').attr('src', OC.imagePath('mailnotify', 'mail.png'));
		  				$(that).parent().parent().parent().parent().attr('data-state', 'enable');
				  	}
					
				  }
				});
				
	  		}
}
