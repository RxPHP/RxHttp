<?php

include __DIR__ . '/../../vendor/autoload.php';

$bufferResult = false;
$source       = \Rx\React\Http::get('https://www.google.com/');

$source
    ->subscribeCallback(
        function ($data) {
            echo $data, PHP_EOL;
        },
        function (\Exception $e) {
            echo $e->getMessage(), PHP_EOL;
        },
        function () {
            echo "completed", PHP_EOL;
        }
    );
