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

namespace PhpBg\MiniHttpd\Tests\Logger;

use PhpBg\MiniHttpd\Logger\Console;
use PhpBg\MiniHttpd\Logger\ConsoleFormatter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class ConsoleTest extends TestCase
{
    public function testLogException()
    {
        $output = fopen('php://memory', 'w+');
        $logger = new Console(LogLevel::DEBUG, $output);

        $logger->error("foo", ['exception' => new \Exception("bar")]);

        rewind($output);
        $this->assertNotEmpty(stream_get_contents($output));
    }

    public function testLogExceptionWithPrevious()
    {
        $output = fopen('php://memory', 'w+');
        $logger = new Console(LogLevel::DEBUG, $output);

        $logger->error("foo", ['exception' => new \Exception("bar", 0, new \Exception("baz"))]);

        rewind($output);
        $this->assertNotEmpty(stream_get_contents($output));
    }

    public function testLogError()
    {
        $output = fopen('php://memory', 'w+');
        $logger = new Console(LogLevel::DEBUG, $output);

        $logger->error("foo", ['exception' => new \Error("bar")]);

        rewind($output);
        $this->assertNotEmpty(stream_get_contents($output));
    }

    public function testLogContextNotJsonEncodable()
    {
        $output = fopen('php://memory', 'w+');
        $logger = new Console(LogLevel::DEBUG, $output);

        $logger->error("foo", ['bar' => \log(0)]);

        rewind($output);
        $msg = stream_get_contents($output);
        $this->assertNotEmpty($msg);
        $this->assertTrue(strpos($msg, 'could not log full context') !== false);
    }

    public function testLogStartWithDate()
    {
        $output = fopen('php://memory', 'w+');
        $logger = new Console(LogLevel::DEBUG, $output);

        $logger->error("foo");

        rewind($output);
        $msg = stream_get_contents($output);
        $this->assertNotEmpty($msg);
        $this->assertSame(1, preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', $msg));
    }

    public function testLogContainLevel()
    {
        $output = fopen('php://memory', 'w+');
        $logger = new Console(LogLevel::DEBUG, $output);

        $logger->error("foo");

        rewind($output);
        $msg = stream_get_contents($output);
        $this->assertNotEmpty($msg);
        $this->assertTrue(strrpos($msg, LogLevel::ERROR) !== false);
    }

    public function testCustomFormatterWithoutDate()
    {
        $output = fopen('php://memory', 'w+');
        $logger = new Console(LogLevel::DEBUG, $output, new ConsoleFormatter(false));

        $logger->error("foo");

        rewind($output);
        $msg = stream_get_contents($output);
        $this->assertNotEmpty($msg);
        $this->assertTrue(strrpos($msg, LogLevel::ERROR) !== false);
        $this->assertSame(0, preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', $msg));
    }
}