<?php

namespace ZanderBaldwin\Collect;

class Collect implements \IteratorAggregate
{
    /** @var \Iterator  */
    private $input;

    /**
     * @param array|callable|\IteratorAggregate|\Iterator $input
     */
    public function __construct($input)
    {
        if (is_callable($input)) {
            $reflect = new \ReflectionFunction($input);
            if (!$reflect->isGenerator()) {
                throw new \InvalidArgumentException('Callable is not a generator.');
            }
            $this->input = call_user_func($input);
        } elseif (is_array($input)) {
            $this->input = new \ArrayIterator($input);
        } elseif ($input instanceof \IteratorAggregate) {
            $this->input = $input->getIterator();
        } elseif ($input instanceof \Iterator) {
            $this->input = $input;
        } else {
            throw new \InvalidArgumentException('Cannot use $input as collection.');
        }
    }

    /**
     * Clone Collection
     */
    public function __clone()
    {
        if (is_object($this->input)) {
            $this->input = clone $this->input;
        }
    }

    /**
     * Retrieve Wrapped Iterator
     *
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable
     * @since 5.0.0
     */
    public function getIterator()
    {
        return $this->input;
    }

    /**
     * @param mixed $value
     * @param scalar $key
     * @return static
     */
    public function append($value, $key = null)
    {
        if ($key !== null && !is_scalar($key)) {
            throw new \InvalidArgumentException('Element key is not scalar.');
        }
        return new static(function () use ($value, $key) {
            foreach ($this->input as $collectionKey => $collectionValue) {
                yield $collectionKey => $collectionValue;
            }
            if ($key !== null) {
                yield $key => $value;
            } else {
                yield $value;
            }
        });
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function apply(callable $callback)
    {
        $collection = call_user_func($callback, $this);
        if (!$collection instanceof static) {
            throw new \RuntimeException('Apply callback does not return a collection.');
        }
        return $collection;
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    public function applyFlattening(callable $callback)
    {
        return call_user_func($callback, $this);
    }

    /**
     * @param \\Traversable|array $items
     * @return static
     */
    public function concat($items)
    {
        if (!is_array($items) && !$items instanceof \Traversable) {
            throw new \InvalidArgumentException('Items are not iterable.');
        }
        return new static(function () use ($items) {
            foreach ($this->input as $key => $value) {
                yield $key => $value;
            }
            foreach ($items as $key => $value) {
                yield $key => $value;
            }
        });
    }

    /**
     * @param mixed $value
     * @param boolean $strict
     * @return boolean
     */
    public function contains($value, $strict = false)
    {
        if ($strict) {
            foreach ($this->input as $collectionValue) {
                if ($collectionValue === $value) {
                    return true;
                }
            }
        } else {
            foreach ($this->input as $collectionValue) {
                if ($collectionValue == $value) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param callable $callback
     * @return boolean
     */
    public function every(callable $callback)
    {
        foreach ($this->input as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this->input as $key => $value) {
                if (!$callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * @return static
     */
    public function filterNumeric()
    {
        return $this->filter(function ($value) {
            return is_int($value) || is_float($value);
        });
    }

    /**
     * @return static
     */
    public function flip()
    {
        return new static(function () {
            foreach ($this->input as $key => $value) {
                yield $value => $key;
            }
        });
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        foreach ($this->input as $value) {
            return false;
        }
        return true;
    }

    /**
     * @return boolean
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * @return static
     */
    public function keys()
    {
        return new static(function () {
            foreach ($this->input as $key => $value) {
                yield $key;
            }
        });
    }

    /**
     * @param integer $amount
     * @return static
     */
    public function limit($amount)
    {
        if (!is_int($amount) || $amount < 0) {
            throw new \InvalidArgumentException('Limit amount must be a positive integer.');
        }
        return $this->slice(0, $amount);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback)
    {
        return new static(function () use ($callback) {
            foreach ($this->input as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    /**
     * @param boolean $strict
     * @return float|integer
     */
    public function max($strict = false)
    {
        $max = PHP_INT_MIN;
        foreach ($this->input as $value) {
            if (is_int($value) || is_float($value)) {
                $max = max($max, $value);
                continue;
            }
            if ($strict) {
                throw new \InvalidArgumentException('Collection contains a non-float value.');
            }
        }
        return $max;
    }

    /**
     * @param boolean $strict
     * @return integer|float
     */
    public function min($strict = false)
    {
        $min = PHP_INT_MAX;
        foreach ($this->input as $value) {
            if (is_int($value) || is_float($value)) {
                $min = min($min, $value);
                continue;
            }
            if ($strict) {
                throw new \InvalidArgumentException('Collection contains a non-float value.');
            }
        }
        return $min;
    }

    /**
     * @param mixed $value
     * @param scalar $key
     * @return static
     */
    public function prepend($value, $key = null)
    {
        return new static(function () use ($value, $key) {
            if ($key !== null) {
                yield $key => $value;
            } else {
                yield $value;
            }
            foreach ($this->input as $key => $value) {
                yield $key => $value;
            }
        });
    }

    /**
     * @param callable $callback
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        foreach ($this->input as $key => $value) {
            $initial = $callback($initial, $value);
        }
        return $initial;
    }

    /**
     * @param mixed $search
     * @param mixed $replace
     * @param boolean $strict
     * @return static
     */
    public function replace($search, $replace, $strict = false)
    {
        return new static(function () use ($search, $replace, $strict) {
            if ($strict) {
                foreach ($this->input as $value) {
                    if ($value === $search) {
                        yield $replace;
                    } else {
                        yield $value;
                    }
                }
            } else {
                foreach ($this->input as $value) {
                    if ($value === $search) {
                        yield $replace;
                    } else {
                        yield $value;
                    }
                }
            }
        });
    }

    /**
     * @return integer
     */
    public function size()
    {
        $size = 0;
        foreach ($this->input as $value) {
            $size++;
        }
        return $size;
    }

    /**
     * @param integer $start
     * @param integer $end
     * @return static
     */
    public function slice($start, $end)
    {
        if (!is_int($start) || $start < 0) {
            throw new \InvalidArgumentException('Start position is not a positive integer.');
        }
        if (!is_int($end) || $end < $start) {
            throw new \InvalidArgumentException('End position is not an integer position after start position.');
        }
        return new static(function () use ($start, $end) {
            $step = -1;
            foreach ($this->input as $key => $value) {
                $step++;
                if ($step >= $end) {
                    break;
                }
                if ($step >= $start) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * @param callable $callback
     * @return boolean
     */
    public function some(callable $callback)
    {
        foreach ($this->input as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param boolean $strict
     * @return float|integer
     */
    public function sum($strict = false)
    {
        $total = 0.0;
        foreach ($this->input as $value) {
            if (is_int($value) || is_float($value)) {
                $total += $value;
                continue;
            }
            if ($strict) {
                throw new \InvalidArgumentException('Collection contains non-numeric value type (must be integer or float).');
            }
        }
        return $total;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array($this->input);
    }
}
