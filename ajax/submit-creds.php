<?php
\OCP\JSON::checkAdminUser();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$l = OC_L10N::get('core');

$return = false;

$client_id = $_POST['client_id'];
$client_secret = $_POST['client_secret'];

$appConfig = \OC::$server->getAppConfig();

if (empty($client_id) or empty($client_secret)) {
	$appConfig->deleteKey('slacknotify', 'slackClientID');
	$appConfig->deleteKey('slacknotify', 'slackClientSecret');
	\OCP\JSON::success(array('data' => array('message' => $l->t('Slack Integration disabled.'))));
} else {
	$appConfig->setValue('slacknotify', 'slackClientID', $client_id);
	$appConfig->setValue('slacknotify', 'slackClientSecret', $client_secret);
	\OCP\JSON::success(array('data' => array('message' => $l->t('Slack Integration credentials updated.'))));
}
