# PhpBg\MiniHttpd
This is a small http server framework built on top of [React HTTP](https://github.com/reactphp/http).

This framework is designed to build quick proofs of concepts.

It is **not** mature enough to run in production environments, because:
 * it still contains synchronous blocking code
 * it lacks a dependency (ioc) /configuration management

## License
MIT

## Features
Most of features are directly inherited from [PHP React HTTP](https://github.com/reactphp/http)
* [Middleware](https://github.com/reactphp/http#middleware) based
  * see https://github.com/reactphp/http/wiki/Middleware for interesting middlewares you can add to this library
* Highly customizable
* [PSR-7 messages](https://www.php-fig.org/psr/psr-7/)
* Basic routing
* Basic [PSR-3 logging](https://www.php-fig.org/psr/psr-3/)
* Static files serving
* Let you focus on **your service logic**, not **building a HTTP response** (boooring)
  * Just return `arrays` or `objects` in your route handlers, that's it:
  ```php
  function (ServerRequestInterface $request) {
      return ['hello' => 'world'];
  }
  ```

## Install
Install with [composer](https://getcomposer.org/):
```
composer require phpbg/mini-httpd
```

## Examples
See `example` folder
* `bare-minimal-json-server.php` shows the very minimal setup for json rendering
* `full-featured-server.php` shows a full setup with:
  * Static files serving
  * Route redirection examples
  * Automatic PHTML renderer features and suggested layout
  * Accessing request params with proper validation / filtering

There is also a complete example that integrates with ratchet Websockets here: https://github.com/phpbg/mini-httpd-ratchet

## TODO
* writing tests
