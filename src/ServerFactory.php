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

namespace PhpBg\MiniHttpd;

use PhpBg\MiniHttpd\Middleware\AutoPhtml;
use PhpBg\MiniHttpd\Middleware\Context;
use PhpBg\MiniHttpd\Middleware\GzipResponse;
use PhpBg\MiniHttpd\Middleware\LogError;
use PhpBg\MiniHttpd\Middleware\LogRequest;
use PhpBg\MiniHttpd\Middleware\Render;
use PhpBg\MiniHttpd\Middleware\ResponseBodyToReactStream;
use PhpBg\MiniHttpd\Middleware\Route;
use PhpBg\MiniHttpd\Middleware\Run;
use PhpBg\MiniHttpd\Middleware\StaticContent;
use PhpBg\MiniHttpd\Middleware\UriPath;
use PhpBg\MiniHttpd\MimeDb\MimeDb;
use PhpBg\MiniHttpd\MimeDb\MimeDbException;
use PhpBg\MiniHttpd\Model\ApplicationContext;
use React\Http\Server;

class ServerFactory
{
    /**
     * Create default stack handler
     *
     * @param ApplicationContext $applicationContext
     * @return array
     * @throws MimeDbException
     */
    public static function createDefaultStack(ApplicationContext $applicationContext): array
    {
        if (! isset($applicationContext->logger)) {
            throw new \RuntimeException("A logger is required in your application context");
        }
        $mimeDb = new MimeDb($applicationContext->logger);
        $middlewares = [];
        // Log all incoming requests
        $middlewares[] = new LogRequest($applicationContext->logger);

        // Make application context and request context available to all middlewares. Allow data exchanging between middlewares
        $middlewares[] = new Context($applicationContext);

        // Decode once uri path
        $middlewares[] = new UriPath();

        // Convert (some) PSR-7 bodies to react streams
        $middlewares[] = new ResponseBodyToReactStream($applicationContext->loop);

        // Compress compressible responses
        $middlewares[] = new GzipResponse($mimeDb->getCompressible());

        // Serve static files (only if a publicPath is provided)
        if (isset($applicationContext->publicPath) && file_exists($applicationContext->publicPath)) {
            $applicationContext->logger->notice("Serving *all* files from: $applicationContext->publicPath");
            $applicationContext->logger->notice("Don't hide your secrets there");
            $middlewares[] = new StaticContent($applicationContext->publicPath, $mimeDb->getNamesByExtension());
        }

        // Render responses
        if (! isset($applicationContext->defaultRenderer)) {
            throw new \RuntimeException("You must configure a defaultRenderer in your application context");
        }
        $middlewares[] = new Render($applicationContext->defaultRenderer);

        // Log exceptions
        $middlewares[] = new LogError($applicationContext->logger);

        // Calculate route to be run
        $middlewares[] = new Route();

        // Auto render PHTML files
        $middlewares[] = new AutoPhtml();

        // Run route selected
        $middlewares[] = new Run();

        return $middlewares;
    }

    /**
     * Creates a default, full featured, application stack
     *
     * @param ApplicationContext $applicationContext
     * @return Server
     * @throws MimeDbException
     */
    public static function create(ApplicationContext $applicationContext): Server
    {
        $middlewares = static::createDefaultStack($applicationContext);
        $server = new Server($middlewares);

        // Log server errors
        $server->on('error', function ($exception) use ($applicationContext) {
            $applicationContext->logger->error('Internal server error', ['exception' => $exception]);
        });

        return $server;
    }
}