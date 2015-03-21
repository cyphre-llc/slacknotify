<?php

namespace OCA\SlackNotify;

class SendSlack extends \OC\BackgroundJob\TimedJob
{
	const CLI_BATCH_SIZE = 500;
	const WEB_BATCH_SIZE = 5;

	public function __construct() {
		$this->setInterval(5 * 60);
	}

	public function run($argument) {
		if (\OC::$CLI) {
			do {
				$sent = $this->runBatch(self::CLI_BATCH_SIZE);
			} while ($sent === self::CLI_BATCH_SIZE);
		} else {
			$this->runBatch(self::WEB_EMAIL_BATCH_SIZE);
		}
	}

	protected function runBatch($limit) {
		$num_users = 0;

		$users = \OCP\User::getUsers();

		foreach ($users as $user) {
			$xoxp = \OCP\Config::getUserValue($user, 'slacknotify', 'xoxp');
			if (empty($xoxp))
				continue;

			$lock = new ExclusiveLock("/tmp/slacknotify");
			if (!$lock->lock())
				continue;
			$notif = \OCP\Config::getUserValue($user, 'slacknotify', 'notifications');
			\OCP\Config::setUserValue($user, 'slacknotify', 'notifications', '');
			$lock->unlock();

			if (empty($notif))
				continue;

			$channel = \OCP\Config::getUserValue($user, 'slacknotify', 'channel');
			$msgs = array();
			$notif = unserialize($notif);

			foreach ($notif as $vals) {
				$person = $vals['person'];
				$object = $vals['object'];
				$action = $vals['action'];

				// First time seeing this person
				if (empty($msgs[$person])) {
					$msgs[$person] = array(
						$action => "$person $action: $object",
					);
					continue;
				}

				// First time seeing this action for this person
				if (empty($msgs[$person][$action])) {
					$msgs[$person][$action] = "$person $action: $object";
					continue;
				}

				// We've seen this person and action, so just append object
				$msgs[$person][$action] .= ", $object";
			}

			$this->slackSend($xoxp, $channel, $msgs);

			$num_users++;
			if ($num_users >= $limit)
				break;
		}

		return $num_users;
	}

	function slackSend($xoxp, $channel, $msgs) {
		$appConfig = \OC::$server->getAppConfig();

		$bot_name = $appConfig->getValue('slacknotify', 'slackBotName');
		$icon_url = $appConfig->getValue('slacknotify', 'slackIconUrl');

		if (empty($xoxp) or empty($channel))
			return;

		$attachments = array();

		foreach ($msgs as $person) {
			foreach ($person as $target) {
				// Replace <foo|bar> with bar
				$pattern = '/<[^|]+\|([^>]+)>/i';
				$fallback = preg_replace($pattern, '$1', $target);

				// Remove occurences of *
				$pattern = '/(\*)/i';
				$fallback = preg_replace($pattern, '', $fallback);

				$attachments[] = array(
					'text' => $target,
					'color' => '#232323',
					'fallback' => $fallback,
					"mrkdwn_in" => array("text"),
				);
			}
		}

		$attachments = json_encode($attachments);

		$Slack = new SlackAPI($xoxp);
		$Slack->call('chat.postMessage', array(
			'icon_url' => $icon_url,
			'channel' => $channel,
			'username' => $bot_name,
			'parse' => 'none',
			'attachments' => $attachments,
		));
	}
}
