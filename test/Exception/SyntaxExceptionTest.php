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

use Mustache\Exception\SyntaxException;
use Mustache\Test\TestCase;
use Mustache\Tokenizer;

class SyntaxExceptionTest extends TestCase
{
    public function testInstance()
    {
        $e = new SyntaxException('whot', ['is' => 'this']);
        $this->assertInstanceOf(\LogicException::class, $e);
        $this->assertInstanceOf(\Mustache\Exception::class, $e);
    }

    public function testGetToken()
    {
        $token = [Tokenizer::TYPE => 'whatever'];
        $e = new SyntaxException('ignore this', $token);
        $this->assertSame($token, $e->getToken());
    }

    public function testPrevious()
    {
        $previous = new \Exception();
        $e = new SyntaxException('foo', [], $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
