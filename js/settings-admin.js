$(document).ready(function(){ 
	$('button:button[name="submitSlackKeys"]').click(function() {
		var client_id = $('#slackClientID').val();
		var client_secret = $('#slackClientSecret').val();
		var bot_name = $('#slackBotName').val();
		var icon_url = $('#slackIconUrl').val();
		OC.msg.startSaving('#slacknotify .msg');
		$.post(
		OC.filePath( 'slacknotify', 'ajax', 'submit-creds.php' )
			, { client_id: client_id, client_secret: client_secret,
			    bot_name: bot_name, icon_url: icon_url }
			,  function( data ) {
				if (data.status == "error") {
					OC.msg.finishedSaving('#slacknotify .msg', data);
				} else {
					OC.msg.finishedSaving('#slacknotify .msg', data);
				}
			}
		);
	});
});
