<?php

// Register hooks
OCA\SlackNotify\Hooks::register();

// Add settings pages
OCP\App::registerAdmin('slacknotify', 'settings-admin');
OCP\App::registerPersonal('slacknotify', 'settings-personal');

// Add cronjob
// Cron job for sending Emails
// OCP\Backgroundjob::registerJob('OCA\SlackNotify\Notification');
