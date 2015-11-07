To set this up:

(These dev.twitter.com instructions are right as of November 2012, but the UI
could always change. Wing it.)

- Go to dev.twitter.com, login, go to "My applications" on the menu that pops
up when you mouse over your avatar on the top right of the page.
- Click the "Create a new application" button.
- Fill in whatever you want here. You can leave "Callback URL" blank. Agree to
the license and click the create button.
- When you app is created, click "Create my access token" at the bottom of the
next page.

This app only needs to be read-only; it never tries to post or edit anything
with your account. We just need this so we can grab your follower list from
Twitter.

Now you should have four magic values on that page: Consumer Key,
Consumer Secret, Access Token, and Access Token Secret.

Make a file in this directory called config.php, with these four magic values,
and your screen name, like this:


<?php
define('CONSUMER_KEY', '3k9sjcS4thSfsfsgW');
define('CONSUMER_SECRET', 'AKCdfsdfSF9sdf98989sdfFSsFQasdfokfAdsfqFRTAcVx');
define("OAUTH_TOKEN", '34672782-wwgovSDFSdf9sdf0DFSFLf08afamkg2GZga';
define("OAUTH_SECRET", 'sf0fkv9s82sffah2k3333FSFsfkskaf9aFAf9faghjQ');
define("TWITTER_USERNAME", 'icculus');


Now run "php ./twitterlosses.php" and if all went well, it should pull in all
of your current users, assuming you aren't like Beyonce famous. This first
run or so will dump out a lot of information, and cache the users it sees.
Once you are set up, future runs will just report changes from what is already
known (that is, new people following and accounts that unfollow you).

You'll want to run twitterlosses.php in a cronjob, so new info shows up
whenever the script sees it. I use the included mailupdate.sh script in a
cronjob every 15 minutes, but you can do whatever you like.

You may need to sniff around for hardcoded things. Grep for 'icculus' to
fix a hardcoded URL or two. Send patches.

Questions: ask me.

--ryan.

icculus@icculus.org

