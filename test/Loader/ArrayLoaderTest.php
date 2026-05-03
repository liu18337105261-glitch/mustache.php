<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Loader;

use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\ArrayLoader;
use Mustache\Test\TestCase;

class ArrayLoaderTest extends TestCase
{
    public function testConstructor()
    {
        $loader = new ArrayLoader([
            'foo' => 'bar',
        ]);

        $this->assertSame('bar', $loader->load('foo'));
    }

    public function testSetAndLoadTemplates()
    {
        $loader = new ArrayLoader([
            'foo' => 'bar',
        ]);
        $this->assertSame('bar', $loader->load('foo'));

        $loader->setTemplate('baz', 'qux');
        $this->assertSame('qux', $loader->load('baz'));

        $loader->setTemplates([
            'foo' => 'FOO',
            'baz' => 'BAZ',
        ]);
        $this->assertSame('FOO', $loader->load('foo'));
        $this->assertSame('BAZ', $loader->load('baz'));
    }

    public function testMissingTemplatesThrowExceptions()
    {
        $this->expectException(UnknownTemplateException::class);
        $loader = new ArrayLoader();
        $loader->load('not_a_real_template');
    }
}
