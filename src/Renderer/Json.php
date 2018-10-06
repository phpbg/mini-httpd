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

namespace PhpBg\MiniHttpd\Renderer;

use PhpBg\MiniHttpd\HttpException\HttpException;
use PhpBg\MiniHttpd\Middleware\ContextTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function RingCentral\Psr7\stream_for;

/**
 * JSON Renderer
 */
class Json implements RendererInterface
{
    use ContextTrait;

    public function render(ServerRequestInterface $request, $data): ResponseInterface
    {
        $response = $this->getContext($request)->getResponse();
        $response = $response->withHeader('Content-Type', 'application/json');
        $response = $response->withBody(stream_for(json_encode($data)));
        return $response;
    }

    public function renderException(ServerRequestInterface $request, \Exception $exception): ResponseInterface
    {
        $response = $this->getContext($request)->getResponse();
        $response = $response->withHeader('Content-Type', 'application/json');
        if ($exception instanceof HttpException) {
            $response = $response->withStatus($exception->httpStatus);
            $data = [
                'message' => $exception->getMessage(),
                'details' => $exception->data
            ];
            $response = $response->withBody(stream_for(json_encode($data)));
        } else {
            $response = $response->withStatus(500);
        }

        return $response;
    }
}