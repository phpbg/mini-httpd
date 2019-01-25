<?php

// Standard composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Create a React EventLoop
$loop = React\EventLoop\Factory::create();

// Application context will be accessible everywhere
// @See \PhpBg\MiniHttpd\Middleware\ContextTrait to retrieve it easily
$applicationContext = new \PhpBg\MiniHttpd\Model\ApplicationContext();
$applicationContext->loop = $loop;

// Default renderer
$applicationContext->defaultRenderer = new \PhpBg\MiniHttpd\Renderer\Json();

// Define a single route
$applicationContext->routes = [
    '/' => new \PhpBg\MiniHttpd\Model\Route(function () {
        return ['hello' => 'world'];
    }, $applicationContext->defaultRenderer)
];

// PSR3 logger
$applicationContext->logger = new \PhpBg\MiniHttpd\Logger\Console(\Psr\Log\LogLevel::DEBUG);

// Create a default application stack
$server = \PhpBg\MiniHttpd\ServerFactory::create($applicationContext);

// Listen on port 8080
$socket = new React\Socket\Server(8080, $loop);
$server->listen($socket);
$applicationContext->logger->notice("Server started");

// now just open your browser and go to http://localhost:8080
$loop->run();