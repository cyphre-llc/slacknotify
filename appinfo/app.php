<?php

#OC::$CLASSPATH['OC_SlackNotify'] = 'slacknotify/lib/slack.php';
#OC::$CLASSPATH['OC_SlackNotify_Hooks'] = 'slacknotify/lib/hooks.php';
#OC::$CLASSPATH['OC_SlackAPI'] = 'slacknotify/lib/SlackAPI.php';

OCA\SlackNotify\Hooks::register();

// OCP\Util::addScript('slacknotify', 'filelist_hook');
