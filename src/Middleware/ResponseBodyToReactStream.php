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

use PhpBg\MiniHttpd\Model\ReactResponse;
use PhpBg\MiniHttpd\ReactStream\ReadeableStreamProxy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class ResponseBodyToReactStream.
 * Convert PSR-7 bodies to \React\Stream\ReadableStreamInterface.
 *
 * This will reduce memory pressure in some cases
 * The main case here is streaming big files opened with the blocking fopen()
 * The underlying react http will try to load it in memory, which will likely make your server crash or subject to DoS
 * This has been discussed here: https://github.com/reactphp/http/issues/328
 *
 * Of course opening those files with non blocking ReadableStreamInterface would be better, but:
 * non blocking filesystem access in react currently rely on processes / threads
 *   * eio: rely on threads
 *   * child processes: rely on a pool of processes
 * So maybe some users just want blocking filesystem, with multiple server processes/threads, which should offer similar performance (a benchmark would be nice)
 *
 * If you don't like this, just do not use it because it is completely optional
 */
class ResponseBodyToReactStream
{
    protected $minSizeConvert;

    /**
     * @param int $minSizeConvert Don't convert streams that have a too small size because there's no benefit for doing it
     */
    public function __construct(int $minSizeConvert = 65535)
    {
        $this->minSizeConvert = $minSizeConvert;
    }

    public function __invoke(ServerRequestInterface $request, $next)
    {
        $result = $next($request);

        // Convert all non-promise response to promises
        // There is little overhead for this, but this allows simpler code
        if (!$result instanceof PromiseInterface) {
            $result = new FulfilledPromise($result);
        }

        return $result->then(function (ResponseInterface $response) use ($request) {
            // response has no content body (no need to compress anything)
            // or it is not worth compressing it
            if ($response->hasHeader('Content-Length') && intval($response->getHeaderLine('Content-Length')) <= $this->minSizeConvert) {
                return $response;
            }
            $size = $response->getBody()->getSize();
            if ($size !== null && $size <= $this->minSizeConvert) {
                return $response;
            }

            // We aim at converting PSR7 bodies to ReadableStreamInterface, so filter out non PSR7 bodies
            if (!$response->getBody() instanceof StreamInterface) {
                return $response;
            }

            return new ReactResponse($response, new ReadeableStreamProxy($response->getBody()));
        });
    }
}
