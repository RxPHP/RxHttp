<?php

include __DIR__ . '/../../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\Rx\Scheduler::setDefaultFactory(function () use ($loop) {
    return new \Rx\Scheduler\EventLoopScheduler($loop);
});

$connector = new \React\Socket\Connector($loop, ['dns' => (new React\Dns\Resolver\Factory())->create('4.2.2.2', $loop)]);

$source = (new \Rx\React\Client($loop, $connector))->request('GET', 'https://www.example.com/');

$source->subscribe(
    function ($data) {
        echo $data, PHP_EOL;
    },
    function (\Throwable $e) {
        echo $e->getMessage(), PHP_EOL;
    },
    function () {
        echo 'completed', PHP_EOL;
    }
);

$loop->run();
