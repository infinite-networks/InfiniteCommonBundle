<?php

/**
 * This file is part of the InfiniteCommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Activity;

class Context implements \ArrayAccess
{
    /**
     * Context holding array.
     * @var array
     */
    private $context;

    /**
     * Stores the result of the Activity callable.
     *
     * @var mixed
     */
    private $result;

    /**
     * @param array $context
     */
    public function __construct(array $context = [])
    {
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Merges previous context information into this context object.
     *
     * @param Context $previousContext
     */
    public function merge(Context $previousContext)
    {
        $this->context = array_merge($previousContext->context, $this->context);
    }

    public function offsetExists($offset)
    {
        return isset($this->context[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ?
            $this->context[$offset] :
            null;
    }

    public function offsetSet($offset, $value)
    {
        $this->context[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->context[$offset]);
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Returns an array representation of all context variables stored.
     *
     * @return array
     */
    public function toArray()
    {
        if (null === $this->result) {
            return $this->context;
        }

        return array_merge(['result' => $this->result], $this->context);
    }
}
