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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use React\Stream\ReadableStreamInterface;

/**
 * Allow ResponseInterface to have ReadableStreamInterface bodies
 *
 * @internal This is only to be used with react as it is not really legal...
 */
final class ReactResponse implements ResponseInterface
{

    protected $proxiedResponse;

    protected $body;

    public function __construct(ResponseInterface $proxiedResponse, ReadableStreamInterface $body)
    {
        $this->proxiedResponse = $proxiedResponse;
        $this->body = $body;
    }

    public function getProtocolVersion()
    {
        return $this->proxiedResponse->getProtocolVersion();
    }

    public function withProtocolVersion($version)
    {
        $newResponse = $this->proxiedResponse->withProtocolVersion($version);
        return new static($newResponse, $this->body);
    }

    public function getHeaders()
    {
        return $this->proxiedResponse->getHeaders();
    }

    public function hasHeader($name)
    {
        return $this->proxiedResponse->hasHeader($name);
    }

    public function getHeader($name)
    {
        return $this->proxiedResponse->getHeader($name);
    }

    public function getHeaderLine($name)
    {
        return $this->proxiedResponse->getHeaderLine($name);
    }

    public function withHeader($name, $value)
    {
        $newResponse = $this->proxiedResponse->withHeader($name, $value);
        return new static($newResponse, $this->body);
    }

    public function withAddedHeader($name, $value)
    {
        $newResponse = $this->proxiedResponse->withAddedHeader($name, $value);
        return new static($newResponse, $this->body);
    }

    public function withoutHeader($name)
    {
        $newResponse = $this->proxiedResponse->withoutHeader($name);
        return new static($newResponse, $this->body);
    }

    public function getBody()
    {
        // OMG we're not respecting ResponseInterface here...
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        throw new \RuntimeException('Are you sure you want to put a StreamInterface body on an object created to have ReadableStreamInterface body');
    }

    public function getStatusCode()
    {
        return $this->proxiedResponse->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        $newResponse = $this->proxiedResponse->withStatus($code, $reasonPhrase = '');
        return new static($newResponse, $this->body);
    }

    public function getReasonPhrase()
    {
        return $this->proxiedResponse->getReasonPhrase();
    }
}