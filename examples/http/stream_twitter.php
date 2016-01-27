<?php

//Requires jacobkiers/oauth and rx/operator-extras

include __DIR__ . '/../../vendor/autoload.php';

const TWITTER_USER_ID = -1; // Use http://gettwitterid.com/ to get the wanted twitter ID
const CONSUMER_KEY    = '';
const CONSUMER_SECRET = '';
const TOKEN           = '';
const TOKEN_SECRET    = '';

function generateHeader($method, $url, $params = null)
{
    $consumer     = new JacobKiers\OAuth\Consumer\Consumer(CONSUMER_KEY, CONSUMER_SECRET);
    $token        = new JacobKiers\OAuth\Token\Token(TOKEN, TOKEN_SECRET);
    $oauthRequest = JacobKiers\OAuth\Request\Request::fromConsumerAndToken($consumer, $token, $method, $url, $params);
    $oauthRequest->signRequest(new JacobKiers\OAuth\SignatureMethod\HmacSha1(), $consumer, $token);
    return trim(substr($oauthRequest->toHeader(), 15));
}

$postData = 'follow=' . TWITTER_USER_ID;
$method   = 'POST';
$url      = 'https://stream.twitter.com/1.1/statuses/filter.json';
$headers  = [
    'Authorization'  => generateHeader($method, $url, ['follow' => TWITTER_USER_ID]),
    'Content-Type'   => 'application/x-www-form-urlencoded',
    'Content-Length' => strlen($postData),
];

$source = \Rx\React\Http::post($url, $postData, $headers, '1.1', false)->share();

$connected = $source->take(1)->doOnNext(function () {
    echo 'Connected to twitter, listening in on stream:', PHP_EOL;
});

$allTweets = $connected
    ->merge($source)
    ->lift(function () {
        return new \Rx\Extra\Operator\CutOperator(PHP_EOL);
    })
    ->filter(function ($tweet) {
        return strlen(trim($tweet)) > 0;
    })
    ->map(function ($tweet) {
        return json_decode($tweet);
    });

$endTwitterStream = $allTweets
    ->filter(function ($tweet) {
        return is_object($tweet);
    })
    ->filter(function ($tweet) {
        return trim($tweet->text) == 'exit();';
    })
    ->doOnNext(function ($twitter) {
        echo 'exit(); found, stopping...', PHP_EOL;
    });

$usersTweets = $allTweets->filter(function ($tweet) {
    return isset($tweet->user->screen_name);
});

$tweets = $usersTweets->takeUntil($endTwitterStream);

$urls = $tweets->flatMap(function ($tweet) {
    return \Rx\Observable::fromArray($tweet->entities->urls);
});

$measurementsSubscription = $urls
    ->filter(function ($url) {
        return substr($url->expanded_url, 0, 36) == 'https://atlas.ripe.net/measurements/';
    })->map(function ($url) {
        return trim(substr($url->expanded_url, 36), '/');
    })->flatMap(function ($id) {
        return \Rx\React\Http::get("https://atlas.ripe.net/api/v1/measurement/{$id}/");
    })->map(function ($data) {
        return json_decode($data);
    })->subscribeCallback(
        function ($json) {
            echo 'Measurement #', $json->msm_id, ' "', $json->description, '" had ', $json->participant_count, ' nodes involved', PHP_EOL;
        },
        function (\Rx\React\HttpResponseException $e) {
            echo "Error: ", $e->getMessage(), PHP_EOL;
        },
        function () {
            echo "complete", PHP_EOL;
        }
    );

$probesSubscription = $urls
    ->filter(function ($url) {
        return substr($url->expanded_url, 0, 30) == 'https://atlas.ripe.net/probes/';
    })->map(function ($url) {
        return trim(substr($url->expanded_url, 30), '/');
    })->flatMap(function ($id) {
        return \Rx\React\Http::get("https://atlas.ripe.net/api/v1/probe/{$id}/");
    })->map(function ($data) {
        return json_decode($data);
    })->subscribeCallback(
        function ($json) {
            echo 'Probe #', $json->id, ' connected since ' . date('r', $json->status_since), PHP_EOL;
        },
        function (\Exception $e) {
            echo "Error: ", $e->getMessage(), PHP_EOL;
        },
        function () {
            echo "complete", PHP_EOL;
        }
    );
