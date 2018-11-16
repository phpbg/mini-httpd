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

namespace PhpBg\MiniHttpd\Model;

use PhpBg\MiniHttpd\Renderer\RendererInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Response;

/**
 * This class is used to pass data between all middlewares in both direction
 */
class RequestContext
{
    /**
     * @var ApplicationContext
     */
    public $applicationContext;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * URI path
     *   percent-decoded
     *   absolute (starting with /)
     * @see \Psr\Http\Message\UriInterface::getPath()
     * @var string
     */
    public $uriPathDecoded;

    /**
     * @var Route
     */
    public $route;

    /**
     * Options to pass to renderer
     * Options are renderer specific, @see your \PhpBg\MiniHttpd\Renderer\RendererInterface implementation
     *
     * @var array
     */
    public $renderOptions = [];

    public function __construct(ApplicationContext $context)
    {
        $this->applicationContext = $context;
    }

    /**
     * Return the response that will be returned
     * Remember that because of PSR-7, responses are immutable so @see RequestContext::setResponse()
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        if (!isset($this->response)) {
            $this->response = new Response();
        }
        return $this->response;
    }

    /**
     * Set the response that will be returned
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function setResponse(ResponseInterface $response): ResponseInterface
    {
        $this->response = $response;
        return $this->response;
    }

    /**
     * Return renderer instance to use
     *
     * @return RendererInterface
     */
    public function getRenderer(): RendererInterface
    {
        // Use route renderer if any
        if (isset($this->route->renderer)) {
            return $this->route->renderer;
        }

        // Otherwise use default renderer
        return $this->applicationContext->defaultRenderer;
    }
}