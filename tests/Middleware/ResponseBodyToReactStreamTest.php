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

namespace PhpBg\MiniHttpd\Tests\Middleware;

use GuzzleHttp\Psr7\ServerRequest;
use PhpBg\MiniHttpd\Middleware\ResponseBodyToReactStream;
use PhpBg\MiniHttpd\Model\ReactResponse;
use PhpBg\MiniHttpd\ReactStream\ReadableStreamProxy;
use PhpBg\MiniHttpd\Tests\Mocks\StringResponseMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use React\Stream\ReadableStreamInterface;

class ResponseBodyToReactStreamTest extends TestCase
{
    public function testShouldBeConverted()
    {
        $minSize = 100;
        $responseBodyToReactStreamMiddleware = new ResponseBodyToReactStream($minSize);
        $body = str_repeat('0', $minSize + 1);
        $request = new ServerRequest('GET', '/foo', ['Content-Type' => 'text/plain'], $body);

        $response = $responseBodyToReactStreamMiddleware($request, new StringResponseMiddleware());

        $extractedResponse = null;
        $response->done(function ($response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $extractedResponse);
        $this->assertInstanceOf(ReactResponse::class, $extractedResponse);
        /** @var ReactResponse $extractedResponse */
        $readableBody = $extractedResponse->getBody();
        $this->assertInstanceOf(ReadableStreamInterface::class, $readableBody);
        $this->assertInstanceOf(ReadableStreamProxy::class, $readableBody);
        /** @var ReadableStreamProxy $readableBody */
        $this->assertSame($minSize+1, $readableBody->getSize());
        $readData = '';
        $readableBody->on('data', function($data) use (&$readData) {
            $readData .= $data;
        });

        // Body will be instantly read on resume()
        $readableBody->resume();
        $this->assertSame($body, $readData);
    }

    public function testShouldNotBeConverted()
    {
        $minSize = 100;
        $responseBodyToReactStreamMiddleware = new ResponseBodyToReactStream($minSize);
        $body = str_repeat('0', $minSize - 1);
        $request = new ServerRequest('GET', '/foo', ['Content-Type' => 'text/plain'], $body);

        $response = $responseBodyToReactStreamMiddleware($request, new StringResponseMiddleware());

        $extractedResponse = null;
        $response->done(function ($response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $extractedResponse);
        $this->assertNotInstanceOf(ReactResponse::class, $extractedResponse);
    }
}