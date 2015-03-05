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
        <input type="text" name="slackBotName" id="slackBotName"
                value="<?php echo($_['slackBotName']); ?>"/>
        <label for="slackBotName"><?php p($l->t("Slack Bot Name")); ?></label>
        <br/>
        <input type="text" name="slackIconUrl" id="slackIconUrl"
                value="<?php echo($_['slackIconUrl']); ?>"/>
        <label for="slackIconUrl"><?php p($l->t("Slack Bot Icon URL")); ?></label>
        <br/>
	<button type="button" name="submitSlackKeys"><?php p($l->t("Save")); ?>
	</button>
	<span class="msg"></span>
</form>
