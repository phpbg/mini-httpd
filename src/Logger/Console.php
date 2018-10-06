<?php

/**
 * This file was largely inspired from Symfony 4.0 Logger (symfony/src/Symfony/Component/HttpKernel/Log/Logger.php)
 *
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

namespace PhpBg\MiniHttpd\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

class Console extends AbstractLogger
{
    private static $levels = array(
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    );

    private $minLevelIndex;
    private $formatter;
    private $handle;

    public function __construct(string $minLevel = LogLevel::WARNING, $output = 'php://stderr', callable $formatter = null)
    {
        if (!isset(self::$levels[$minLevel])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $minLevel));
        }

        $this->minLevelIndex = self::$levels[$minLevel];
        $this->formatter = $formatter ?: array($this, 'format');
        if (false === $this->handle = \is_resource($output) ? $output : @fopen($output, 'a')) {
            throw new InvalidArgumentException(sprintf('Unable to open "%s".', $output));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        if (!isset(self::$levels[$level])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        if (self::$levels[$level] < $this->minLevelIndex) {
            return;
        }

        $formatter = $this->formatter;

        //TODO investigate async write
        fwrite($this->handle, $formatter($level, $message, $context));
    }

    private function format(string $level, string $message, array $context): string
    {
        $exception = null;
        if (isset($context['exception'])) {
            $exception = $context['exception'];
            unset($context['exception']);
        }

        if (!empty($context)) {
            $message .= ' ' . json_encode($context);
        }

        if (isset($exception)) {
            $message .= ' ' . $this->formatException($exception);
        }

        return sprintf('%s [%s] %s', date(\DateTime::RFC3339), $level, $message) . \PHP_EOL;
    }

    /**
     * Format an exception
     * @param \Exception $e
     * @param string $newLine Optionnal new line char
     * @return string
     */
    protected function formatException(\Exception $e, $newLine = PHP_EOL)
    {
        $message = '';

        $currentException = $e;
        while (true) {
            if ($currentException === $e) {
                $message .= $this->_formatException($currentException);
            } else {
                $message .= $newLine . 'Caused by: ' . $this->_formatException($currentException, $newLine);
            }

            $currentException = $currentException->getPrevious();
            if (!isset($currentException)) {
                break;
            }
        }
        return $message;
    }

    /**
     * Format an exception as string
     *
     * @param \Exception $e
     * @param string $newLine
     * @return string
     */
    protected function _formatException(\Exception $e, string $newLine = PHP_EOL): string
    {
        return get_class($e) . ': ' . $e->getMessage() . $newLine . '#> ' . $e->getFile() . '(' . $e->getLine() . ')' . $newLine . $e->getTraceAsString();
    }
}
