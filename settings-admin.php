<?php

OC_Util::checkAdminUser();

\OCP\Util::addscript('slacknotify', 'settings-admin');

$appConfig = \OC::$server->getAppConfig();

$slackClientID = $appConfig->getValue('slacknotify', 'slackClientID');
$slackClientSecret = $appConfig->getValue('slacknotify', 'slackClientSecret');
$slackBotName = $appConfig->getValue('slacknotify', 'slackBotName');
$slackIconUrl = $appConfig->getValue('slacknotify', 'slackIconUrl');

$tmpl = new OCP\Template('slacknotify', 'settings-admin');

$tmpl->assign('slackClientID', $slackClientID);
$tmpl->assign('slackClientSecret', $slackClientSecret);
$tmpl->assign('slackBotName', $slackBotName);
$tmpl->assign('slackIconUrl', $slackIconUrl);

return $tmpl->fetchPage();
