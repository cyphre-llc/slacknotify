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
		// The connector after Activity App stores an entry in activity_mq
		\OCP\Util::connectHook('OC_Activity', 'post_email', 'OCA\SlackNotify\Hooks', 'sendNotification');
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
                        $fileLink = \OCP\Util::linkToAbsolute('files', 'index.php', array('dir' => $param));
                } else {
                        $parentDir = (substr_count($param, '/') == 1) ? '/' : dirname($param);
                        $fileName = basename($param);
                        $fileLink = \OCP\Util::linkToAbsolute('files', 'index.php', array(
                                'dir' => $parentDir,
                                'scrollto' => $fileName,
                        ));
                }

                $param = trim($param, '/');
                list($path, $name) = self::splitPathFromFilename($param);
                if ($path === '') {
			return '<' . $fileLink . '|' . \OCP\Util::sanitizeHTML($param) . '>';
                }

		return '<' . $fileLink . '|' . \OCP\Util::sanitizeHTML($name) . '>';
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
	public static function sendNotification($params) {
		if ($params['app'] !== 'files')
			continue;

		$user = \OCP\User::getUser();
		$object = self::prepareFileParam($params['subjectparams'][0]);

		if (substr($params['subject'], -5) === '_self') {
			// You (created|deleted|changed) FILE
			$msg = "You " . substr($params['subject'], 0, -5) .
				" " . $object;
		} else {
			
			// FILE was (created|deleted|changed) by OTHER
			// TODO Link OTHER to Slack user, if they have enabled it
			// https://api.slack.com/docs/formatting
			$msg = $other . " was " .
				substr($params['activity'], 0, -3) . " by " .
				$user;
		}

		self::slackSend($params['affecteduser'], $msg);
	}

	/** PRIVATE Interfaces **/

	/**
	 * @brief Call Slack API
	 * @msg Message to print to user
	 */
	private static function slackSend($user, $msg) {
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
			'channel' => $channel,
			'username' => 'Cyphre',
			'parse' => 'none',
			'attachments' => $attachments,
		));
	}
}
