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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use React\Http\Response;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\PumpStream;

/**
 * Class GzipResponse
 * GZIP response bodies, when necessary
 */
class GzipResponse
{

    /**
     * Minimum size under which we won't compress, in bytes.
     * Defaults to some arbitrary choice around MTU...
     * See interesting resources here:
     * @see https://webmasters.stackexchange.com/questions/31750/what-is-recommended-minimum-object-size-for-gzip-performance-benefits
     * @see https://www.itworld.com/article/2693941/cloud-computing/why-it-doesn-t-make-sense-to-gzip-all-content-from-your-web-server.html
     */
    protected $minSizeCompress = 1400;

    protected $compressibleMimeKeys;

    /**
     * @param array $compressibleMimes Array of mime types that can be compressed. E.g. ['application/json', 'text/html', ...]
     */
    public function __construct(array $compressibleMimes)
    {
        $this->compressibleMimeKeys = array_flip($compressibleMimes);
    }

    public function __invoke(ServerRequestInterface $request, $next)
    {
        $result = $next($request);

        // Convert all non-promise response to promises
        // There is little overhead for this, but this allows simpler code
        if (!$result instanceof PromiseInterface) {
            $result = new FulfilledPromise($result);
        }

        return $result->then(function (Response $response) use ($request) {
            // Does request accept gzip?
            $accept = $request->getHeaderLine('Accept-Encoding');
            if (stristr($accept, 'gzip') === false) {
                return $response;
            }

            // response got already encoded
            if ($response->hasHeader('Content-Encoding')) {
                return $response;
            }

            // response has no content body (no need to compress anything)
            // or it is not worth compressing it
            if ($response->hasHeader('Content-Length') && intval($response->getHeaderLine('Content-Length')) <= $this->minSizeCompress) {
                return $response;
            }
            $size = $response->getBody()->getSize();
            if ($size !== null && $size <= $this->minSizeCompress) {
                return $response;
            }


            // compressible content-type is unknown
            if (!$response->hasHeader('Content-Type')) {
                return $response;
            }

            // response mime-type is not compressible
            $mime = $response->getHeaderLine('Content-Type');
            if (! isset($this->compressibleMimeKeys[$mime])) {
                return $response;
            }

            return $response
                ->withHeader('Content-Encoding', 'gzip')
                ->withBody($this->toCompressedStream($response->getBody()));
        });
    }

    /**
     * Pipe a StreamInterface to a compressed StreamInterface
     *
     * @param StreamInterface $body
     * @return StreamInterface
     */
    protected function toCompressedStream(StreamInterface $body): StreamInterface
    {
        return new PumpStream(function ($chunkSize) use ($body) {
            if ($body->eof()) {
                return null;
            }
            if (!$body->isReadable()) {
                return null;
            }
            $data = $body->read($chunkSize);
            if ($data === false || $data === null) {
                return null;
            }
            return gzencode($data);
        });
    }
}
