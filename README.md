# PhpBg\MiniHttpd
This is a small http server framework built on top of [React HTTP](https://github.com/reactphp/http).

This framework is designed to build quick proofs of concepts.

It is **not** mature enough to run in production environments, because:
 * it still contains synchronous blocking code
 * react http itself is not stable

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
* Focus on **returning** data, not **building response to render** data
  * Route handlers can return simple array or objects

## Install
Install with [composer](https://getcomposer.org/):
```
composer require phpbg/mini-httpd
```

## Examples
See `example` folder for a complete demo.
