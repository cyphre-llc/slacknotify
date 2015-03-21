<?php

// Register hooks
OCA\SlackNotify\Hooks::register();

// Add settings pages
OCP\App::registerAdmin('slacknotify', 'settings-admin');
OCP\App::registerPersonal('slacknotify', 'settings-personal');

OCP\Backgroundjob::registerJob('OCA\SlackNotify\SendSlack');
