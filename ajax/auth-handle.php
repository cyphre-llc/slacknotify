<?php

\OCP\JSON::checkLoggedIn();
\OCP\JSON::checkAppEnabled('slacknotify');
# \OCP\JSON::callCheck();

$l = OC_L10N::get('core');
$user = \OCP\User::getUser();

$redirect_uri = \OC_Helper::linkToAbsolute('slacknotify',
	'ajax/auth-handle.php');

if (!empty($_GET['error']) and $_GET['error'] == 'access_denied') {
	# Guess they had second thoughts.
	\OCP\Response::redirect(\OC_Helper::linkToRoute( "settings_personal" ).'#slacknotify');
}

# Make sure this is valid
if (empty($_GET['code']) or empty($_GET['state'])) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Invalid request'))));
	exit();
}

$state = $_GET['state'];
$code = $_GET['code'];

# Verify the state matches the user
$uuid = \OC_Preferences::getValue($user, 'slacknotify', 'uuid');

if (empty($uuid) or $uuid != $state) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Invalid request'))));
	exit();
}

# Check that we're configured correctly
$appConfig = \OC::$server->getAppConfig();

$slackClientID = $appConfig->getValue('slacknotify', 'slackClientID');
$slackClientSecret = $appConfig->getValue('slacknotify', 'slackClientSecret');

if (empty($slackClientID) or empty($slackClientSecret)) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Invalid request'))));
	exit();
}

# Pull the auth token for this code
$Slack = new \OCA\SlackNotify\SlackAPI();
$ret = $Slack->call('oauth.access', array(
	'client_id' => $slackClientID,
	'client_secret' => $slackClientSecret,
	'redirect_uri' => $redirect_uri,
	'code' => $code,
));

if (empty($ret) or empty($ret['access_token'])) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Error Accessing Server'))));
	exit();
}

$token = $ret['access_token'];

# Get the User ID and test this token
$Slack = new \OCA\SlackNotify\SlackAPI($token);
$ret = $Slack->call('auth.test', array(
	'token' => $token,
));

if (empty($ret) or empty($ret['user_id'])) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Error Accessing Server'))));
	exit();
}

# All looks good. Store it up.
\OC_Preferences::setValue($user, 'slacknotify', 'xoxp', $token);
\OC_Preferences::setValue($user, 'slacknotify', 'channel', $ret['user_id']);

# Send us back to the settings page
\OCP\Response::redirect(\OC_Helper::linkToRoute( "settings_personal" ).'#slacknotify');
