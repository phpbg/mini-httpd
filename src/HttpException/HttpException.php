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

namespace PhpBg\MiniHttpd\HttpException;

class HttpException extends \Exception
{
    /**
     * @var int
     */
    public $httpStatus;

    /**
     * @var mixed|null
     */
    public $data;

    /**
     * HttpException are meant to provide meaning to requester
     * For this reason you can pass a $message and further $data that should be rendered
     *
     * @param string $message Message that should be rendered
     * @param int $httpStatus The HTTP status that will be returned
     * @param mixed $data Any additionnal content that should be rendered
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $httpStatus = 500, $data = null, \Throwable $previous = null)
    {
        parent::__construct($message, 500, $previous);
        $this->httpStatus = $httpStatus;
        $this->data = $data;
    }
}