<?php

/**
* ownCloud - MailNotify Plugin
*
* @author Jascha Burmeister
* @contributors Bastien Ho, Felix Baltruschat
* @copyright 2012 Jascha Burmeister burmeister@wortbildton.de
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* 
*/


OC::$CLASSPATH['OC_SlackNotify_Mailing'] = 'slacknotify/lib/slack.php';
OC::$CLASSPATH['OC_SlackNotify_Hooks'] = 'slacknotify/lib/hooks.php';


OC_HOOK::connect('OC_Filesystem', 'post_create', 'OC_SlackNotify_Hooks', 'notify');
OCP\Util::addScript('mailnotify', 'filelist_hook');
