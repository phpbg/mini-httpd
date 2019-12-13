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

namespace PhpBg\MiniHttpd\Renderer\Phtml;

class Template
{
    private $__filepath;
    private $__data;

    /**
     * @param string $filepath Full path to the phtml template file to render
     * @param mixed $data Data transmitted to view. These data will be accessible like object property on the view. It must be either an array or an object
     */
    public function __construct(string $filepath, $data = null)
    {
        if (!is_file($filepath)) {
            throw new PhtmlException("{$filepath} does not exist");
        }
        $this->__filepath = $filepath;

        if ($data !== null && !is_array($data) && !is_object($data)) {
            throw new PhtmlException("You must pass data either as an array or as an object");
        }
        $this->__data = $data;
    }

    /**
     * Return rendered content as string
     * @throws PhtmlException
     */
    public function getContent()
    {
        if (false === ob_start()) {
            throw new PhtmlException("Couldn't start output buffering");
        }
        // TODO: do we need to go async for this?
        require $this->__filepath;
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }

    /**
     * Magic getter to access data transmitted to view
     * @param string $name
     */
    public function __get($name)
    {
        if ($this->__data == null) {
            return null;
        }
        // Array
        if (is_array($this->__data)) {
            if (array_key_exists($name, $this->__data)) {
                return $this->__data[$name];
            }
            return null;
        }

        // ArrayAccess
        if ($this->__data instanceof \ArrayAccess) {
            if ($this->__data->offsetExists($name)) {
                return $this->__data[$name];
            }
            return null;
        }

        // Plain object
        if (property_exists($this->__data, $name)) {
            return $this->__data->$name;
        }
        return null;
    }

    /**
     * Magic isset on data transmitted to view
     * @param string $name
     */
    public function __isset($name)
    {
        if ($this->__data == null) {
            return false;
        }
        if (is_array($this->__data) || $this->__data instanceof \ArrayAccess) {
            return isset($this->__data[$name]);
        }
        return isset($this->__data->$name);
    }

    public function __toString()
    {
        return $this->getContent();
    }
}