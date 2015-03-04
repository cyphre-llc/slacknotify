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
		Util::connectHook('OC_Activity', 'post_email', 'OCA\SlackNotify\Hooks', 'processOne');
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

                $vals = unserialize($config->getUserValue($user, 'slacknotify', 'notifications', array()));
                $vals[] = $msg;
                $config->setUserValue($user, 'slacknotify', 'notifications', serialize($vals));

                $lock->unlock();
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

		self::storeOne($params['affecteduser'], $msg);
	}
}
