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

use PhpBg\MiniHttpd\Middleware\GzipResponse;
use PhpBg\MiniHttpd\Tests\Mocks\EmptyResponseMiddleware;
use PhpBg\MiniHttpd\Tests\Mocks\StringResponseMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RingCentral\Psr7\ServerRequest;

class GzipResponseTest extends TestCase
{
    public function testCompressStringNotChunked()
    {
        $minSizeCompress = 1400;
        $gzipResponseMiddleware = new GzipResponse(['text/plain'], $minSizeCompress);
        $body10k = str_repeat('0', $minSizeCompress + 1);
        $request = new ServerRequest('GET', '/foo', [
            'Accept-Encoding' => 'gzip, deflate, br',
            'Content-Type' => 'text/plain'
        ], $body10k);

        $response = $gzipResponseMiddleware($request, new StringResponseMiddleware());
        $extractedResponse = null;
        $response->done(function ($response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $extractedResponse);
        /** @var ResponseInterface $extractedResponse */
        $this->assertSame('gzip', $extractedResponse->getHeaderLine('Content-Encoding'));
        $gzippedContent = $extractedResponse->getBody()->getContents();
        $this->checkGzip($gzippedContent);
        $this->assertSame($body10k, gzdecode($gzippedContent));
    }

    /**
     * @dataProvider CompressChunkedProvider
     */
    public function testCompressStringChunked(int $chunkSize)
    {
        $minSizeCompress = 1400;
        $gzipResponseMiddleware = new GzipResponse(['text/plain'], $minSizeCompress);
        $body10k = str_repeat('0', $minSizeCompress + 1);
        $request = new ServerRequest('GET', '/foo', [
            'Accept-Encoding' => 'gzip, deflate, br',
            'Content-Type' => 'text/plain'
        ], $body10k);

        $response = $gzipResponseMiddleware($request, new StringResponseMiddleware());
        $extractedResponse = null;
        $response->done(function ($response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $extractedResponse);
        /** @var ResponseInterface $extractedResponse */
        $this->assertSame('gzip', $extractedResponse->getHeaderLine('Content-Encoding'));
        $gzippedContent = '';
        while (!$extractedResponse->getBody()->eof()) {
            $data = $extractedResponse->getBody()->read($chunkSize);
            if (!empty($data)) {
                $gzippedContent .= $data;
            }
        }
        $this->checkGzip($gzippedContent);
        $testFile = __DIR__ . '/test.gz';
        @unlink($testFile);
        $this->assertFalse(file_exists($testFile));
        file_put_contents($testFile, $gzippedContent);
        $this->assertSame($body10k, gzdecode($gzippedContent));
        @unlink($testFile);
    }

    public function CompressChunkedProvider()
    {
        // Test reading different chunk sizes
        return [
            [1],
            [2],
            [10],
            [10000],
            [65535]
        ];
    }

    /**
     * Help understanding why gzipped data is corrupted, but gzip data is never corrupted, is it? :-)
     * https://www.ietf.org/rfc/rfc1952.txt
     *
     * @param string $data
     * @throws \Exception
     */
    protected function checkGzip(string $data)
    {
        $len = strlen($data);
        if ($len < 18) {
            throw new \Exception("Too short to be GZIP");
        }
        if (substr($data, 0, 2) !== "\x1f\x8b") {
            throw new \Exception("No GZIP ID");
        }
    }

    public function testNoCompressTooSmall()
    {
        $minSizeCompress = 1400;
        $gzipResponseMiddleware = new GzipResponse(['text/plain'], $minSizeCompress);
        $body = str_repeat('0', $minSizeCompress - 1);
        $request = new ServerRequest('GET', '/foo', [
            'Accept-Encoding' => 'gzip, deflate, br',
            'Content-Type' => 'text/plain'
        ], $body);

        $response = $gzipResponseMiddleware($request, new StringResponseMiddleware());
        $extractedResponse = null;
        $response->done(function ($response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $extractedResponse);
        /** @var ResponseInterface $extractedResponse */
        $this->assertEmpty($extractedResponse->getHeaderLine('Content-Encoding'));
        $this->assertSame($body, $extractedResponse->getBody()->getContents());
    }

    public function testNoCompressNotCompressible()
    {
        $minSizeCompress = 1400;
        $gzipResponseMiddleware = new GzipResponse(['foo/bar'], $minSizeCompress);
        $body = str_repeat('0', $minSizeCompress + 1);
        $request = new ServerRequest('GET', '/foo', [
            'Accept-Encoding' => 'gzip, deflate, br',
            'Content-Type' => 'text/plain'
        ], $body);

        $response = $gzipResponseMiddleware($request, new StringResponseMiddleware());
        $extractedResponse = null;
        $response->done(function ($response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $extractedResponse);
        /** @var ResponseInterface $extractedResponse */
        $this->assertEmpty($extractedResponse->getHeaderLine('Content-Encoding'));
        $this->assertSame($body, $extractedResponse->getBody()->getContents());
    }

    public function testNoBodyNoCompress()
    {
        $gzipResponseMiddleware = new GzipResponse(['application/json']);
        $request = new ServerRequest('GET', '/foo');

        $response = $gzipResponseMiddleware($request, new EmptyResponseMiddleware());
        $extractedResponse = null;
        $response->done(function ($response) use (&$extractedResponse) {
            // We expect a FullFilledPromise that resolve instantly,
            // so extracting response and making assertions outside of callback secure them
            $extractedResponse = $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $extractedResponse);
        /** @var ResponseInterface $extractedResponse */
        $this->assertEmpty($extractedResponse->getHeaderLine('Content-Encoding'));
    }

    /**
     * This test reveals a bug on older versions of PHP
     * https://github.com/guzzle/psr7/issues/222
     *
     * @dataProvider StreamReadEofProvider
     */
    public function testStreamReadEof($stream, $data)
    {
        $this->assertSame(strlen($data), $stream->getSize());
        $this->assertSame($data, $stream->read(strlen($data) + 1));
        $this->assertSame(strlen($data), $stream->tell());
        $this->assertTrue($stream->eof(), "Your PHP version is affected by https://bugs.php.net/bug.php?id=77146");
    }

    public function StreamReadEofProvider()
    {
        $data = "foo";
        return [
            [\GuzzleHttp\Psr7\stream_for($data), $data],
            [\RingCentral\Psr7\stream_for($data), $data]
        ];
    }

    /**
     * This test reveals a bug on older versions of PHP
     * https://bugs.php.net/bug.php?id=77146
     */
    public function testPlainPhpMemoryStreamRead()
    {
        $data = "foo";
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $data);
        fseek($stream, 0);
        $this->assertSame($data, fread($stream, 10));
        $this->assertSame(strlen($data), ftell($stream));
        $this->assertTrue(feof($stream), "Your PHP version is affected by https://bugs.php.net/bug.php?id=77146");
    }
}