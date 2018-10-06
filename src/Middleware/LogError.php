<?php

/**
 * MIT License
 *
 * Copyright (c) 2018 Samuel CHEMLA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace PhpBg\MiniHttpd\Middleware;

use PhpBg\MiniHttpd\HttpException\HttpException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use function React\Promise\reject;

/**
 * Log unexpected exceptions
 */
class LogError
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        try {
            $result = $next($request);
            if ($result instanceof PromiseInterface) {
                return $result->then(null, function (\Exception $e) use ($request) {
                    $this->doHandleException($request, $e);
                    return reject($e);
                });

            }
            return $result;
        } catch (\Exception $e) {
            $this->doHandleException($request, $e);
            throw $e;
        }
    }

    protected function doHandleException(ServerRequestInterface $request, \Exception $e)
    {
        if ($e instanceof HttpException) {
            // HTTPExceptions are meant to be rendered, so there is no reason to log them
            return;
        } else {
            $this->logger->debug($request->getMethod() . ' ' . $request->getUri()->__toString() . ' raised an exception: ', ['exception' => $e]);
        }
    }
}