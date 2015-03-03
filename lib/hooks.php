<?php
namespace OCA\SlackNotify;

/**
 * @brief The class to handle the filesystem hooks
 */
class Hooks {
	/**
	 * @brief Registers the filesystem hooks for basic filesystem operations.
	 * All other events has to be triggered by the apps.
	 */
	public static function register() {
		\OCP\Util::connectHook('OC_Filesystem', 'post_create', 'OCA\SlackNotify\Hooks', 'fileCreate');
		\OCP\Util::connectHook('OC_Filesystem', 'post_update', 'OCA\SlackNotify\Hooks', 'fileUpdate');
		\OCP\Util::connectHook('OC_Filesystem', 'delete', 'OCA\SlackNotify\Hooks', 'fileDelete');
		\OCP\Util::connectHook('OCP\Share', 'post_shared', 'OCA\SlackNotify\Hooks', 'share');

		\OCP\Util::connectHook('OC_User', 'post_deleteUser', 'OCA\SlackNotify\Hooks', 'deleteUser');
	}

	/**
	 * @brief Store the create hook events
	 * @param array $params The hook params
	 */
        public static function fileCreate($params) {
		if (\OCP\User::getUser() === false)
			return;

		self::slackSend("Created \"" . $params['path'] . "\"");
	}

	/**
	 * @brief Store the update hook events
	 * @param array $params The hook params
	 */
	public static function fileUpdate($params) {
		self::slackSend("Updated \"" . $params['path'] . "\"");
	}

	/**
	 * @brief Store the delete hook events
	 * @param array $params The hook params
	 */
	public static function fileDelete($params) {
		self::slackSend("Deleted \"" . $params['path'] . "\"");
	}

	/**
	 * @brief Manage sharing events
	 * @param array $params The hook params
	 */
	public static function share($params) {
		if ($params['itemType'] !== 'file' and $params['itemType'] !== 'folder')
			return;

		if ($params['shareWith']) {
			if ($params['shareType'] == \OCP\Share::SHARE_TYPE_USER) {
				self::slackSend("Shared \"" . $params['path'] . "\" a user");
			} else if ($params['shareType'] == \OCP\Share::SHARE_TYPE_GROUP) {
				self::slackSend("Shared \"" . $params['path'] . "\" a group");
			}
		} else {
			self::slackSend("Shared \"" . $params['path'] . "\" with you");
		}
	}

	/**
	 * Delete remaining activities and emails when a user is deleted
	 * @param array $params The hook params
	 */
        public static function deleteUser($params) {
	}

	/**
	 * @brief Call Slack API
	 * @msg Message to print to user
	 */
	private static function slackSend($msg) {
		$user = \OCP\User::getUser();
		$config = \OC::$server->getConfig();
		$xoxp = $config->getUserValue($user, 'slacknotify', 'xoxp', null);
		$channel = $config->getUserValue($user, 'slacknotify', 'channel', null);
		if (empty($xoxp) or empty($channel))
			return;

		$Slack = new \OCA\SlackNotify\SlackAPI($xoxp);
		$Slack->call('chat.postMessage', array(
			'icon_url' => 'https://files.cyphre.com/themes/svy/core/img/favicon.png',
			'channel' => $channel,
			'username' => 'Cyphre',
			'text' => $msg,
		));
	}
}
