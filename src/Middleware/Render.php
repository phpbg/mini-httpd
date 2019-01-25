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

use PhpBg\MiniHttpd\HttpException\RedirectException;
use PhpBg\MiniHttpd\Renderer\RendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Render the data returned by route execution
 */
class Render
{
    use ContextTrait;

    /**
     * @var RendererInterface
     */
    protected $defaultRenderer;

    /**
     * Render constructor.
     * @param RendererInterface $defaultRenderer Default renderer, used mainly for "route not found" exceptions
     */
    public function __construct(RendererInterface $defaultRenderer)
    {
        $this->defaultRenderer = $defaultRenderer;
    }

    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        $context = $this->getContext($request);
        try {
            $result = $next($request);

            // Convert all non-promise response to promises
            // There is little overhead for this, but this allows simpler code
            if (!$result instanceof PromiseInterface) {
                $result = new FulfilledPromise($result);
            }

            return $result->then(
                function ($result) use ($context, $request) {
                    if ($result instanceof ResponseInterface) {
                        // Handle Response: passthrough
                        return $result;
                    }
                    $response = $context->getResponse();
                    $renderer = $this->getRenderer($request);
                    return $renderer->render($request, $response, $context->renderOptions, $result);
                },
                function (\Exception $e) use ($request) {
                    return $this->doRenderException($request, $e);
                }
            );
        } catch (\Exception $e) {
            return $this->doRenderException($request, $e);
        }
    }

    /**
     * Render exceptions
     * @param ServerRequestInterface $request
     * @param \Exception $e
     * @return ResponseInterface
     */
    protected function doRenderException(ServerRequestInterface $request, \Exception $e): ResponseInterface
    {
        if ($e instanceof RedirectException) {
            $response = $this->getContext($request)->getResponse();
            $response = $response->withHeader('Location', $e->url);
            $response = $response->withStatus($e->httpStatus);
            return $response;
        }

        $renderer = $this->getRenderer($request);
        $context = $this->getContext($request);
        $response = $context->getResponse();
        return $renderer->renderException($request, $response, $context->renderOptions, $e);
    }

    /**
     * Return renderer instance to use
     * Override this if you want another strategy, based for example on request "Accept" header
     *
     * @param ServerRequestInterface $request
     * @return RendererInterface
     */
    protected function getRenderer(ServerRequestInterface $request): RendererInterface
    {
        $context = $this->getContext($request);
        $renderer = $context->getRenderer();
        return $renderer ?? $this->defaultRenderer;
    }
}