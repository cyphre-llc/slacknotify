<?php

OCA\SlackNotify\Hooks::register();

OCP\App::registerAdmin('slacknotify', 'settings-admin');
OCP\App::registerPersonal('slacknotify', 'settings-personal');
