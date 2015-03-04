<?php

\OCP\JSON::checkLoggedIn();
\OCP\JSON::checkAppEnabled('slacknotify');
\OCP\JSON::callCheck();

$l = OC_L10N::get('core');

$user = \OCP\User::getUser();
$config = \OC::$server->getConfig();

$config->setUserValue($user, 'slacknotify', 'xoxp', '');
$config->setUserValue($user, 'slacknotify', 'channel', '');
$config->setUserValue($user, 'slacknotify', 'name', '');
$config->setUserValue($user, 'slacknotify', 'notifications', '');

\OCP\JSON::success(array('data' => array('message' => $l->t('Slack Integration Disabled.'))));
