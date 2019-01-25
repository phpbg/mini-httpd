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

namespace PhpBg\MiniHttpd\ReactStream;

use Evenement\EventEmitter;
use Psr\Http\Message\StreamInterface;
use React\Http\StreamingServer;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

/**
 * Class ReadableStreamProxy
 * Proxies a PSR-7 Stream to make it look like \React\Stream\ReadableStreamInterface
 *
 * PSR-7 Streams and ReadableStreamInterface are of quite different nature, so don't expect a miracle here.
 * The stream
 *   * won't emit data until you call resume() or pipe(),
 *   * won't stop reading the underlying PSR-7 stream, **probably in a blocking-mode**, unless
 *     * it reaches eof
 *     * you explicitely call pause() or close()
 */
class ReadableStreamProxy extends EventEmitter implements ReadableStreamInterface
{
    protected $psr7stream;

    protected $paused;

    protected $chunkSize;

    protected $closed;

    public function __construct(StreamInterface $psr7stream, int $chunkSize = 65535)
    {
        $this->psr7stream = $psr7stream;
        $this->chunkSize = $chunkSize;
        $this->pause();
        $this->closed = !$this->isReadable();
    }

    /**
     * This getSize() is just here for react http compatibility
     * @see StreamingServer::handleResponse() that wants to get size of the response body (that itself should be a ResponseInterface but isn't)
     *
     * @return int|null
     */
    public function getSize() {
        return $this->psr7stream->getSize();
    }

    public function pause()
    {
        $this->paused = true;
    }

    public function isReadable()
    {
        return $this->psr7stream->isReadable();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        return Util::pipe($this, $dest, $options);
    }

    public function resume()
    {
        if ($this->closed) {
            return;
        }
        $this->paused = false;
        $this->doResume();
    }

    protected function doResume()
    {
        while (!$this->psr7stream->eof()) {
            if ($this->paused) {
                return;
            }
            if ($this->closed) {
                return;
            }
            $data = $this->psr7stream->read($this->chunkSize);
            if ($data === false || $data === null) {
                break;
            }
            $this->emit('data', [$data]);
        }
        $this->emit('end');
        $this->close();
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }
        $this->psr7stream->close();
        $this->closed = true;
    }
}