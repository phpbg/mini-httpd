<?php

require __DIR__ . '/../vendor/autoload.php';

/**
 * This is a sample tasks management class.
 */
class TasksController extends \PhpBg\MiniHttpd\Controller\AbstractController
{
    use \PhpBg\MiniHttpd\Middleware\ContextTrait;

    // Initial tasks
    public $tasks = ['task1', 'task2'];

    /**
     * Simple add task example
     */
    public function add(\Psr\Http\Message\ServerRequestInterface $request)
    {
        try {
            $task = $this->getFromPost($request, 'task', null, new \Zend\Validator\NotEmpty());
        } catch (\PhpBg\MiniHttpd\Model\ValidateException $e) {
            throw new \PhpBg\MiniHttpd\HttpException\BadRequestException($e->getMessage());
        }
        $this->tasks[] = $task;
        return $this->tasks;
    }

    /**
     * Asynchronous add task example
     */
    public function addAsync(\Psr\Http\Message\ServerRequestInterface $request)
    {
        return new React\Promise\Promise(function($resolve, $reject) use ($request) {
            $this->getContext($request)->applicationContext->loop->addTimer(2, function() use ($resolve, $reject, $request) {
                try {
                    $task = $this->getFromPost($request, 'task', null, new \Zend\Validator\NotEmpty());
                } catch (\PhpBg\MiniHttpd\Model\ValidateException $e) {
                    return $reject(new \PhpBg\MiniHttpd\HttpException\BadRequestException($e->getMessage()));
                }
                $this->tasks[] = $task;
                return $resolve($this->tasks);
            });
        });
    }
}

$loop = React\EventLoop\Factory::create();
$jsonRenderer = new \PhpBg\MiniHttpd\Renderer\Json();
$taskController = new TasksController();
$routes = [
    // Redirection example
    '/' => new \PhpBg\MiniHttpd\Model\Route(function () {
        throw new \PhpBg\MiniHttpd\HttpException\RedirectException('/demo.html');
    }, $jsonRenderer),

    // Inline callable example, that return an array rendered as JSON
    '/api/task/get' => new \PhpBg\MiniHttpd\Model\Route(function () use ($taskController) {
        return $taskController->tasks;
    }, $jsonRenderer),

    // Controller callback, with request manipulation, that return an array rendered as JSON
    '/api/task/add' => new \PhpBg\MiniHttpd\Model\Route([$taskController, 'add'], $jsonRenderer),

    // Controller callback, with request manipulation, that return a promise
    '/api/task/add-async' => new \PhpBg\MiniHttpd\Model\Route([$taskController, 'addAsync'], $jsonRenderer),
];

// Application context will be injected in a request attribute.
// See \PhpBg\MiniHttpd\Middleware\ContextTrait to retrieve it easily
$applicationContext = new \PhpBg\MiniHttpd\Model\ApplicationContext();

// You may require loop for async tasks
$applicationContext->loop = $loop;

// You can put your configuration directves in options
$applicationContext->options = [];
$applicationContext->routes = $routes;

// Public path is where static files are served from
$applicationContext->publicPath = __DIR__ . '/public';

// You can share your PSR3 logger here
$applicationContext->logger = new \PhpBg\MiniHttpd\Logger\Console(\Psr\Log\LogLevel::DEBUG);

$server = new \React\Http\Server([
    // Log all incoming requests
    new \PhpBg\MiniHttpd\Middleware\LogRequest($applicationContext->logger),

    // Make application context and request context available to all middlewares. Allow data exchanging between middlewares
    new \PhpBg\MiniHttpd\Middleware\Context($applicationContext),

    // Decode once uri path
    new \PhpBg\MiniHttpd\Middleware\UriPath(),

    // Serve static files
    new \PhpBg\MiniHttpd\Middleware\StaticContent($applicationContext->publicPath),

    // Prepare fore rendering
    new \PhpBg\MiniHttpd\Middleware\Render($jsonRenderer),

    // Log exceptions
    new \PhpBg\MiniHttpd\Middleware\LogError($applicationContext->logger),

    // Calculate route
    new \PhpBg\MiniHttpd\Middleware\Route(),

    // Run route selected
    new \PhpBg\MiniHttpd\Middleware\Run()
]);

// Run server on port 8080
// just open your browser and go to http://localhost:8080
$socket = new React\Socket\Server(8080, $loop);
$server->listen($socket);
$loop->run();