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

namespace PhpBg\MiniHttpd\MimeDb;

use Psr\Log\LoggerInterface;

/**
 * Class MimeDb
 * Provide mime info based on a file extension
 * File extensions having multiple mime types will be ignored
 *
 * Inspired from https://github.com/narrowspark/mimetypes and unsing https://github.com/jshttp/mime-db
 */
class MimeDb
{
    protected $byExtension;

    public function __construct(LoggerInterface $logger)
    {
        $dbFile = __DIR__ . '/db.json';
        if (! is_file($dbFile)) {
            throw new MimeDbException("{$dbFile} not found");
        }

        $dbStr = file_get_contents($dbFile);
        if ($dbStr === false) {
            throw new MimeDbException("Could not read {$dbFile}");
        }

        $dbArray = json_decode($dbStr, true);
        if ($dbArray === false) {
            throw new MimeDbException("Could not parse {$dbFile}");
        }

        $extensions = [];
        $extensionsToDelete = [];
        foreach ($dbArray as $mimeName => $mimeInfo) {
            if (empty($mimeInfo['extensions'])) {
                continue;
            }
            foreach ($mimeInfo['extensions'] as $extension) {
                if (isset($extensions[$extension])) {
                    $extensionsToDelete[] = $extension;
                    continue;
                }
                $extensions[$extension] = [
                    'name' => $mimeName,
                    'compressible' => $mimeInfo['compressible'] ?? false
                ];
            }

        }
        if (! empty($extensionsToDelete)) {
            $logger->debug("MIME extensions ignored (duplicates)", $extensionsToDelete);
            foreach ($extensionsToDelete as $extension) {
                unset($extensions[$extension]);
            }
        }

        $this->byExtension = $extensions;
        $logger->debug(count($extensions) . " MIME extensions loaded");
    }

    /**
     * Return mime info by file extension.
     * Mime info is an array containing:
     *   - name: string: mime type name (aka "content type" in HTTP context). Eg application/json
     *   - compressible: boolean: tells whether the mime type can be compressed
     *
     * @param string $extension
     * @return array|null
     */
    public function getFromExtension(string $extension) {
        if (empty($extension)) {
            return null;
        }
        return $this->byExtension[$extension] ?? null;
    }
}