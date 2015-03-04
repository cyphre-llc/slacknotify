#!/usr/bin/env php
<?php
$base = dirname(__FILE__);

class OC {
	public static $SERVERROOT = null;
};

include_once("$base/slackapi.php");
include_once("$base/exclusivelock.php");
include_once("$base/../../../config/config.php");

$db = new mysqli($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpassword'],
		 $CONFIG['dbname']);

if ($db->connect_error) {
	die('Connect Error (' . $db->connect_errno . ') ' .
	    $db->connect_error);
}

function clearNotifications($user)
{
	global $db, $CONFIG;

	$user = $db->real_escape_string($user);

	$db->query("DELETE FROM " . $CONFIG['dbtableprefix'] .
		"preferences " . "WHERE appid='slacknotify' AND " .
		"configkey='notifications' AND userid='" . $user . "'");
}

function getUserValue($user, $key)
{
	global $db, $CONFIG;

	$key = $db->real_escape_string($key);
	$user = $db->real_escape_string($user);

	$res = $db->query("SELECT * FROM " . $CONFIG['dbtableprefix'] .
		"preferences " . "WHERE appid='slacknotify' AND configkey='" .
		$key . "' AND userid='" . $user . "'");

	if ($res === false or $res->num_rows != 1)
		return null;

	$row = $res->fetch_array();

	return $row['configvalue'];
}

function slackSend($xoxp, $user, $msg)
{
        $channel = getUserValue($user, 'channel');
        if (empty($xoxp) or empty($channel))
                return;

	$attachments = array();
	$attachments[] = array('text' => $msg, 'color' => '#232323');

	$attachments = json_encode($attachments);

	$Slack = new \OCA\SlackNotify\SlackAPI($xoxp);
	$Slack->call('chat.postMessage', array(
		'icon_url' => 'https://files.cyphre.com/themes/svy/core/img/favicon.png',
		'channel' => $channel,
		'username' => 'Cyphre',
		'parse' => 'none',
		'fallback' => 'Cyphre activity',
		'attachments' => $attachments,
	));
}


$res = $db->query("SELECT * FROM " . $CONFIG['dbtableprefix'] .
	"preferences " . "WHERE appid='slacknotify' AND configkey='xoxp'");

if ($res === false)
	exit();

while ($row = $res->fetch_array()) {
	$user = $row['userid'];
	$xoxp = $row['configvalue'];

	$lock = new \OCA\SlackNotify\ExclusiveLock("/tmp/slacknotify");
	if (!$lock->lock())
		continue;

	$notif = getUserValue($user, 'notifications');

	if (empty($notif)) {
		$lock->unlock();
		continue;
	}

	$notif = unserialize($notif);

	$msg = "";
	foreach ($notif as $line) {
		if (!empty($msg))
			$msg .= "\n";
		$msg .= $line;
	}

	slackSend($xoxp, $user, $msg);

	clearNotifications($user);

	$lock->unlock();
}
