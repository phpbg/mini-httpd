<?php

// Standard composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Manual requires for demo purpose.
// In real life use composer autoload features
require __DIR__ . '/api/Tasks.php';
require __DIR__ . '/pages/Demo.php';

$loop = React\EventLoop\Factory::create();
$jsonRenderer = new \PhpBg\MiniHttpd\Renderer\Json();
$taskController = new Tasks();
$routes = [
    // Redirection example
    '/' => new \PhpBg\MiniHttpd\Model\Route(function () {
        throw new \PhpBg\MiniHttpd\HttpException\RedirectException('/demo');
    }),

    // Inline callable example, that return an array rendered as JSON
    '/api/task/get' => new \PhpBg\MiniHttpd\Model\Route(function () use ($taskController) {
        return $taskController->tasks;
    }, $jsonRenderer),

    // Controller callback, with request manipulation, that return an array rendered as JSON
    '/api/task/add' => new \PhpBg\MiniHttpd\Model\Route([$taskController, 'add'], $jsonRenderer),

    // Controller callback, with request manipulation, that return a promise
    '/api/task/add-async' => new \PhpBg\MiniHttpd\Model\Route([$taskController, 'addAsync'], $jsonRenderer),

    // This is the suggested example for all your PHTML pages
    // It consists of
    //  * Demo.php: a PHP controller that will handle the request
    //  * Demo.phtml: a file that will receive controller data and generate a response
    //  * optional Demo.css and Demo.js files that will be inlined with the response
    '/demo' => new \PhpBg\MiniHttpd\Model\Route(new Demo(), new \PhpBg\MiniHttpd\Renderer\Phtml\Phtml(__DIR__ . '/pages/layout.phtml')),
];

// Application context will be accessible everywhere
// @See \PhpBg\MiniHttpd\Middleware\ContextTrait to retrieve it easily
$applicationContext = new \PhpBg\MiniHttpd\Model\ApplicationContext();

// You may require loop for async tasks
$applicationContext->loop = $loop;

// You can put your configuration directives in options
$applicationContext->options = [];
$applicationContext->routes = $routes;

// Public path is where static files are served from (optional)
$applicationContext->publicPath = __DIR__ . '/public';

// You can share your PSR3 logger here
$applicationContext->logger = new \PhpBg\MiniHttpd\Logger\Console(\Psr\Log\LogLevel::DEBUG);

$applicationContext->defaultRenderer = $jsonRenderer;

$server = \PhpBg\MiniHttpd\ServerFactory::create($applicationContext);

// Listen on port 8080
// If you want to listen on all interfaces, then use 'tcp://0.0.0.0:8080'
// @see \React\Socket\TcpServer and @see \React\Socket\SecureServer for more options
$socket = new React\Socket\Server(8080, $loop);
$server->listen($socket);
if (extension_loaded('xdebug')) {
    $applicationContext->logger->warning('The "xdebug" extension is loaded, this has a major impact on performance.');
}
$applicationContext->logger->notice("Server started");

// now just open your browser and go to http://localhost:8080
$loop->run();