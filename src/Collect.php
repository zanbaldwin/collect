<?php declare(strict_types=1);

namespace ZanderBaldwin\Collect;

class Collect
{
    private $input;

    public function __construct($input)
    {
        if (is_callable($input)) {
            $reflect = new \ReflectionFunction($input);
            if (!$reflect->isGenerator()) {
                throw new \InvalidArgumentException('Callable is not a generator.');
            }
            $this->input = call_user_func($input);
        } elseif (is_array($input)) {
            $this->input = new \RecursiveArrayIterator($input);
        } elseif ($input instanceof \IteratorAggregate) {
            $this->input = $input->getIterator();
        } elseif ($input instanceof \Iterator) {
            $this->input = $input;
        } else {
            throw new \InvalidArgumentException('Cannot use $input as collection.');
        }
    }

    public function __clone()
    {
        if (is_object($this->input)) {
            $this->input = clone $this->input;
        }
    }

    public function append($value, $key = null): Collect
    {
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

    public function apply(callable $callback): Collect
    {
        return $callback($this);
    }

    public function concat($items): Collect
    {
        return new static(function () use ($items) {
            foreach ($this->input as $key => $value) {
                yield $key => $value;
            }
            foreach ($items as $key => $value) {
                yield $key => $value;
            }
        });
    }

    public function contains($value, bool $strict = false): Collect
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

    public function every(callable $callback): bool
    {
        foreach ($this->input as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }
        return true;
    }

    public function filter(callable $callback): Collect
    {
        return new static(function () use ($callback) {
            foreach ($this->input as $key => $value) {
                if (!$callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    public function filterNumeric()
    {
        return $this->filter(function ($value) {
            return is_int($value) || is_float($value);
        });
    }

    public function flip(): Collect
    {
        return new static(function () {
            foreach ($this->input as $key => $value) {
                yield $value => $key;
            }
        });
    }

    public function isEmpty(): bool
    {
        foreach ($this->input as $value) {
            return false;
        }
        return true;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function keys(): Collect
    {
        return new static(function () {
            foreach ($this->input as $key => $value) {
                yield $key;
            }
        });
    }

    public function map(callable $callback): Collect
    {
        return new static(function () use ($callback) {
            foreach ($this->input as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    public function max(): float
    {
        $max = PHP_INT_MIN;
        foreach ($this->input as $value) {
            if (!is_int($value) && !is_float($value)) {
                throw new \InvalidArgumentException('Collection contains a non-float value.');
            }
            $max = max($max, $value);
        }
        return $max;
    }

    public function min(): float
    {
        $min = PHP_INT_MAX;
        foreach ($this->input as $value) {
            if (!is_int($value) && !is_float($value)) {
                throw new \InvalidArgumentException('Collection contains a non-float value.');
            }
            $min = min($min, $value);
        }
        return $min;
    }

    public function prepend($value, $key = null): Collect
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

    public function reduce(callable $callback, $initial = null)
    {
        foreach ($this->input as $key => $value) {
            $initial = $callback($initial, $value);
        }
        return $initial;
    }

    public function replace($search, $replace, bool $strict = false): Collect
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

    public function size(): int
    {
        $size = 0;
        foreach ($this->input as $value) {
            $size++;
        }
        return $size;
    }

    public function slice(int $start, int $end): Collect
    {
        return new static(function () use ($start, $end) {
            $step = -1;
            foreach ($this->input as $key => $value) {
                $step++;
                if ($step >= $start) {
                    yield $key => $value;
                } elseif ($step >= $end) {
                    break;
                }
            }
        });
    }

    public function some(callable $callback): bool
    {
        foreach ($this->input as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }
        return false;
    }

    public function sum(): float
    {
        $total = 0;
        foreach ($this->input as $value) {
            if (!is_int($value) && !is_float($value)) {
                throw new \InvalidArgumentException('Collection contains non-float value.');
            }
            $total += $value;
        }
        return $total;
    }

    public function take(int $amount): Collect
    {
        return $this->slice(0, $amount);
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this->input as $key => $value) {
            $array[$key] = $value;
        }
        return $array;
    }
}
