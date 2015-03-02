<?php

OC::$CLASSPATH['OC_SlackNotify'] = 'slacknotify/lib/slack.php';
OC::$CLASSPATH['OC_SlackNotify_Hooks'] = 'slacknotify/lib/hooks.php';
OC::$CLASSPATH['OC_SlackAPI'] = 'slacknotify/lib/SlackAPI.php';

OC_HOOK::connect('OC_Filesystem', 'post_create', 'OC_SlackNotify_Hooks', 'notify_create');
OCP\Util::addScript('slacknotify', 'filelist_hook');
