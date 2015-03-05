<?php
\OCP\JSON::checkAdminUser();
\OCP\JSON::checkAppEnabled('slacknotify');
\OCP\JSON::callCheck();

$l = OC_L10N::get('core');

$client_id = $_POST['client_id'];
$client_secret = $_POST['client_secret'];
$bot_name = $_POST['bot_name'];
$icon_url = $_POST['icon_url'];

$appConfig = \OC::$server->getAppConfig();

if (empty($client_id) or empty($client_secret)) {
	$appConfig->deleteKey('slacknotify', 'slackClientID');
	$appConfig->deleteKey('slacknotify', 'slackClientSecret');
	$appConfig->deleteKey('slacknotify', 'slackBotName');
	$appConfig->deleteKey('slacknotify', 'slackIconUrl');
	\OCP\JSON::success(array('data' => array('message' => $l->t('Slack Integration disabled.'))));
} else {
	# TODO Maybe check if there is a way to validate these creds? -- BenC
	$appConfig->setValue('slacknotify', 'slackClientID', $client_id);
	$appConfig->setValue('slacknotify', 'slackClientSecret', $client_secret);
	$appConfig->setValue('slacknotify', 'slackBotName', $bot_name);
	$appConfig->setValue('slacknotify', 'slackIconUrl', $icon_url);
	\OCP\JSON::success(array('data' => array('message' => $l->t('Slack Integration updated.'))));
}
