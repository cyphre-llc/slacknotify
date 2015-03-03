$(document).ready(function(){ 
	$('button:button[name="disableSlack"]').click(function() {
		OC.msg.startSaving('#slacknotify .msg');
		$.post(
		OC.filePath( 'slacknotify', 'ajax', 'disable.php' )
			, { }
			,  function( data ) {
				if (data.status == "error") {
					OC.msg.finishedSaving('#slacknotify .msg', data);
				} else {
					OC.msg.finishedSaving('#slacknotify .msg', data);
					this.reload();
				}
			}
		);
	});
	$('button:button[name="enableSlack"]').click(function() {
		var url = $('#auth_url').val;
		OC.msg.startAction('#slacknotify .msg', t('core', 'Redirecting...'));
		OC.redirect($('#auth_url').val());
	});
});
