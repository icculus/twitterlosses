<?php
/* Load required lib files. */
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_SECRET);

/* If method is set change API call made. Test is called by default. */
$content = $connection->get('account/verify_credentials');

$num_tweets = 0;
$max_id = -1;
$tweeters = array();

print("Gathering tweets until Twitter kicks us out...\n");
while (true)
{
    print("Asking for more tweets from Twitter (max_id=$max_id)...\n");
    $args = array('count' => 200, 'contributor_details' => 'false', 'include_entities' => 'false');
    if ($max_id != -1)
        $args['max_id'] = $max_id;

    $data = $connection->get('statuses/home_timeline', $args);
    if ($data === false)
    {
        print("get home_timeline (max_id=$max_id) failed:\n");
        print("  - HTTP connection totally failed. Network burp?\n");
        break;
    }
    else if (isset($data->errors))
    {
        print("get home_timeline (max_id=$max_id) failed:\n");
        foreach ($data->errors as $err)
            print("  - {$err->message}\n");
        break;
    }

    $new_tweets = count($data);
    $num_tweets += $new_tweets;

    foreach ($data as $tweet)
    {
        if (!isset($tweeters[$tweet->user->screen_name]))
            $tweeters[$tweet->user->screen_name] = 1;
        else
            $tweeters[$tweet->user->screen_name]++;
        #print("{$tweet->user->screen_name}: {$tweet->text}\n");
        $max_id = $tweet->id - 1;
    }

    print("Got $new_tweets more tweets (max_id=$max_id).\n");

    if ($new_tweets == 0)
    {
        printf("...so we're done.\n");
        break;
    }
}

if ($num_tweets == 0) {
    print("Uhoh, didn't get _any_ tweets! Check credentials, or try again later.\n");
    exit(1);
}

arsort($tweeters, SORT_NUMERIC);
print("Top tweeters at the moment...\n");
foreach ($tweeters as $key => $val) {
    print("$key: $val tweets\n");
}

print("\n");
print("The higher on the list, the more time you probably spend reading\n");
print("that account's tweets vs the others. 140 characters add up!\n");
print("You might benefit from just turning off retweets from some users.\n");
print("Also, this is only the last $num_tweets tweets on your timeline,\n");
print("so this list can change drastically by time of day, etc. Run me again later!\n");

