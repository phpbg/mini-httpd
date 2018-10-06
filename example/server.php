<?php

require __DIR__ . '/../vendor/autoload.php';

class TasksController extends \PhpBg\MiniHttpd\Controller\AbstractController
{
    use \PhpBg\MiniHttpd\Middleware\ContextTrait;

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

$applicationContext = new \PhpBg\MiniHttpd\Model\ApplicationContext();
$applicationContext->loop = $loop;
$applicationContext->options = [
    'this' => 'is where',
    'you can store' => 'configuration directives'];
$applicationContext->routes = $routes;
$applicationContext->publicPath = __DIR__ . '/public';
$applicationContext->logger = new \PhpBg\MiniHttpd\Logger\Console(\Psr\Log\LogLevel::DEBUG);

$server = new \React\Http\Server([
    new \PhpBg\MiniHttpd\Middleware\LogRequest($applicationContext->logger),
    new \PhpBg\MiniHttpd\Middleware\Context($applicationContext),
    new \PhpBg\MiniHttpd\Middleware\UriPath(),
    new \PhpBg\MiniHttpd\Middleware\StaticContent($applicationContext->publicPath),
    new \PhpBg\MiniHttpd\Middleware\Render($jsonRenderer),
    new \PhpBg\MiniHttpd\Middleware\LogError($applicationContext->logger),
    new \PhpBg\MiniHttpd\Middleware\Route(),
    new \PhpBg\MiniHttpd\Middleware\Run()
]);

$socket = new React\Socket\Server(8080, $loop);
$server->listen($socket);

$loop->run();