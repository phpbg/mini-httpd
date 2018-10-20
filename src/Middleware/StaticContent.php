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

namespace PhpBg\MiniHttpd\Middleware;

use PhpBg\MiniHttpd\MimeDb\MimeDb;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Serve static files
 */
class StaticContent
{
    use ContextTrait;

    /**
     * @see StaticContent::__construct()
     * @var string
     */
    protected $publicPath;

    /**
     * @var MimeDb
     */
    protected $mimeDb;

    /**
     * @param string $publicPath Full path to the directory that contain files to serve. Ex: /var/www/public
     * @param LoggerInterface $logger
     */
    public function __construct(string $publicPath, LoggerInterface $logger)
    {
        if (!is_dir($publicPath)) {
            throw new \RuntimeException();
        }
        $publicPath = realpath($publicPath);
        if ($publicPath === false) {
            throw new \RuntimeException();
        }
        $this->publicPath = $publicPath;
        $this->mimeDb = new MimeDb($logger);
    }

    public function __invoke(ServerRequestInterface $request, callable $next = null)
    {
        $pathDecoded = $this->getContext($request)->uriPathDecoded;
        $realPath = realpath("{$this->publicPath}{$pathDecoded}");

        // serve static file without calling next middleware
        // make sure we serve only within public path
        if ($realPath !== false && strpos($realPath, $this->publicPath) === 0 && is_file($realPath)) {
            $extension = pathinfo($realPath, PATHINFO_EXTENSION);
            $headers = [
                'Cache-Control' => 'public, max-age=31536000' //1 year of cache, make sure you change URLs when changing resources
            ];
            $mime = $this->mimeDb->getFromExtension($extension);
            if ($mime !== null) {
                $headers['Content-Type'] = $mime['name'];
            }
            return new \React\Http\Response(
                200,
                $headers,
                // Beware that this won't achieve good performance and scalability, mainly because native php file streams may or not be blocking, who knows...?
                // @see https://bugs.php.net/bug.php?id=75538
                fopen($realPath, 'rb')
            );
        }

        if ($next !== null) {
            return $next($request);
        }

        // If no next middleware, then issue a 404 not found
        return new \React\Http\Response(
            404
        );
    }
}