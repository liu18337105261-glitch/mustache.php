<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Behavior;

use Mustache\Engine;
use Mustache\Test\TestCase;

class CallTest extends TestCase
{
    public function testCallEatsContext()
    {
        $m = new Engine();
        $tpl = $m->loadTemplate('{{# foo }}{{ label }}: {{ name }}{{/ foo }}');

        $foo = new ClassWithCall();
        $foo->name = 'Bob';

        $data = ['label' => 'name', 'foo' => $foo];

        $this->assertSame('name: Bob', $tpl->render($data));
    }
}

class ClassWithCall
{
    public $name;

    public function __call($method, $args)
    {
        return 'unknown value';
    }
}
