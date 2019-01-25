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

use PhpBg\MiniHttpd\Renderer\Phtml\Phtml;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Automatically calculate files to render with PHTML renderer
 * Will look for phtml, css and javascript files with same name/location as your controller
 * Example: your controller is called Test.php, it will look for Test.phtml, Test.css and Test.js
 */
class AutoPhtml
{
    use ContextTrait;

    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        $result = $next($request);
        $context = $this->getContext($request);
        $renderer = $context->getRenderer();
        if (! isset($renderer)) {
            return $result;
        }
        if (! $renderer instanceof Phtml) {
            return $result;
        }
        if (! is_object($context->route->handler)) {
            return $result;
        }
        $rc = new \ReflectionClass(get_class($context->route->handler));
        $controllerFilePath = $rc->getFileName();
        $controllerFilePathinfo = pathinfo($controllerFilePath);

        // PHTML view file
        $phtmlFile = "{$controllerFilePathinfo['dirname']}/{$controllerFilePathinfo['filename']}.phtml";
        if (!array_key_exists('viewFilePath', $context->renderOptions)) {
            $context->renderOptions['viewFilePath'] = $phtmlFile;
        }

        // JS file
        $jsFile = "{$controllerFilePathinfo['dirname']}/{$controllerFilePathinfo['filename']}.js";
        if (!isset($context->renderOptions['inlineScripts'])) {
            $context->renderOptions['inlineScripts'] = [];
        }
        if (is_file($jsFile)) {
            //TODO async file get contents?
            $context->renderOptions['inlineScripts'][] = file_get_contents($jsFile);
        }

        // CSS file
        $cssFile = "{$controllerFilePathinfo['dirname']}/{$controllerFilePathinfo['filename']}.css";
        if (!isset($context->renderOptions['inlineCss'])) {
            $context->renderOptions['inlineCss'] = [];
        }
        if (is_file($cssFile)) {
            //TODO async file get contents?
            $context->renderOptions['inlineCss'][] = file_get_contents($cssFile);
        }

        return $result;
    }
}