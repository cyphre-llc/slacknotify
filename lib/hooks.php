<?php
				
class OC_SlackNotify_Hooks{
	
	
	/**
	 *  Notify a new file/forder creation or change.
	 */
	static public function notify($path) {
		OC_MailNotify_Mailing::queue_fileChange_notification($path);
		
		return true;
	}
}
