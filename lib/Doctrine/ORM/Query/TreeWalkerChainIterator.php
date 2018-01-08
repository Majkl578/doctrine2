<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;
use function reset;
use function key;
use function next;

/**
 *
 */
class TreeWalkerChainIterator implements \Iterator, \ArrayAccess
{
    /**
     * @var TreeWalker[]
     */
    private $walkers = [];
    /**
     * @var TreeWalkerChain
     */
    private $treeWalkerChain;
    /**
     * @var
     */
    private $query;
    /**
     * @var
     */
    private $parserResult;

    public function __construct(TreeWalkerChain $treeWalkerChain, $query, $parserResult)
    {
        $this->treeWalkerChain = $treeWalkerChain;
        $this->query = $query;
        $this->parserResult = $parserResult;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        return reset($this->walkers);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->offsetGet(key($this->walkers));
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->walkers);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->walkers);

        return $this->offsetGet(key($this->walkers));
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return key($this->walkers) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->walkers[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return new $this->walkers[$offset](
                $this->query,
                $this->parserResult,
                $this->treeWalkerChain->getQueryComponents()
            );
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->walkers[] = $value;
        } else {
            $this->walkers[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->walkers[$offset]);
        }
    }
}
