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

namespace PhpBg\MiniHttpd\Controller;

use PhpBg\MiniHttpd\Middleware\ContextTrait;
use PhpBg\MiniHttpd\Model\ValidatedFiltered;
use PhpBg\MiniHttpd\Model\ValidateException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Filter\FilterInterface;
use Zend\Validator\ValidatorInterface;

/**
 * This is an optional base class for all controllers
 */
abstract class AbstractController
{
    use ContextTrait;
    use ValidatedFiltered;

    /**
     * Return the parameter $name from request (GET query string or POST data)
     * with precedence of $_GET on $_POST
     * validated by $validator
     *
     * @param ServerRequestInterface $request
     * @param string $name
     *            Name of param to retrieve
     * @param mixed $default
     *            Default value if parameter is not found
     * @param ValidatorInterface $validator
     *            Validator
     * @param FilterInterface $filter
     *            Optional filter to apply before validation
     * @return mixed
     * @throws ValidateException
     */
    protected function getFromBoth(ServerRequestInterface $request, string $name, $default, ValidatorInterface $validator, FilterInterface $filter = null)
    {
        $params = $request->getQueryParams();
        $post = $request->getParsedBody();
        if (!empty($post) && !is_array($post)) {
            // Convert $post to array
            $post = json_decode(json_encode($post), true);
        }
        if (!empty($post)) {
            $params += $post;
        }
        return $this->getValidatedFiltered($params, $name, $default, $validator, $filter);
    }

    /**
     * Return the parameter $name from query string, validated by $validator, filtered by $filter
     *
     * @param ServerRequestInterface $request
     * @param string $name
     *            Name of param to retrieve
     * @param mixed $default
     *            Default value if parameter is not found
     * @param ValidatorInterface $validator
     *            Validator
     * @param FilterInterface $filter
     *            Optional filter to apply before validation
     * @throws ValidateException
     * @return mixed
     */
    protected function getFromQuery(ServerRequestInterface $request, string $name, $default, ValidatorInterface $validator, FilterInterface $filter = null)
    {
        return $this->getValidatedFiltered($request->getQueryParams(), $name, $default, $validator, $filter);
    }

    /**
     * Return the parameter $name from query string, validated by $validator, filtered by $filter
     *
     * @param ServerRequestInterface $request
     * @param string $name
     *            Name of param to retrieve
     * @param mixed $default
     *            Default value if parameter is not found
     * @param ValidatorInterface $validator
     *            Validator
     * @param FilterInterface $filter
     *            Optional filter to apply before validation
     * @throws ValidateException
     * @return mixed
     */
    protected function getFromPost(ServerRequestInterface $request, string $name, $default, ValidatorInterface $validator, FilterInterface $filter = null)
    {
        return $this->getValidatedFiltered($request->getParsedBody(), $name, $default, $validator, $filter);
    }
}