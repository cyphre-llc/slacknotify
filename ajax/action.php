<?php
OCP\JSON::callCheck();
$action = '';
$action_gid = '';
if(isset($_POST['action']) && isset($_POST['action_gid']) ){
	$action = $_POST['action'];
	$action_gid = $_POST['action_gid'];

	if($action == 'get_status' and $action_gid != ''){
		echo OC_MailNotify_Mailing::db_user_setting_get_status(OCP\User::getUser(), $action_gid);
		exit();
	}

	if($action == 'do_enable' and $action_gid != ''){
		echo OC_MailNotify_Mailing::db_remove_user_setting(OCP\User::getUser(), $action_gid);
		exit();
	}
	if($action == 'do_disable' and $action_gid != ''){
		echo OC_MailNotify_Mailing::db_user_setting_disable(OCP\User::getUser(), $action_gid);
		exit();		
	}
}
echo '0';
exit();
