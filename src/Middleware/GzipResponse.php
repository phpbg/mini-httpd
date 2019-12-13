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

use PhpBg\MiniHttpd\Psr7Stream\MinLengthPumpStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class GzipResponse
 * GZIP response bodies, when necessary
 */
class GzipResponse
{
    protected $minSizeCompress;

    protected $compressibleMimeKeys;

    /**
     * @param array $compressibleMimes Array of mime types that can be compressed. E.g. ['application/json', 'text/html', ...]
     * @param int $minSizeCompress
     *   Minimum size under which we won't compress, in bytes.
     *   Defaults to some arbitrary choice around MTU...
     *   See interesting resources here:
     *    * https://webmasters.stackexchange.com/questions/31750/what-is-recommended-minimum-object-size-for-gzip-performance-benefits
     *    * https://www.itworld.com/article/2693941/cloud-computing/why-it-doesn-t-make-sense-to-gzip-all-content-from-your-web-server.html
     */
    public function __construct(array $compressibleMimes, int $minSizeCompress = 1400)
    {
        $this->compressibleMimeKeys = array_flip($compressibleMimes);
        $this->minSizeCompress = $minSizeCompress;
    }

    public function __invoke(ServerRequestInterface $request, $next)
    {
        $result = $next($request);

        // Convert all non-promise response to promises
        // There is little overhead for this, but this allows simpler code
        if (!$result instanceof PromiseInterface) {
            $result = new FulfilledPromise($result);
        }

        return $result->then(function (ResponseInterface $response) use ($request) {
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
            if (!isset($this->compressibleMimeKeys[$mime])) {
                return $response;
            }

            // We only compress PSR7 bodies
            // This is for react that may have non PSR7 bodies (probably ReadableStreamInterface)
            // TODO work on ReadableStreamInterface support
            if (!$response->getBody() instanceof StreamInterface) {
                return $response;
            }

            return $response
                ->withHeader('Content-Encoding', 'gzip')
                ->withBody($this->toCompressedStream($response->getBody()));
        });
    }

    /**
     * Pipe a PSR7 StreamInterface to a PSR7 compressed StreamInterface
     *
     * @param StreamInterface $body
     * @return StreamInterface
     */
    protected function toCompressedStream(StreamInterface $body): StreamInterface
    {
        //@see http://php.net/manual/en/function.deflate-init.php
        //@see http://www.zlib.net/manual.html#Constants
        $deflateContext = deflate_init(ZLIB_ENCODING_GZIP, ['level' => 5, 'memory' => 9, 'window' => 15]);
        if ($deflateContext === false) {
            throw new \RuntimeException("Unable to init deflate context");
        }
        $bodyRead = 0;
        $bodySize = $body->getSize();

        // GZIP output seems corrupted when reading too small chunks. Ensure we read at least 2 bytes by 2 bytes
        return new MinLengthPumpStream(2, function ($chunkSize) use ($body, $deflateContext, &$bodyRead, &$bodySize) {
            if ($body->eof()) {
                return null;
            }
            if (!$body->isReadable()) {
                return null;
            }
            $data = $body->read($chunkSize);
            // We do test for false and null because we do not trust StreamInterface implementations
            // e.g. some implementations rely on fread
            if ($data === false || $data === null || $data === '') {
                return null;
            }
            $bodyRead += strlen($data);
            $mode = ZLIB_NO_FLUSH;
            if ($body->eof() || ($bodySize !== null && $bodyRead >= $bodySize)) {
                // Make sure we will provide complete gzip chunks
                // eof may not be reached because it is not yet detected (reading the very last character without requiring more)
                // eof may not be reached because of PHP bugs, see https://bugs.php.net/bug.php?id=77146
                $mode = ZLIB_FINISH;
            }
            $ret = deflate_add($deflateContext, $data, $mode);
            if ($ret === false) {
                throw new \RuntimeException();
            }
            return $ret;
        });
    }
}
