<?

// Your email address
$email = 'you@example.com';

// Test this query out on twitter.com/search to make sure you're getting the results you expect
$search_query = '"experimenting with babies" OR "correlated.org" OR "shaun gallagher" lang:en -from:shaun_gallagher';

// See: https://dev.twitter.com/apps
$settings = array(
    'oauth_access_token' => "GET THIS FROM TWITTER",
    'oauth_access_token_secret' => "GET THIS FROM TWITTER",
    'consumer_key' => "GET THIS FROM TWITTER",
    'consumer_secret' => "GET THIS FROM TWITTER"
);

// Your database
$link = mysqli_connect("localhost", "my_user", "my_password", "world");




// YOU NEED NOT EDIT BELOW THIS LINE

require_once('TwitterAPIExchange.php'); // https://github.com/J7mbo/twitter-api-php

$url = 'https://api.twitter.com/1.1/search/tweets.json';
$body = '';
$new_tweet_count = 0;
$getfield = '?q='.urlencode($search_query).'&count=50';
$requestMethod = 'GET';
$twitter = new TwitterAPIExchange($settings);

$json = $twitter->setGetfield($getfield)
       ->buildOauth($url, $requestMethod)
       ->performRequest();

$results = json_decode($json, $assoc=true);

$statuses = $results[statuses];

foreach ($statuses as $key => $value) {

    $id = $value[id];
    $sn = $value[user][screen_name];
    $created = $value[created_at];
    $name = $value[user][name];
    $text = $value[text];

    if ($stmt = mysqli_prepare($link, "SELECT tweet_id FROM twitter_search_results WHERE tweet_id = ?")) {

        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 0) {

            $stmt2 = mysqli_prepare($link, "INSERT INTO twitter_search_results VALUES (?)");
            mysqli_stmt_bind_param($stmt2, "s", $id);
            mysqli_stmt_execute($stmt2);

            $new_tweet_count++;

            $tweet = <<< EOF

<div style="margin:20px; padding:20px; border-bottom:1px solid #CCC;">
    <div style="margin-bottom:15px">
        <a href="http://www.twitter.com/$sn" style="font-size:16px; color:#1155CC; font-weight:bold; font-family:arial">
            $name
        </a> &nbsp;
        <a style="font-size:15px; color: #1155CC; font-weight:normal; font-family:arial">
            @$sn
        </a>
    </div>
    <div style="font-family:arial; font-size:18px; color:#000000; font-weight:normal; line-height:24px; margin-bottom:20px">
        $text
    </div>
    <div style="font-size:12px; color:#555555; font-family:arial; font-weight:normal">
        $created
    </div>
</div>

EOF;

            $body .= $tweet;

        }
    }

}

if ($new_tweet_count > 0) {
   mail($email, $new_tweet_count.' new tweets', $body, "Content-type: text/html\nFrom: Twitter Alerts");
}

