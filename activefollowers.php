<?php

$total_statuses = 0;
$unknown_statuses = 0;

$dead_duration = array();

function process_follower($obj)
{
    global $total_statuses, $unknown_statuses, $dead_duration;

    $total_statuses++;

    if (!isset($obj->last_tweeted))
    {
        $unknown_statuses++;
        return;
    }

    $now = DateTime::createFromFormat("U", "" . time());

    $created_date = new DateTime($obj->created_at);
    $created_interval = $created_date->diff($now);

    $last_tweet_date = new DateTime($obj->last_tweeted);
    $last_tweet_interval = $last_tweet_date->diff($now);

    $lifetime_interval = $created_date->diff($last_tweet_date);

    $idx = ($last_tweet_interval->y * 12) + $last_tweet_interval->m;
    if (!isset($dead_duration[$idx]))
        $dead_duration[$idx] = 0;
    $dead_duration[$idx]++;
} // process_follower

$cachedir = 'followers';
if (!file_exists($cachedir))
{
    printf("Cache dir '$cachedir' doesn't exist! Run twitterlosses.php first?\n");
    exit(1);
}

$dirids = scandir($cachedir);
if ($dirids !== false)
{
    while (($dirids[0] == '.') || ($dirids[0] == '..'))
        array_shift($dirids);
}

foreach ($dirids as $id)
{
    $cachefname = "$cachedir/$id";
    $data = file_get_contents($cachefname);
    if ($data === false)
        print("couldn't read $cachefname!\n");
    else
    {
        $obj = unserialize($data);
        if ($obj === false)
            print("couldn't unserialize $cachefname!\n");
        else
            process_follower($obj);
    }
}

ksort($dead_duration, SORT_NUMERIC);

print("total followers: $total_statuses\n");
print("followers with no tweets ever or locked account: $unknown_statuses\n");
print("$unknown_statuses unknown-status followers (no tweets ever or private)\n");
foreach($dead_duration as $k => $v)
    print("Followers inactive >$k months: $v\n");

exit(0);
?>
