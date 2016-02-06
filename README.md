# Http Client for RxPHP


This library is a RxPHP wrapper for the [ReactPHP's Http-client](https://github.com/reactphp/http-client) library.  It allows you to make asynchronous http calls and emit the results through an RxPHP observable.

It uses the [Voryx event-loop](https://github.com/voryx/event-loop) which behaves like the Javascript event-loop.  ie. You don't need to start it.


##Installation

Install dependencies using [composer](https://getcomposer.org/doc/00-intro.md#downloading-the-composer-executable)

      $ php composer.phar require "rx/http"      

## Usage

### Get
```php
    
$source = \Rx\React\Http::get('https://www.example.com/');

$source->subscribeCallback(
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
    
```

### Post
```php
    
$postData = json_encode(["test" => "data"]);
$headers  = ['Content-Type' => 'application/json'];

$source = \Rx\React\Http::post('https://www.example.com/', $postData, $headers);

$source->subscribeCallback(
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
    
```

### Multiple Asynchronous Requests

```php

$imageTypes = ["png", "jpeg", "webp"];

$images = \Rx\Observable::fromArray($imageTypes)
    ->flatMap(function ($type) {
        return \Rx\React\Http::get("http://httpbin.org/image/{$type}")->map(function ($image) use ($type) {
            return [$type => $image];
        });
    });

$images->subscribeCallback(
    function ($data) {
        echo "Got Image: ", array_keys($data)[0], PHP_EOL;
    },
    function (\Exception $e) {
        echo $e->getMessage(), PHP_EOL;
    },
    function () {
        echo "completed", PHP_EOL;
    }
);


```


For more information, see the [examples](examples).