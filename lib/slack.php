<?php
class OC_SlackNotify {
	function __construct() {
   	}


	//==================== PUBLIC ==============================//
/** @deprecated */
	 public static function main($path){
		\OCP\Util::writeLog('slacknotify', 'The main() function found at line '.__LINE__.' is depricated. use queue_fileChange_notification() insted', \OCP\Util::WARN);
		self::queue_fileChange_notification($path);
	}
/** @deprecated */	 

	/**
	 *  Add file change to the notification queue in the database 
	 * trigger on file/folder change/upload
	 * @param $path Path of the modified file. 
 	 * @return void 
	 */		
	public static function slack_notification_create($path) {
		// $fileInfo = \OC\Files\Filesystem::getFileInfo($path['path']);
		$Slack = new OC_SlackAPI('xoxp-3645164208-3702564767-3875117521-02f5b0');
		$Slack->call('chat.postMessage', array(
			'icon_url' => 'https://files.cyphre.com/themes/svy/core/img/favicon.png',
			'channel' => 'D03LNGLP5',
			'username' => 'Cyphre',
			'text' => "You created \"" . $path['path'] . "\"",
		));
	}


//================= PRIVATE ===============================//



//=================== DATABASE ACCES ===================================//
	/**
	 * Remove notification disable entry form database
	 * @param $uid uid if the requesting user.
	 * @param $path of the file request.
	 * @return  1 if succes, 0 if fail.
	 */
	public static function db_remove_user_setting($uid, $path){
		$query=OC_DB::prepare('DELETE FROM `*PREFIX*mn_usersettings` WHERE `uid` = ? AND `path` = ?');
		if($query->execute(array($uid, $path))){
			return 1;
		}
		return 0;
	}


	/**
	 * Add a notification disable entry in the database 
	 * @param $uid uid if the requesting user. 
	 * @param $path of the requested file. 
	 * @return  1 if succes, 0 if fail. 
	 */
	public static function db_user_setting_disable($uid, $path)
	{
		$query=OC_DB::prepare('INSERT INTO `*PREFIX*mn_usersettings`(`uid`, `path`, `value`) VALUES(?,?,?)');
		if($query->execute(array($uid, $path, 'disable'))){
			return 1;
		}
		return 0;
	}



	/**
	 * Get user's notification preferances status for a file/folder.  
	 * @param $uid uid if the requesting user. 
	 * @param $path of the requested file. 
	 * @return [enable|disable|notShared] or 0 if fail
	 */
	public static function db_user_setting_get_status($uid, $path){
		$path = urldecode($path);
	
		if(self::get_first_sharing_in($path) !== -1){
			$query=OC_DB::prepare('SELECT * FROM `*PREFIX*mn_usersettings` WHERE `uid` = ? AND `path` = ?');
			$result=$query->execute(array($uid, $path));

			if($result->numRows() == 0){
				return 'enable'; 
			}else{
				return 'disable';
			}
		}else{
			return 'notShared';
		}
		return 0;
	}
}
