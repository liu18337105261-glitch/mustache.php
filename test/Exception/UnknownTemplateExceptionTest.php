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

use Mustache\Exception\UnknownTemplateException;
use Mustache\Test\TestCase;

class UnknownTemplateExceptionTest extends TestCase
{
    public function testInstance()
    {
        $e = new UnknownTemplateException('mario');
        $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        $this->assertInstanceOf(\Mustache\Exception::class, $e);
    }

    public function testMessage()
    {
        $e = new UnknownTemplateException('luigi');
        $this->assertSame('Unknown template: luigi', $e->getMessage());
    }

    public function testGetTemplateName()
    {
        $e = new UnknownTemplateException('yoshi');
        $this->assertSame('yoshi', $e->getTemplateName());
    }

    public function testPrevious()
    {
        $previous = new \Exception();
        $e = new UnknownTemplateException('foo', $previous);
        $this->assertSame($previous, $e->getPrevious());
    }
}
