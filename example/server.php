<?php

ini_set('memory_limit', '64M');

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
    }, $jsonRenderer),

    // Inline callable example, that return an array rendered as JSON
    '/api/task/get' => new \PhpBg\MiniHttpd\Model\Route(function () use ($taskController) {
        return $taskController->tasks;
    }, $jsonRenderer),

    // Controller callback, with request manipulation, that return an array rendered as JSON
    '/api/task/add' => new \PhpBg\MiniHttpd\Model\Route([$taskController, 'add'], $jsonRenderer),

    // Controller callback, with request manipulation, that return a promise
    '/api/task/add-async' => new \PhpBg\MiniHttpd\Model\Route([$taskController, 'addAsync'], $jsonRenderer),

    '/demo' => new \PhpBg\MiniHttpd\Model\Route(new Demo(), new \PhpBg\MiniHttpd\Renderer\Phtml\Phtml(__DIR__ . '/pages/layout.phtml')),
];

// Application context will be injected in a request attribute.
// See \PhpBg\MiniHttpd\Middleware\ContextTrait to retrieve it easily
$applicationContext = new \PhpBg\MiniHttpd\Model\ApplicationContext();

// You may require loop for async tasks
$applicationContext->loop = $loop;

// You can put your configuration directives in options
$applicationContext->options = [];
$applicationContext->routes = $routes;

// Public path is where static files are served from
$applicationContext->publicPath = __DIR__ . '/public';

// You can share your PSR3 logger here
$applicationContext->logger = new \PhpBg\MiniHttpd\Logger\Console(\Psr\Log\LogLevel::DEBUG);

$mimeDb = new \PhpBg\MiniHttpd\MimeDb\MimeDb($applicationContext->logger);

$server = new \React\Http\Server([
    // Log all incoming requests
    new \PhpBg\MiniHttpd\Middleware\LogRequest($applicationContext->logger),

    // Make application context and request context available to all middlewares. Allow data exchanging between middlewares
    new \PhpBg\MiniHttpd\Middleware\Context($applicationContext),

    // Decode once uri path
    new \PhpBg\MiniHttpd\Middleware\UriPath(),

    // Compress compressible responses
    new \PhpBg\MiniHttpd\Middleware\GzipResponse($mimeDb->getCompressible()),

    // Serve static files
    new \PhpBg\MiniHttpd\Middleware\StaticContent($applicationContext->publicPath, $applicationContext->logger),

    // Prepare fore rendering
    new \PhpBg\MiniHttpd\Middleware\Render($jsonRenderer),

    // Log exceptions
    new \PhpBg\MiniHttpd\Middleware\LogError($applicationContext->logger),

    // Calculate route
    new \PhpBg\MiniHttpd\Middleware\Route(),

    // Auto render PHTML files
    new \PhpBg\MiniHttpd\Middleware\AutoPhtml(),

    // Run route selected
    new \PhpBg\MiniHttpd\Middleware\Run()
]);

// Log server errors
$server->on('error', function($exception) use ($applicationContext) {
    $applicationContext->logger->error('', ['exception' => $exception]);
});

// Run server on port 8080
// just open your browser and go to http://localhost:8080
$socket = new React\Socket\Server(8080, $loop);
$server->listen($socket);
if (extension_loaded('xdebug')) {
    $applicationContext->logger->warning('The "xdebug" extension is loaded, this has a major impact on performance.');
}
$applicationContext->logger->notice("Server started");
$loop->run();