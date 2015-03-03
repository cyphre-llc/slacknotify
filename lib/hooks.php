<?php
namespace OCA\SlackNotify;

use \OCP\Config;

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

		self::slackSend("Created files...", $params['path']);
	}

	/**
	 * @brief Store the update hook events
	 * @param array $params The hook params
	 */
	public static function fileUpdate($params) {
		self::slackSend("Updated files...", $params['path']);
	}

	/**
	 * @brief Store the delete hook events
	 * @param array $params The hook params
	 */
	public static function fileDelete($params) {
		self::slackSend("Deleted files...", $params['path']);
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
				self::slackSend("Files shared to a user...", $params['path']);
			} else if ($params['shareType'] == \OCP\Share::SHARE_TYPE_GROUP) {
				self::slackSend("Files shared...", $params['path']);
			}
		} else {
			self::slackSend("Files shared with you...", $params['path']);
		}
	}

	/**
	 * Delete remaining activities and emails when a user is deleted
	 * @param array $params The hook params
	 */
        public static function deleteUser($params) {
	}

	/** PRIVATE Interfaces **/

	/**
	 * @brief Call Slack API
	 * @msg Message to print to user
	 */
	private static function slackSend($pre, $msg) {
		$l = $this->getLanguage($lang);
		$dataHelper = new \OCA\Activity\DataHelper(\OC::$server->getActivityManager(), new ParameterHelper(new \OC\Files\View(''), $l), $l);

		$user = \OCP\User::getUser();
		$config = \OC::$server->getConfig();
		$xoxp = $config->getUserValue($user, 'slacknotify', 'xoxp', null);
		$channel = $config->getUserValue($user, 'slacknotify', 'channel', null);
		if (empty($xoxp) or empty($channel))
			return;

		$attachments = array();
		$attachments[] = array('text' => $msg, 'color' => '#232323');

		$attachments = json_encode($attachments);

		$Slack = new \OCA\SlackNotify\SlackAPI($xoxp);
		$Slack->call('chat.postMessage', array(
			'icon_url' => 'https://files.cyphre.com/themes/svy/core/img/favicon.png',
			'text' => $pre,
			'channel' => $channel,
			'username' => 'Cyphre',
			'attachments' => $attachments,
		));
	}
}
