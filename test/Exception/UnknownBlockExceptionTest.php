<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2025 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Exception;

use Mustache\Exception\UnknownBlockException;
use Mustache\Test\TestCase;

class UnknownBlockExceptionTest extends TestCase
{
    public function testInstance()
    {
        $e = new UnknownBlockException('alpha');
        $this->assertInstanceOf(\UnexpectedValueException::class, $e);
        $this->assertInstanceOf(\Mustache\Exception::class, $e);
    }

    public function testMessage()
    {
        $e = new UnknownBlockException('beta');
        $this->assertSame('Unknown block: beta', $e->getMessage());
    }

    public function testGetBlockName()
    {
        $e = new UnknownBlockException('gamma');
        $this->assertSame('gamma', $e->getBlockName());
    }

    public function testPrevious()
    {
        $previous = new \Exception();
        $e = new UnknownBlockException('foo', $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
