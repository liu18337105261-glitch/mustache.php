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

use Mustache\Loader\StringLoader;
use Mustache\Test\TestCase;

class StringLoaderTest extends TestCase
{
    public function testLoadTemplates()
    {
        $loader = new StringLoader();

        $this->assertSame('foo', $loader->load('foo'));
        $this->assertSame('{{ bar }}', $loader->load('{{ bar }}'));
        $this->assertSame("\n{{! comment }}\n", $loader->load("\n{{! comment }}\n"));
    }
}
