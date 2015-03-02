<?php
				
class OC_SlackNotify_Hooks{
	/**
	 *  Notify a new file/forder creation or change.
	 */
	static public function notify_create($path) {
		OC_SlackNotify::slack_notification_create($path);
		return true;
	}
}
