<?php
namespace OCA\SlackNotify;

use \OCP\Config;
use \OCP\Util;

/**
 * @brief The class to handle the filesystem hooks
 */
class Hooks {
	/**
	 * @brief Registers the filesystem hooks for basic filesystem operations.
	 * All other events has to be triggered by the apps.
	 */
	public static function register() {
		// The connector after Activity App stores an entry in activity_mq
		Util::connectHook('OC_Activity', 'post_event', 'OCA\SlackNotify\Hooks', 'processOne');
	}

        /**
         * Split the path from the filename string
         *
         * @param string $filename
         * @return array Array with path and filename
         */
        private static function splitPathFromFilename($filename) {
                if (strrpos($filename, '/') !== false) {
                        return array(
                                trim(substr($filename, 0, strrpos($filename, '/')), '/'),
                                substr($filename, strrpos($filename, '/') + 1),
                        );
                }
                return array('', $filename);
        }

        /**
         * Prepares a file parameter for usage
         *
         * Removes the path from filenames and adds link syntax
         *
         * @param string $param
         * @return string
         */
	private static function prepareFileParam($param) {
		$rootView = new \OC\Files\View('');
                $is_dir = $rootView->is_dir('/' . \OCP\User::getUser() . '/files' . $param);

                if ($is_dir) {
                        $fileLink = Util::linkToAbsolute('files', 'index.php', array('dir' => $param));
                } else {
                        $parentDir = (substr_count($param, '/') == 1) ? '/' : dirname($param);
                        $fileName = basename($param);
                        $fileLink = Util::linkToAbsolute('files', 'index.php', array(
                                'dir' => $parentDir,
                                'scrollto' => $fileName,
                        ));
                }

                $param = trim($param, '/');
                list($path, $name) = self::splitPathFromFilename($param);
                if ($path === '') {
			return '<' . $fileLink . '|' . Util::sanitizeHTML($param) . '>';
                }

		return '<' . $fileLink . '|' . Util::sanitizeHTML($name) . '>';
        }

	public static function storeOne($user, $msg) {
		$config = \OC::$server->getConfig();

		/* Use locking to make sure we don't lose anything, especially since
		 * these entries are processed by a cron job. */
		$lock = new ExclusiveLock("/tmp/slacknotify");
		if (!$lock->lock()) {
			Util::writeLog('slacknotify', "Failed to obtain lock.", Util::ERROR);
			return;
		}

		$vals = unserialize($config->getUserValue($user, 'slacknotify', 'notifications', ""));
		$vals[] = $msg;
		$config->setUserValue($user, 'slacknotify', 'notifications', serialize($vals));

		$lock->unlock();
	}

	public static function linkUser($user, $other) {
		$config = \OC::$server->getConfig();

		// Check if on the same team.
		$team_id = $config->getUserValue($user, 'slacknotify', 'team_id');
		$other_team_id = $config->getUserValue($other, 'slacknotify', 'team_id');
		if ($team_id !== $other_team_id)
			return "*$user*";

		$channel = $config->getUserValue($user, 'slacknotify', 'channel');
		$name = $config->getUserValue($user, 'slacknotify', 'name');

		if (empty($name))
			$name = $user;

		if (empty($channel))
			return "*$user*";

		return "<@$channel|$name> ($user)";
	}

	/**
	 * @brief Accepts arguments from Activity App to use for Slack
	 * @params Values from Activity
	 *
	 * array(
	 *	'app'                   => $app,
	 *	'subject'               => $subject,
	 *	'subjectparams' => $subjectParams,
	 *	'affecteduser'  => $affectedUser,
	 *	'timestamp'             => $timestamp,
	 *	'type'                  => $type,
	 *	'latest_send'   => $latestSendTime,
	 * );
	 */
	public static function processOne($params) {
		if ($params['app'] !== 'files')
			continue;

		$user = $params['affecteduser'];

		// Make sure this user even wants this
		$config = \OC::$server->getConfig();
		$xoxp = $config->getUserValue($user, 'slacknotify', 'xoxp');
		if (empty($xoxp))
			return;

		$object = self::prepareFileParam($params['subjectparams'][0]);

		switch ($params['subject']) {
		case 'created_self':
		case 'deleted_self':
		case 'changed_self':
			$action = substr($params['subject'], 0, -5);
			$person = "*You*";
			break;

		case 'created_by':
		case 'deleted_by':
		case 'changed_by':
			$action = substr($params['subject'], 0, -3);
			$person = self::linkUser($params['subjectparams'][1], $user);
			break;

		case 'shared_user_self':
			$with = self::linkUser($params['subjectparams'][1], $user);
			$action = "shared with $with";
			$person = "*You*";
			break;

		case 'shared_group_self':
			$with = $params['subjectparams'][1];
			$action = "shared with *$with* (group)";
			$person = "*You*";
			break;

		case 'shared_with_by':
			$action = "shared with *you*";
			$person = self::linkUser($params['subjectparams'][1], $user);
			break;

		default:
			Util::writeLog('slacknotify', serialize($params), Util::ERROR);
			return;
		}

		$msg = array(
			'person' => $person,
			'action' => $action,
			'object' => $object
		);

		self::storeOne($user, $msg);
	}
}
