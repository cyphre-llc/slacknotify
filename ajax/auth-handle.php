<?php

\OCP\JSON::checkLoggedIn();
\OCP\JSON::checkAppEnabled('slacknotify');
# \OCP\JSON::callCheck();

$l = OC_L10N::get('core');
$user = \OCP\User::getUser();

$redirect_uri = \OC_Helper::linkToAbsolute('slacknotify',
	'ajax/auth-handle.php');

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);

# Make sure this is valid
if (empty($_GET['code']) or empty($_GET['state'])) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Invalid request'))));
	exit();
}

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);

$state = $_GET['state'];
$code = $_GET['code'];

# Verify the state matches the user
$uuid = \OC_Preferences::getValue($user, 'slacknotify', 'uuid');

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . '):' . $uuid . " @@ " . $state, \OCP\Util::ERROR);

if (empty($uuid) or $uuid != $state) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Invalid request'))));
	exit();
}

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);

# Check that we're configured correctly
$appConfig = \OC::$server->getAppConfig();

$slackClientID = $appConfig->getValue('slacknotify', 'slackClientID');
$slackClientSecret = $appConfig->getValue('slacknotify', 'slackClientSecret');

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);

if (empty($slackClientID) or empty($slackClientSecret)) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Invalid request'))));
	exit();
}

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);

# Pull the auth token for this code
$Slack = new \OCA\SlackNotify\SlackAPI();
$ret = $Slack->call('oauth.access', array(
	'client_id' => $slackClientID,
	'client_secret' => $slackClientSecret,
	'redirect_uri' => $redirect_uri,
	'code' => $code,
));

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);

if (empty($ret) or empty($ret['access_token'])) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Error Accessing Server'))));
	exit();
}

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);
$token = $ret['access_token'];

# Get the User ID and test this token
$Slack = new \OCA\SlackNotify\SlackAPI($token);
$ret = $Slack->call('auth.test', array(
	'token' => $token,
));

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);

if (empty($ret) or empty($ret['user_id'])) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Error Accessing Server'))));
	exit();
}

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);
# All looks good. Store it up.
\OC_Preferences::setValue($user, 'slacknotify', 'xoxp', $token);
\OC_Preferences::setValue($user, 'slacknotify', 'channel', $ret['user_id']);

\OCP\Util::writeLog('slacknotify', __FILE__ . '(' . __LINE__ . ')', \OCP\Util::ERROR);

# Send us back to the settings page
\OCP\Response::redirect(\OC_Helper::linkToRoute( "settings_personal" ).'#slacknotify');
