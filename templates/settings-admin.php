<form id="slacknotify" class="section">
        <h2><?php p($l->t('Slack Integration')); ?></h2>
	<?php p($l->t("Enter OAuth2 Credentials for Slack App:")); ?>
	<br/>
	<br/>
	<input type="text" name="slackClientID" id="slackClientID" length="100"
		value="<?php echo($_['slackClientID']); ?>"/>
	<label for="slackClientID"><?php p($l->t("Client ID")); ?></label>
	<br/>
	<input type="text" name="slackClientSecret" id="slackClientSecret"
		value="<?php echo($_['slackClientSecret']); ?>"/>
	<label for="slackClientSecret"><?php p($l->t("Client Secret")); ?></label>
	<br/>
	<button type="button" name="submitSlackKeys"><?php p($l->t("Save Credentials")); ?>
	</button>
	<span class="msg"></span>
</form>
