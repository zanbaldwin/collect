<?php

namespace ZanderBaldwin\Collect\Tests;

use PHPUnit\Framework\TestCase;
use ZanderBaldwin\Collect\Collect;

class CollectTest extends TestCase
{
    public function testInstantiateWithArray()
    {
        $collection = new Collect([1, 2, 3]);
        $this->assertInstanceOf(Collect::class, $collection);
    }

    public function testInstantiateWithCallback()
    {
        $collection = new Collect(function () {
            foreach(range(1, 3) as $number) {
                yield $number;
            }
        });
        $this->assertInstanceOf(Collect::class, $collection);
    }

    public function testInstantiationWithGenerator()
    {
        $callback = function () {
            foreach(range(1, 3) as $number) {
                yield $number;
            }
        };
        $generator = $callback();
        $collection = new Collect($generator);
        $this->assertInstanceOf(Collect::class, $collection);
    }

    public function testAppend()
    {
        $expected = [1, 2, 3, 4];

        $collection = new Collect([1, 2, 3]);
        $collection->append(4);
        $this->assertEquals($expected, $collection->toArray());

        $collection = new Collect([1, 2, 3]);
        $collection->append(4, null);
        $this->assertEquals($expected, $collection->toArray());
    }

    public function testAppendWithKey()
    {
        $collection = new Collect([1, 2, 3]);
        $collection->append(4, 'key');
        $this->assertEquals([1, 2, 3, 'key' => 4], $collection->toArray());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAppendWithNonScalarKey()
    {
        $collection = new Collect([1, 2, 3]);
        $collection->append(4, new \stdClass);
    }

    public function testConcatCollection()
    {
        $collection = new Collect([1, 2, 3]);
        $collection->concat(new Collect([4, 5, 6]));
        $this->assertEquals([1, 2, 3, 4, 5, 6], $collection->toArray());
    }

    public function testConcatArray()
    {
        $collection = new Collect([1, 2, 3]);
        $collection->concat([4, 5, 6]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $collection->toArray());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConcatNonIterable()
    {
        $collection = new Collect([1, 2, 3]);
        $collection->concat(4);
    }
}
