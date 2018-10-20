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

    protected $byName;

    public function __construct(LoggerInterface $logger)
    {
        $dbFile = __DIR__ . '/db.json';
        if (!is_file($dbFile)) {
            throw new MimeDbException("{$dbFile} not found");
        }

        $dbStr = file_get_contents($dbFile);
        if ($dbStr === false) {
            throw new MimeDbException("Could not read {$dbFile}");
        }

        $this->byName = json_decode($dbStr, true);
        if ($this->byName === false) {
            throw new MimeDbException("Could not parse {$dbFile}");
        }

        $extensions = [];
        foreach ($this->byName as $mimeName => $mimeInfo) {
            if (empty($mimeInfo['extensions'])) {
                continue;
            }
            foreach ($mimeInfo['extensions'] as $extension) {
                $extensions[$extension][] = array_merge(['name' => $mimeName], $mimeInfo);
            }

        }

        // Handle extension duplicates
        $extensionsFromIana = [];
        $extensionsWhatever = [];
        $this->byExtension = [];
        foreach ($extensions as $extension => $extensionList) {
            $this->byExtension[$extension] = $this->selectExtensionDescriptor($extension, $extensionList, $extensionsFromIana, $extensionsWhatever);
        };
        $logger->debug("MIME extensions",$extensions);
        $logger->info("MIME: " . count($extensions) . " extensions loaded");
        $logger->debug("MIME: following extensions have duplicates resolved with IANA flavor", $extensionsFromIana);
        $logger->debug("MIME: following extensions have duplicates resolved randomly", $extensionsWhatever);
    }

    protected function selectExtensionDescriptor(string $extension, array $extensionList, array &$extensionsFromIana, array &$extensionsWhatever)
    {
        if (count($extensionList) === 1) {
            // No duplicate: GG
            return array_pop($extensionList);
        }
        foreach ($extensionList as $extensionDescriptor) {
            if (isset($extensionDescriptor['source']) && $extensionDescriptor['source'] == 'iana') {
                // Prefer IANA if any
                $extensionsFromIana[] = $extension;
                return $extensionDescriptor;
            }
        }
        // Whatever...
        $extensionsWhatever[] = $extension;
        return array_pop($extensionList);
    }

    /**
     * Return mime descriptor by file extension.
     * Mime descriptor is an array containing:
     *   - name: string: mime type name (aka "content type" in HTTP context). Eg application/json
     *   - other data described here: https://github.com/jshttp/mime-db
     *
     * @param string $extension
     * @return array|null
     */
    public function getFromExtension(string $extension)
    {
        if (empty($extension)) {
            return null;
        }
        return $this->byExtension[$extension] ?? null;
    }

    /**
     * Return compressible mime names
     * @return array
     */
    public function getCompressible():array {
        $compressible = [];
        foreach ($this->byName as $name => $descriptor) {
            if (isset($descriptor['compressible']) && $descriptor['compressible']) {
                $compressible[] = $name;
            }
        }
        return $compressible;
    }
}