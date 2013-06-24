<?php


require('OAuth.php');
require('TwitterOAuth.php');


class TwitterListMembers
{
    // The following four tokens can usually be found within http://dev.twitter.com/apps
    const CONSUMER_KEY = ''; // your Twitter app consumer key
    const CONSUMER_SECRET = ''; // you Twitter app consumer secret
    const OAUTH_TOKEN = ''; // your Twitter OAuth token for this app
    const OAUTH_SECRET = ''; // your Twitter OAuth secret for this app
    const TWITTER_LIST = 'yourtwitterlist'; // Twitter list to display
    const TWITTER_OWNER = 'yourtwittername'; // Twitter user who owns list

    public function get($owner, $list)
    {
        // Unfortunately, the GET LIST MEMBERS API requires authentication for
        // some reason, even when the same data is available on the website. So
        // this requires hard-coding (or otherwise storing) a user's OAuth
        // credentials in here. Be sure that the application has read-only
        // permissions to reduce the chance of tomfoolery.

        $oauth = new TwitterOAuth(self::CONSUMER_KEY, self::CONSUMER_SECRET, self::OAUTH_TOKEN, self::OAUTH_SECRET);
        $response = $oauth->OAuthRequest("https://api.twitter.com/1.1/lists/members.json", array(
            'slug' => $list,
            'owner_screen_name' => $owner,
        ), 'GET');
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
            $days = round((float) ($delta / 86400.0));

            $howlong = "$days days ago";
            if ($days > 7)
            {
                $howlong = date('F j \a\t g:ia', $then);
            }
        }

        return $howlong;
    }

    public static function linkify($text)
    {
        $text = preg_replace('?(http://[^\s]+)?', '<a href="$1">$1</a>', $text);
        $text = preg_replace('/@([a-zA-Z0-9_]+)/', '@<a href="http://twitter.com/$1">$1</a>', $text);

        return $text;
    }

    public function render($members)
    {
        foreach ((array) $members as $member)
        {
            $howLongAgo = self::howLongAgo(strtotime($member->status->created_at));

            echo <<<EOF
        <div id="twitter_roll" style="font-family: Arial; font-size: 8pt; width: 50%;">
            <img src="{$member->profile_image_url}" style="float: left; margin-right: 10px;">
            <a href="http://twitter.com/{$member->screen_name}" rel="me"><strong>{$member->name} </strong></a><br/>
            {$member->description}
EOF;

            $status  = "<span> " . self::linkify($member->status->text) . " </span>"
                    . "<a style=\"font-size: 75%;\" href=\"http://twitter.com/{$member->screen_name}/statuses/{$member->status->id_str}\"> $howLongAgo </a>";
            if ($member->protected)
            {
                $status = "<em> This person has protected their tweets. </em>";
            }

            echo <<<EOF
            <div style="clear: both;"></div>
            <a href="http://twitter.com/{$member->screen_name}" rel="me"> {$member->screen_name}</a> $status
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
        date_default_timezone_set('America/New_York');
        $list = new TwitterListMembers();

        try
        {
            $members = $list->get(TwitterListMembers::TWITTER_OWNER, TwitterListMembers::TWITTER_LIST);
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
