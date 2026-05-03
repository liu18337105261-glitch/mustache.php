<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Exception;

use Mustache\Exception\UnknownFilterException;
use Mustache\Test\TestCase;

class UnknownFilterExceptionTest extends TestCase
{
    public function testInstance()
    {
        $e = new UnknownFilterException('bacon');
        $this->assertInstanceOf(\UnexpectedValueException::class, $e);
        $this->assertInstanceOf(\Mustache\Exception::class, $e);
    }

    public function testMessage()
    {
        $e = new UnknownFilterException('sausage');
        $this->assertSame('Unknown filter: sausage', $e->getMessage());
    }

    public function testGetFilterName()
    {
        $e = new UnknownFilterException('eggs');
        $this->assertSame('eggs', $e->getFilterName());
    }

    public function testPrevious()
    {
        $previous = new \Exception();
        $e = new UnknownFilterException('foo', $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
