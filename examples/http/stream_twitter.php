<?php

//Requires jacobkiers/oauth and rx/operator-extras

use Rx\Observable;
use Rx\React\Http;
use Rx\React\HttpResponseException;

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

$source = Http::post($url, $postData, $headers)
    ->streamResults()
    ->share();

$connected = $source
    ->take(1)
    ->do(function () {
        echo 'Connected to twitter, listening in on stream:', PHP_EOL;
    });

/** @var Observable $allTweets */
$allTweets = $connected
    ->merge($source)
    ->cut(PHP_EOL)
    ->filter(function ($tweet) {
        return strlen(trim($tweet)) > 0;
    })
    ->map('json_decode');

$endTwitterStream = $allTweets
    ->filter('is_object')
    ->filter(function ($tweet) {
        return trim($tweet->text) === 'exit();';
    })
    ->do(function ($twitter) {
        echo 'exit(); found, stopping...', PHP_EOL;
    });

$usersTweets = $allTweets->filter(function ($tweet) {
    return isset($tweet->user->screen_name);
});

$tweets = $usersTweets->takeUntil($endTwitterStream);

$urls = $tweets->flatMap(function ($tweet) {
    return Observable::fromArray($tweet->entities->urls);
});

$measurementsSubscription = $urls
    ->filter(function ($url) {
        return 0 === strpos($url->expanded_url, 'https://atlas.ripe.net/measurements/');
    })
    ->map(function ($url) {
        return trim(substr($url->expanded_url, 36), '/');
    })
    ->flatMap(function ($id) {
        return Http::get("https://atlas.ripe.net/api/v1/measurement/{$id}/");
    })
    ->map('json_decode')
    ->subscribe(
        function ($json) {
            echo 'Measurement #', $json->msm_id, ' "', $json->description, '" had ', $json->participant_count, ' nodes involved', PHP_EOL;
        },
        function (HttpResponseException $e) {
            echo 'Error: ', $e->getMessage(), PHP_EOL;
        },
        function () {
            echo 'complete', PHP_EOL;
        }
    );

$probesSubscription = $urls
    ->filter(function ($url) {
        return 0 === strpos($url->expanded_url, 'https://atlas.ripe.net/probes/');
    })
    ->map(function ($url) {
        return trim(substr($url->expanded_url, 30), '/');
    })
    ->flatMap(function ($id) {
        return Http::get("https://atlas.ripe.net/api/v1/probe/{$id}/");
    })
    ->map('json_decode')
    ->subscribe(
        function ($json) {
            echo 'Probe #', $json->id, ' connected since ' . date('r', $json->status_since), PHP_EOL;
        },
        function (\Throwable $e) {
            echo 'Error: ', $e->getMessage(), PHP_EOL;
        },
        function () {
            echo 'complete', PHP_EOL;
        }
    );
