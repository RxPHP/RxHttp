<?php

include __DIR__ . '/../../vendor/autoload.php';


$imageTypes = ['png', 'jpeg', 'webp'];

$images = \Rx\Observable::fromArray($imageTypes)
    ->flatMap(function ($type) {
        return \Rx\React\Http::get("http://httpbin.org/image/{$type}")->map(function ($image) use ($type) {
            return [$type => $image];
        });
    });

$images->subscribe(
    function ($data) {
        echo 'Got Image: ', array_keys($data)[0], PHP_EOL;
    },
    function (\Throwable $e) {
        echo $e->getMessage(), PHP_EOL;
    },
    function () {
        echo 'completed', PHP_EOL;
    }
);
