<?php

\OCP\Util::addscript('slacknotify', 'settings-personal');

$appConfig = \OC::$server->getAppConfig();

$slackClientID = $appConfig->getValue('slacknotify', 'slackClientID');
$slackClientSecret = $appConfig->getValue('slacknotify', 'slackClientSecret');

$return = false;

if (!empty($slackClientID) and !empty($slackClientSecret)) {
	$user = \OCP\User::getUser();
	$xoxp = \OC_Preferences::getValue($user, 'slacknotify', 'xoxp');
	$channel = \OC_Preferences::getValue($user, 'slacknotify', 'channel');
	$url = '';

	$tmpl = new OCP\Template('slacknotify', 'settings-personal');

	if (!empty($xoxp) and !empty($channel)) {
		$tmpl->assign('slackEnabled', true);
	} else {
		$tmpl->assign('slackEnabled', false);

		$uuid = \OC_Preferences::getValue($user, 'slacknotify', 'uuid');
		if (empty($uuid)) {
			$uuid = \OCP\Util::generateRandomBytes(32);
			\OC_Preferences::setValue($user, 'slacknotify',
				'uuid', $uuid);
		}

		$redirect_uri = \OC_Helper::linkToAbsolute('slacknotify', 'ajax/auth-handle.php');

		$url = "https://slack.com/oauth/authorize?client_id=" .
			\OCP\Util::encodePath($slackClientID) .
			"&redirect_uri=" . \OCP\Util::encodePath(
			$redirect_uri) . '&state=' .
			\OCP\Util::encodePath($uuid);
	}

	$tmpl->assign('slackAuthUrl', $url);

	$return = $tmpl->fetchPage();
}

return $return;
