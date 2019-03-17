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

namespace PhpBg\MiniHttpd\Tests\Renderer;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PhpBg\MiniHttpd\HttpException\NotFoundException;
use PhpBg\MiniHttpd\Renderer\Json;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testRenderOK()
    {
        $renderer = new Json();
        $request = new ServerRequest('GET', '/');
        $response = new Response();
        $data = ['foo' => 'bar'];

        $response = $renderer->render($request, $response, [], $data);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(json_encode($data), $response->getBody()->getContents());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testRenderKO()
    {
        $renderer = new Json();
        $request = new ServerRequest('GET', '/');
        $response = new Response();
        $data = ['foo' => log(0)];

        $response = $renderer->render($request, $response, [], $data);

        $this->assertSame(500, $response->getStatusCode());
        $msg = $response->getBody()->getContents();
        $this->assertNotEmpty($msg);
        $this->assertSame(0, strpos($msg, "Error"));
    }

    public function testRenderHttpException()
    {
        $renderer = new Json();
        $request = new ServerRequest('GET', '/');
        $response = new Response();
        $exception = new NotFoundException();

        $response = $renderer->renderException($request, $response, [], $exception);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRenderException()
    {
        $renderer = new Json();
        $request = new ServerRequest('GET', '/');
        $response = new Response();
        $exception = new \Exception();

        $response = $renderer->renderException($request, $response, [], $exception);

        $this->assertSame(500, $response->getStatusCode());
    }
}