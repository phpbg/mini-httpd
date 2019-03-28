<?php

/**
 * MIT License
 *
 * Copyright (c) 2019 Samuel CHEMLA
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

namespace PhpBg\MiniHttpd\Logger;

class ConsoleFormatter
{
    use ExceptionFormatterTrait;

    /**
     * @var bool
     */
    protected $withDate;

    /**
     * ConsoleFormatter constructor
     *
     * @param bool $withDate Default: true; Wether to include a RFC3339 date in the message
     */
    public function __construct(bool $withDate = true)
    {
        $this->withDate = $withDate;
    }

    public function __invoke(string $level, string $message, array $context): string
    {
        $exception = null;
        if (isset($context['exception'])) {
            $exception = $context['exception'];
            unset($context['exception']);
        }

        if (!empty($context)) {
            $jsonMessage = \json_encode($context, JSON_PARTIAL_OUTPUT_ON_ERROR);
            if (\json_last_error() !== JSON_ERROR_NONE) {
                $jsonMsg = \json_last_error_msg();
                if (!empty($jsonMsg)) {
                    $message .= " <could not log full context because of: {$jsonMsg}>";
                }
            }
            if ($jsonMessage !== false) {
                // log even partial messages
                $message .= ' ' . $jsonMessage;
            }
        }

        if (isset($exception)) {
            $message .= ' ' . $this->formatException($exception);
        }

        if ($this->withDate) {
            return sprintf('%s [%s] %s', \date(\DateTime::RFC3339), $level, $message) . \PHP_EOL;
        } else {
            return sprintf('[%s] %s', $level, $message) . \PHP_EOL;
        }
    }
}
