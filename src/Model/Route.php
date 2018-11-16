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

class Route
{
    /**
     * @var callable
     */
    public $handler;

    /**
     * @var RendererInterface
     */
    public $renderer;

    /**
     * Route constructor.
     * @param callable $handler The callable that will handle the request.
     *   It will receive a \Psr\Http\Message\ServerRequestInterface as first parameter
     *   It must return either :
     *     - a \Psr\Http\Message\ResponseInterface
     *     - a Promise that resolve in a \Psr\Http\Message\ResponseInterface
     *     - a Promise that resolve in anything renderable (probably array or stdClass objects)
     *     - anything renderable (probably array or stdClass object)
     * @param RendererInterface|null $renderer A renderer, if different from default renderer
     */
    public function __construct(callable $handler, RendererInterface $renderer = null)
    {
        $this->handler = $handler;
        $this->renderer = $renderer;
    }
}