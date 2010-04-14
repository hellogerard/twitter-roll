<?php


require('OAuth.php');
require('TwitterOAuth.php');


class TwitterListMembers
{
    const CONSUMER_KEY = ''; // your Twitter app consumer key
    const CONSUMER_SECRET = ''; // you Twitter app consumer secret
    const OAUTH_TOKEN = ''; // your Twitter OAuth token for this app
    const OAUTH_SECRET = ''; // your Twitter OAuth secret for this app
    const TWITTER_LIST = 'yourtwittername/yourtwitterlist'; // Twitter list to display

    public function get($list)
    {
        // Unfortunately, the GET LIST MEMBERS API requires authentication for
        // some reason, even when the same data is available on the website. So
        // this requires hard-coding (or otherwise storing) a user's OAuth
        // credentials in here. Be sure that the application has read-only
        // permissions to reduce the chance of tomfoolery.

        $oauth = new TwitterOAuth(self::CONSUMER_KEY, self::CONSUMER_SECRET, self::OAUTH_TOKEN, self::OAUTH_SECRET);
        $response = $oauth->OAuthRequest("http://api.twitter.com/1/$list/members.json", array(), 'GET');
        $members = json_decode($response);

        usort($members->users, array(self, 'lastUpdated'));
        return $members->users;
    }

    public static function lastUpdated($member1, $member2)
    {
        $lastStatus1 = strtotime($member1->status->created_at);
        $lastStatus2 = strtotime($member2->status->created_at);

        return ($lastStatus1 > $lastStatus2) ? -1 : 1;
    }

    public static function howLongAgo($then)
    {
        $delta = time() - $then;

        if ($delta < 60) 
        {
            $howlong = 'less than a minute ago';
        }
        else if ($delta < 120) 
        {
            $howlong = 'about a minute ago';
        }
        else if ($delta < (60 * 60)) 
        {
            $howlong = round((float)($delta / 60.0)) . ' minutes ago';
        }
        else if ($delta < (120 * 60)) 
        {
            $howlong = 'about an hour ago';
        }
        else if ($delta < (24 * 60 * 60)) 
        {
            $howlong = 'about ' . round((float) ($delta / 3600.0)) . ' hours ago';
        }
        else if ($delta < (48 * 60 * 60)) 
        {
            $howlong = '1 day ago';
        }
        else 
        {
            $howlong = round((float) ($delta / 86400.0)) . ' days ago';
        }

        return $howlong;
    }

    public function render($members)
    {
        foreach ((array) $members as $member)
        {
            $howLongAgo = self::howLongAgo(strtotime($member->status->created_at));

            echo <<<EOF
        <div id="twitter_roll" style="font-family: Arial; font-size: 8pt; width: 50%;">
            <h3>{$member->name} ({$member->screen_name})</h3>
            <img src="{$member->profile_image_url}" style="float: left; margin-right: 10px;">
            <p>{$member->description}</p>
EOF;

            $status = "<span> {$member->status->text} </span> <a style=\"font-size: 85%;\" href=\"http://twitter.com/{$member->screen_name}/statuses/{$member->status->id}\"> $howLongAgo </a>";
            if ($member->protected)
            {
                $status = "<em> This person has protected their tweets. </em>";
            }

            echo <<<EOF
            <ul style="margin-left: 50px;">
                <li> $status </li>
            </ul>        
            <p> <a href="http://twitter.com/{$member->screen_name}" rel="me"> Follow {$member->screen_name} on Twitter</a> </p>
        </div>
EOF;
        }
    }
}

?>

<html>
  <body>
    <h2>Welcome to the Twitter roll!</h2>

    <p>
    <?php
        $list = new TwitterListMembers();

        try
        {
            $members = $list->get(TwitterListMembers::TWITTER_LIST);
            $list->render($members);
        }
        catch (Exception $e)
        {
            echo "<pre>Twitter API error. No Twitter roll for you!</pre>";
        }
    ?>
    </p>

  </body>
</html>
