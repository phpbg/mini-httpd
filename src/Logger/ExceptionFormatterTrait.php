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

trait ExceptionFormatterTrait
{
    /**
     * Format an exception as a full stack trace string, including previous exceptions
     *
     * @param \Throwable $e
     * @param string $newLine Optionnal new line char, e.g "\r\n", or "<br>", default: PHP_EOL
     * @return string
     */
    protected function formatException(\Throwable $e, $newLine = PHP_EOL): string
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
     * @param \Throwable $e
     * @param string $newLine
     * @return string
     */
    private function _formatException(\Throwable $e, string $newLine = PHP_EOL): string
    {
        return get_class($e) . ': ' . $e->getMessage() . $newLine . '#> ' . $e->getFile() . '(' . $e->getLine() . ')' . $newLine . $e->getTraceAsString();
    }
}
