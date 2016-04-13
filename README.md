# Slack Notification for ownCloud

Provides notifications via [Slack](https://slack.com/) concerning file 
activity.

### Download
Git master: [GitHub](https://ci.owncloud.org/job/server-master-linux/)

### Setup
Note, this app depends on the [activity](https://github.com/owncloud/activity)
app being installed and enabled on your ownCloud installaton. It makes use of
the Stream hooks in order to receive notifications. This was done to simplify
the code.

You will also need the one-liner fix to the activity app from [this commit](https://github.com/cyphre-llc/activity/commit/25966cbc5f6a8cf12a62ae9a697c3a1649f8a3d0),
or you can simply use that repo instead of the ownCloud one.

Next, go to the [Slack API](https://api.slack.com/applications) page and
create a new App. You will need the Client ID and Client Secret for the
next step.

Once you've enabled slacknotify in your ownCloud installation, go to the
Admin page (you will need to be logged in as an admin for this setup).
Find the Slack Integration section and enter your Client ID and Client
Secret. These are used to OAuth each user for Slack access.

At this point, you can also name the Bot that your notifications come from
(this applies for all users). You can also specify a URL for a custom
icon for the Bot.

Now that it's been enabled, each user will see a "Slack Integration"
section in their Personal page. They can choose to auth the App to send
them notifications.

To control which notifications are sent, select or unselect items in
the Activity app (Personal page as well) under the Stream column.

### Credits, License and Copyright
Author: [Ben Collins](mailto:ben@cyphre.com)

License: AGPL

Copyright: 2015 by [Cyphre, LLC](https://www.cyphre.com)
