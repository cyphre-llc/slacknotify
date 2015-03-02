<?php

OC_Util::checkAdminUser();

\OCP\Util::addscript('slacknotify', 'settings-admin');

$appConfig = \OC::$server->getAppConfig();

$slackClientID = $appConfig->getValue('slacknotify', 'slackClientID');
$slackClientSecret = $appConfig->getValue('slacknotify', 'slackClientSecret');

$tmpl = new OCP\Template('slacknotify', 'settings-admin');

$tmpl->assign('slackClientID', $slackClientID);
$tmpl->assign('slackClientSecret', $slackClientSecret);

return $tmpl->fetchPage();
