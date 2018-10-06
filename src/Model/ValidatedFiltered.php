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

use Zend\Filter\FilterInterface;
use Zend\Validator\ValidatorInterface;

trait ValidatedFiltered
{
    /**
     * Return the $key from an array $data, validated by $validator, filtered by $filter
     *
     * @param array|object $data
     *            Array or object containing data to retrieve
     * @param string $key
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
    protected function getValidatedFiltered($data, string $key, $default, ValidatorInterface $validator, FilterInterface $filter = null)
    {
        if (is_array($data)) {
            $value = array_key_exists($key, $data) ? $data[$key] : $default;
        } else if (is_object($data)) {
            $value = property_exists($data, $key) ? $data->{$key} : $default;
        } else {
            $value = $default;
        }

        if (!empty($filter)) {
            $value = $filter->filter($value);
        }

        if ($validator->isValid($value)) {
            return $value;
        }

        throw new ValidateException('Invalid parameter ' . $key . ':' . implode('; ', $validator->getMessages()));
    }
}
