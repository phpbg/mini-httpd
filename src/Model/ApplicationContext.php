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
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

/**
 * Common ApplicationContext shared across everybody
 * Public fields are not guaranteed to be initialized so please make your checks when accessing them
 */
class ApplicationContext
{
    /**
     * @var LoopInterface
     */
    public $loop;

    /**
     * Array of <uri paths> => <Route>
     * @var Route[]
     */
    public $routes;

    /**
     * Path where static files are served from
     * @var string
     */
    public $publicPath;

    /**
     * Root path of the application
     * @var string
     */
    public $rootPath;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var RendererInterface
     */
    public $defaultRenderer;
}