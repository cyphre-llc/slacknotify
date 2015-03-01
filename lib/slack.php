<?php
class OC_SlackNotify_Slacking {
	private static $no_notify_folders = array('fakeGroup1','fakeGroup2'); 	// do not notify for following folders	
	private static $minimum_queue_delay = 00;
	private static $now = NULL;
	
	
	function __construct() {
    	self::$now = time();
		$l = new OC_L10N('mailnotify');
		$nm_upload = self::db_get_nm_upload();
		$shares = self::db_get_share();
   	}
	
	
	
	//==================== PUBLIC ==============================//
/** @deprecated */
	 public static function main($path){
		\OCP\Util::writeLog('mailnotify', 'The main() function found at line '.__LINE__.' is depricated. use queue_fileChange_notification() insted', \OCP\Util::WARN);
		self::queue_fileChange_notification($path);
	}
/** @deprecated */	 
	public static function db_notify_group_members(){
		\OCP\Util::writeLog('mailnotify', 'The db_notify_group_members() function found at line '.__LINE__.' is depricated. use do_notification_queue() insted', \OCP\Util::WARN);
		self::do_notification_queue();
	}	
		

		
	/**
	 *  Add file change to the notification queue in the database 
	 * trigger on file/folder change/upload
	 * @param $path Path of the modified file. 
 	 * @return void 
	 */		
	public static function queue_fileChange_notification($path){
		$fileInfo = OC_Files::getFileInfo($path['path']);	
 		$timestamp = time(); //TODO timestamp nust be a constant.
		self::db_insert_upload(OCP\User::getUser(),  $timestamp,$fileInfo['fileid']);	
	}
	


	/**
	 *  Direct notification of an internal message (no queue delay)
	 * @param $fromUid uid of the message author. 
	 * @param $toUid destination uid. 
	 * @param $msg message content. 
	 * @return void 
	 */
	public static function email_IntMsg($fromUid, $toUid, $msg){		
		$l = new OC_L10N('mailnotify');
		$intMsgUrl = OCP\Util::linkToAbsolute('index.php/apps/internal_messages');

		$text = "You have a new message from <b>$fromUid</b>.
				<p><br>$msg<br></p>
				Please log in to <a href=\"$intMsgUrl\">%s</a> to reply.<br>";

		OC_SlackNotify_Slacking::sendEmail($text,$l->t('New message from '.$fromUid),$toUid);
	}

		
	
	/**
	 *  check for pending notification and send corresponding emails (trigger by cronjob) 
	 * @return void 
	 */
	static public function do_notification_queue(){
		$l = new OC_L10N('mailnotify');
		$nm_upload = self::db_get_nm_upload();
		$shares = self::db_get_share();
		$mailTo = array();
		$filesList = array(); 
		$fileInfo = array();		
				
		//list all unique nm_upload path. add most recent timestamp and list editors.
		foreach ($nm_upload as $upload) {		
			$filesList[$upload['path']] = array();
			if ( !isset($filesList[$upload['path']]['timestamp']) || $filesList[$upload['path']]['timestamp'] < $upload['timestamp'] ) {
				$filesList[$upload['path']]['timestamp'] = $upload['timestamp']; 
			}
		}
		
		// find who want wich notifications
		foreach ($filesList as $filePath => $Mod_timestamp) {
			foreach ($shares as $sharesKey => $row) {
				if (self::is_under($row["file_source"],$filePath)){
					if (!self::is_uid_exclude('/Shared'.$row['file_target'],$row['share_with'])) {
						$mailTo[$row['share_with']][] = $sharesKey;							
					} elseif(!self::is_uid_exclude($row['file_target'],$row['uid_owner'])) {  
						$mailTo[$row['uid_owner']][] = $sharesKey;
					}
   					if(self::db_isgroup($row['share_with'])){ 
                        foreach (self::db_get_usersOfGroup($row['share_with']) as $key => $user) {
                           if (!self::is_uid_exclude('/Shared'.$row['file_target'],$row['share_with'])) { //if shared with user 
                                $mailTo[$user][] = $sharesKey;
                           }    
                        }    
                    }
				}
			}
		} 
															//var_dump($mailTo);
		//assamble emails
		if (!empty($mailTo)) {
			foreach ($mailTo as $uid => $files) {
			$msg = '<ul> Following files have been modified. <br><br>';
				foreach ($files as $rowId) {
					$url_path = self::db_get_filecash_path($shares[$rowId]['item_source']);
					$url_name = substr($shares[$rowId]['file_target'], 1);
					$msg .='<li><a href="'.OCP\Util::linkTo('index.php/apps/files?dir=//Shared','').'" target="_blank">'.$url_name.'</a></li>'; //FIXME static redirection :(
					OC_SlackNotify_Slacking::db_remove_all_nmuploads_for($shares[$rowId]['file_source']);
				}	

				OC_SlackNotify_Slacking::sendEmail($msg,$l->t('New upload'),$uid);	

			}
		}
	}

	
	
//================= PRIVATE ===============================//


	private static function sendEmail($msg,$action,$toUid){
 	$l = new OC_L10N('mailnotify');
					
		$txtmsg = '<html><p>Hi, '.$toUid.', <br><br>';
		$txtmsg .= '<p>'.$msg;
		$txtmsg .= $l->t('<p>This e-mail is automatic, please, do not reply to it.</p></html>');
 		if (self::db_get_mail_by_user($toUid) !== NULL) {
	 		$result = OC_Slack::send(self::db_get_mail_by_user($toUid), $toUid, '['.getenv('SERVER_NAME')."] - ".$action, $txtmsg, 'Slack_Notification@'.getenv('SERVER_NAME'), 'Owncloud', 1 );		
		}else{
		 	echo "Email address error<br>";
		 }
	}
	
	
	
	// check if $path shoud be excluded form $uid notifications.
	// @return true if shoud be excluded false if not
	private static function is_uid_exclude($fileName,$share_with_uid){
		
		// hardcoded static exclusion array 
	 	foreach (self::$no_notify_folders as $folder) {
			if ( $fileName == '/'.$folder ) {
				return true;
			}			 
		 }
		
		if ( self::db_user_setting_get_status($share_with_uid, $fileName) !== 'enable') {
			return true;
		}
		
		//exclude creator of change
		$found = 0 ; 
		foreach (self::db_get_nm_upload() as $row) {
			if ($share_with_uid == $row['uid']) {
				$found++;
			}		
		}
		if ($found == 1) {
			return true;
		}
		
		//ignore if the most recent notification is inside the time buffer 
		foreach (self::db_get_nm_upload() as $row) {
			if ($row['path'] == $fileName && $row['timestamp'] > time()-self::$minimum_queue_delay ) {
				return true;	
			}
		}

	return false;	
	}	



	/*
	* Evaluate path and return frist sharing parent
	*/
	private static function get_first_sharing_in($path){		
		$splits = explode("/", $path);
		$shares = self::db_get_share();
	
		foreach ($splits as $file_name) {
			foreach ($shares as $shares_row) {
				if ($shares_row['file_target'] == '/'.$file_name ) {
					return $file_name;
				}				
			}
		}	
		return -1;
	}





//=================== DATABASE ACCES ===================================//


	private static function db_get_filecash_path($itemId){
		$query=OC_DB::prepare('SELECT * FROM `*PREFIX*filecache` WHERE `fileid` = ? ');
		$result = $query->execute(array($itemId));
		
		if(OC_DB::isError($result)) {
			\OCP\Util::writeLog('mailnotify', 'database error at '.__LINE__ .' Result='.$result, \OCP\Util::ERROR);
			return -1;
		}
		while($row=$result->fetchRow()) {
			return $row['path'];
		}
	
	
		
	}

//TODO change function name and add error catch, add doc
//TODO can be done with OC_Files::getFileInfo($path['path'] 
	private static function is_under($needleId,$haystackId){
		if ($needleId == $haystackId) {return TRUE;}
		
		// get parent id 
		$query=OC_DB::prepare('SELECT * FROM `*PREFIX*filecache` WHERE `fileid` = ? ');
		$result = $query->execute(array($haystackId));
		while($row=$result->fetchRow()) {
			
			if ($row['parent'] != $needleId && $row['parent'] != -1 ) {				
				return self:: is_under($needleId,$row['parent']);
				
			} else if ($row['parent'] == -1 ) {
				return false;
				
			}else {
				return true;
			}		
		}
	}




	//get the database table share.
	private static function db_get_share(){
		$query=OC_DB::prepare("SELECT * FROM `*PREFIX*share` ");
		$result=$query->execute();
		
		while($row=$result->fetchRow()) {
			$rtn[] = $row;
		}
		return $rtn;
	}


 
	/**
	 * bool folder shared with me
	 * format: /examplefolder
	 */ 
	private static function db_folder_is_shared_with_me($path,$user = ''){
		if ($user == '' ) {
			$user = OCP\User::getUser();			
		}
		
		$query=OC_DB::prepare('SELECT * FROM `*PREFIX*share` WHERE `item_source` = ? AND (`share_with` = ? OR `uid_owner` = ?)');
		$result=$query->execute(array($path,$user,$user));

		if(OC_DB::isError($result)) {
			\OCP\Util::writeLog('mailnotify', 'database error at '.__LINE__ .' Result='.$result, \OCP\Util::ERROR);
			return -1;
		}
		
		if($result->numRows() > 0){			
			return true;
		}else{
			return false;
		}
	}



	/**
	 * Inserts an upload entry in our mail notify database
	 */
	private static function db_insert_upload($uid,  $timestamp, $fileid){
		
		$query=OC_DB::prepare('INSERT INTO `*PREFIX*mn_uploads`(`uid`, `timestamp`, `path`) VALUES(?,?,?)');
		$result=$query->execute(array($uid, $timestamp, $fileid));
	
		if (OC_DB::isError($result) ) {
			\OCP\Util::writeLog('mailnotify', 'Failed to add new notification in the notify database Result='.$result, \OCP\Util::ERROR);
		}	
		return $result;
	}



	/**
	 * Put nm_upload table into an array
	 */
	private static function db_get_nm_upload(){
		$query=OC_DB::prepare('SELECT * FROM `*PREFIX*mn_uploads`');
		$result=$query->execute();

		if(OC_DB::isError($result)) {
			\OCP\Util::writeLog('mailnotify', 'Failed to get nm_upload from database at line '.__FILE__.' Result='.$relult, \OCP\Util::ERROR);
			return -1;
		}else{
			$strings = array();
			while($row=$result->fetchRow()) {
				$strings[]=$row;
				}
			return $strings;
		}
	}
	


	/**
	* Remove uploads by path
 	*/
	private static function db_remove_all_nmuploads_for($fileId){

		$query=OC_DB::prepare('DELETE FROM `*PREFIX*mn_uploads` WHERE `path` = ?');
		$result=$query->execute(array($fileId));
		if(OC_DB::isError($result)) {
			echo "db_remove_all_nmuploads_for direct remove ERROR";
		}

	// clean forgotten entry in database.
	$query=OC_DB::prepare('DELETE FROM `*PREFIX*mn_uploads` WHERE `timestamp` < ?');
		$result=$query->execute(array((self::$minimum_queue_delay*3)+60));
		if(OC_DB::isError($result)) {
			echo "db_remove_all_nmuploads_for database cleaning error ERROR";
		}	
 

	}
		


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
	
	
	
	/**
	 * Get email address of userID
	 */
	private static function db_get_mail_by_user($uid)
	{
		$key = 'email';
		$query=OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `configkey` = ? AND `userid`=?');
		$result=$query->execute(array($key, $uid));
		if(OC_DB::isError($result)) {
			return;
		}

		$row=$result->fetchRow();
		$mail = $row['configvalue'];

		return $mail;

	}
	
	
	
	private static function db_isgroup($gid){
		$query=OC_DB::prepare('SELECT * FROM `*PREFIX*group_user` WHERE `gid` = ?');
		$result=$query->execute(array($gid));
		 
		 if(OC_DB::isError($result)) {
			return -1;
		 }
		 
		if($result->numRows() > 0){
				return true; 
			}else{
				return false;
			}
	}

		


	private static function db_get_usersOfGroup($gid){
		$query=OC_DB::prepare('SELECT * FROM `*PREFIX*group_user` WHERE `gid` = ?');
		$result=$query->execute(array($gid));
		 
		 if(OC_DB::isError($result)) {
			return -1;
		 }
		$users =   array();
	
		while($row=$result->fetchRow()) {				
		
		$users[]=$row['uid'];	
				
		}
		return $users;
			}


//===================== INIT FUNCTIONS ==========================//
//TODO Put this on a seperate file and class 

	/**
	 * Write data to an INI file
	 * 
	 * The data array has to be like this:
	 * 
	 *  Array
	 *  (
	 *      [Section1] => Array
	 *          (
	 *              [key1] => val1
	 *              [key2] => val2
	 *          )
	 *      [Section2] => Array
	 *          (
	 *              [key3] => val3
	 *              [key4] => val4
	 *          )    
	 *  )
	 *
	 * @param string $filePath
	 * @param array $data
	 */
	 
	 
	 
	public static function ini_write($file, array $data)
	{
	    $output = '';

	    $dir = dirname(__FILE__);
		$filePath = $dir."/".$file;

	 
	    foreach ($data as $section => $values)
	    {

	        if (!is_array($values)) {
	            continue;
	        }
	 	
	        //add section
	        $output .= "[$section]\n";
	 
	        //add key/value pairs
	        foreach ($values as $key => $val) {
	            $output .= $key."=".$val."\n";

	        }

	        $output .= "\n";
	    }
	 
	    unlink($filePath);
	    if(!file_put_contents($filePath, trim($output))){
	    	//print("failure");
	    }
	}
	 
	 
	/**
	 * Read and parse data from an INI file
	 * 
	 * The data is returned as follows:
	 * 
	 *  Array
	 *  (
	 *      [Section1] => Array
	 *          (
	 *              [key1] => val1
	 *              [key2] => val2
	 *          )
	 *      [Section2] => Array
	 *          (
	 *              [key3] => val3
	 *              [key4] => val4
	 *          )    
	 *  )
	 * 
	 * @param string $filePath
	 * @return array|false
	 */
	 
	 
	 
	public static function ini_read($file)
	{
		$dir = dirname(__FILE__);
		$filePath = $dir."/".$file;

	    if (!file_exists($filePath)) {
	        return false;
	        
	    }
	 	
	    //read INI file linewise
	    $lines = array_map('trim', file($filePath));
	    $data  = array();
	    	 
	    $currentSection = null;
	    foreach ($lines as $line)
	    {
	    	

	        if (substr($line, 0, 1) == '[') {
	            $currentSection = substr($line, 1, -1);
	            $data[$currentSection] = array();

	        }
	        else
	        {
	        		        	
	            //skip line feeds in INI file
	            if (empty($line)) {
	                continue;
	            }
	 
	            //if no $currentsection is still null,
	            //there was missing a "[<sectionName>]"
	            //before the first key/value pair
	            if (null === $currentSection) {
	                return false;
	            }
	            
	 
	            //get key and value
	            list($key, $val) = explode('=', $line);
	            $data[$currentSection][$key] = $val;
	        }
	    }
	 
	    return $data;
	}



}
//http://www.youtube.com/watch?v=TJL4Y3aGPuA
