<?php
/* Load required lib files. */
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');


function cache_users(&$userobjs, $cachedir, $idsstr)
{
    global $connection;

    //print("Getting $idsstr ...\n");

    $data = $connection->get('users/lookup', array('user_id' => $idsstr, 'include_entities' => 'true'));
    if ($data === false)
    {
        print("get users failed:\n");
        print("  - HTTP connection totally failed. Network burp?\n");
        return;
    }
    else if (isset($data->errors))
    {
        print("get users failed:\n");
        foreach ($data->errors as $err)
            print("  - {$err->message}\n");
        return;
    }

    //print_r($data);
    foreach ($data as $obj)
    {
        unset($data->status);  // don't care about this.
        $fname = $obj->id_str;
        $username = $obj->screen_name;
        $cachefname = "$cachedir/$fname";
        $obj->account_was_deleted = false;
        if (file_put_contents($cachefname, serialize($obj)) === false)
        {
            print("couldn't write $cachefname (user $username)!\n");
            if (file_exists($cachefname))
                unlink($cachefname);
            continue;
        }

        $userobjs[] = $obj;
    }
} // cache_users

function print_user_object($obj)
{
    if ($obj->account_was_deleted)
        print("*** THIS ACCOUNT APPEARS TO HAVE BEEN DELETED. ***\n");  // POSSIBLY A SPAMMER.
    print("real name: {$obj->name}\n");
    print("screen name: {$obj->screen_name}\n");
    print("description: {$obj->description}\n");
    print("location: {$obj->location}\n");
    print("url: {$obj->url}\n");
    print("twitter url: https://twitter.com/{$obj->screen_name}\n");
    print("twitter url-by-id: https://twitter.com/intent/user?user_id={$obj->id_str}\n");
    print("tweet count: {$obj->statuses_count}\n");
    print("id: {$obj->id_str}\n");
    print("lang: {$obj->lang}\n");
    print("created: {$obj->created_at}\n");
    print("following: {$obj->friends_count}\n");
    print("followers: {$obj->followers_count}\n");
    print("favorites: {$obj->favourites_count}\n");
    print("timezone: {$obj->time_zone}\n");
    print("are following: " . (($obj->following == '1') ? 'yes' : 'no') . "\n");
    print("protected: " . (($obj->protected == '1') ? 'yes' : 'no') . "\n");
    print("verified: " . (($obj->verified == '1') ? 'yes' : 'no') . "\n");
    print("\n");
} // print_user_object


function print_user_objects($type, &$userobjs)
{
    if (count($userobjs) == 0)
        return;

    print("*** $type:\n\n");
    foreach ($userobjs as $obj)
        print_user_object($obj);
} // print_user_objects


function print_cached_user_objects($type, $cachedir, $ids)
{
    $userobjs = array();
    foreach ($ids as $id)
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
                $userobjs[] = $obj;
        }
    }

    print_user_objects($type, $userobjs);
    unset($userobjs);
} // print_cached_user_objects

function check_if_users_still_exist($cachedir, $ids)
{
    foreach ($ids as $id)
    {
        $cachefname = "$cachedir/$id";
        $data = file_get_contents($cachefname);
        if ($data === false)
        {
            print("couldn't read $cachefname!\n");
            continue;
        }
        $obj = unserialize($data);
        if ($obj === false)
        {
            print("couldn't unserialize $cachefname!\n");
            continue;
        }

        // this is not foolproof, of course, but it's good enough.
        //print("Checking if {$obj->screen_name} ({$obj->name}) still exists...");
        $handle = popen("curl -I 'https://twitter.com/intent/user?user_id={$obj->id_str}' 2>/dev/null |head -n 1 |perl -w -p -e 's/\AHTTP\/1.1 (\d+) .*?\Z/$1/;'", 'r');
        $rc = intval(fgets($handle));
        pclose($handle);
        //print("$rc\n");
        if ($rc == 404)
        {
            $obj->account_was_deleted = true;
            file_put_contents($cachefname, serialize($obj));
        }
    }
} // check_if_users_still_exist


/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_SECRET);

/* If method is set change API call made. Test is called by default. */
$content = $connection->get('account/verify_credentials');

$twitterids = array();

$cursor = '-1';
while ($cursor != '0')
{
    //print("cursor == $cursor\n");
    $data = $connection->get('followers/ids', array('screen_name' => TWITTER_USERNAME, 'count' => '5000', 'cursor' => "$cursor", 'stringify_ids' => 'true'));
    if ($data === false)
    {
        print("get followers (cursor=$cursor) failed:\n");
        print("  - HTTP connection totally failed. Network burp?\n");
        exit(1);
    }
    else if (isset($data->errors))
    {
        print("get followers (cursor=$cursor) failed:\n");
        foreach ($data->errors as $err)
            print("  - {$err->message}\n");
        exit(1);
    }

    foreach ($data->ids as $id)
        $twitterids[$id] = $id;

    //print_r($data);

    $cursor = $data->next_cursor_str;
}

//print("saw " . count($twitterids) . " ids\n");

$cachedir = 'followers';
if (!file_exists($cachedir))
    mkdir($cachedir);

$dirids = scandir($cachedir);
if ($dirids !== false)
{
    while (($dirids[0] == '.') || ($dirids[0] == '..'))
        array_shift($dirids);
}

$cachedids = array();
foreach ($dirids as $id)
    $cachedids[$id] = $id;
unset($dirids);

$newfollows = array();
foreach ($twitterids as $id)
{
    if (!isset($cachedids[$id]))
        $newfollows[] = $id;
}

$unfollows = array();
foreach ($cachedids as $id)
{
    if (!isset($twitterids[$id]))
        $unfollows[] = $id;
}

//print_cached_user_objects("All followers", $cachedir, $cachedids);
unset($twitterids);
unset($cachedids);

$userobjs = array();
$count = 0;
$idsstr = '';
foreach ($newfollows as $id)
{
    if ($id == '109134780')  // !!! FIXME: what is this?
        continue;

    if ($idsstr != '')
        $idsstr .= ',';
    $idsstr .= $id;
    $count++;
    if ($count >= 100)
    {
        cache_users($userobjs, $cachedir, $idsstr);
        $idsstr = '';
        $count = 0;
    }
}

if ($count > 0)
    cache_users($userobjs, $cachedir, $idsstr);

check_if_users_still_exist($cachedir, $unfollows);
print_cached_user_objects("Unfollows", $cachedir, $unfollows);
print_user_objects("New follows", $userobjs);

foreach ($unfollows as $id)
{
    $cachefname = "$cachedir/$id";
    if (file_exists($cachefname))
        unlink($cachefname);
}

exit(0);

?>

