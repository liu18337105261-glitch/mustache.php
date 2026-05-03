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

use Mustache\Exception\UnknownVariableException;
use Mustache\Test\TestCase;

class UnknownVariableExceptionTest extends TestCase
{
    public function testInstance()
    {
        $e = new UnknownVariableException('alpha');
        $this->assertInstanceOf(\UnexpectedValueException::class, $e);
        $this->assertInstanceOf(\Mustache\Exception::class, $e);
    }

    public function testMessage()
    {
        $e = new UnknownVariableException('beta');
        $this->assertSame('Unknown variable: beta', $e->getMessage());
    }

    public function testGetVariableName()
    {
        $e = new UnknownVariableException('gamma');
        $this->assertSame('gamma', $e->getVariableName());
    }

    public function testPrevious()
    {
        $previous = new \Exception();
        $e = new UnknownVariableException('foo', $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
