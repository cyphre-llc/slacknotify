<form id="slacknotify" class="section">
	<h2><img width="18" src="<?php p(OCP\Util::imagePath('slacknotify', 'slack-sticker.png')); ?>"/>
        <?php p($l->t('Slack Integration')); ?></h2>
	<?php if ($_['slackEnabled']) { ?>
		<p>In order to limit which notifications you receive, adjust the <a href="#activity_notifications">Activity</a> settings for "Stream".</p>
		<p><?php p($l->t('Slack integration is enabled.')); ?></p>
		<button type="button" name="disableSlack"><?php p($l->t("Disable Slack")); ?></button>
	<?php } else { ?>
		<p><?php p($l->t('You can enable Slack integration by clicking below.')); ?></p>
		<button type="button" name="enableSlack"><?php p($l->t("Enable Slack")); ?></button>
		<input type="hidden" id="auth_url" name="auth_url" value="<?php p($_['slackAuthUrl']); ?>">
	<?php } ?>
	<span class="msg"></span>
</form>
