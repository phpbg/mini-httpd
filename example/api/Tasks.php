<?php

/**
 * This is a sample tasks management class.
 */
final class Tasks extends \PhpBg\MiniHttpd\Controller\AbstractController
{
    use \PhpBg\MiniHttpd\Middleware\ContextTrait;

    // Initial tasks
    public $tasks = ['task1', 'task2'];

    private $loop;

    public function __construct(\React\EventLoop\LoopInterface $loop)
    {
        $this->loop = $loop;
    }

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
            $this->loop->addTimer(2, function() use ($resolve, $reject, $request) {
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