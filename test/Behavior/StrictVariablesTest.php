<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2025 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Behavior;

use Mustache\Engine;
use Mustache\Exception\UnknownVariableException;
use Mustache\Test\TestCase;

class StrictVariablesTest extends TestCase
{
    /**
     * @dataProvider strictVariablesProvider
     */
    public function testStrictVariables($template, $expected)
    {
        $mustache = new Engine(['strict_variables' => true]);
        $context = [
            'a' => ['b' => 'ab'],
            'c' => 'c',
            'd' => 'd',
        ];

        $this->assertSame($expected, $mustache->render($template, $context));
    }

    public function strictVariablesProvider()
    {
        return [
            ['{{ c }}', 'c'],
            ['{{# a }}{{ b }}{{/ a }}', 'ab'],
            ['{{ a.b }}', 'ab'],
            ['{{# c }}{{ d }}{{/ c }}', 'd'],
            ['{{# x }}{{/ x }}', ''],
            ['{{^ x }}{{/ x }}', ''],
            ['{{# a }}{{# x }}{{/ x }}{{/ a }}', ''],
            ['{{# a.x }}{{/ a.x }}', ''],
            ['{{# d.x }}{{/ d.x }}', ''],
            ['{{^ a }}{{ x }}{{/ a }}', ''],
            ['{{# f }}{{ x }}{{/ f }}', ''],
        ];
    }

    /**
     * @dataProvider unknownVariableThrowsExceptionProvider
     */
    public function testUnknownVariableThrowsException($template)
    {
        $this->expectException(UnknownVariableException::class);

        $mustache = new Engine(['strict_variables' => true]);
        $context = [
            'a' => ['b' => 1],
            'c' => 1,
            'd' => 0,
        ];

        $mustache->render($template, $context);
    }

    public function unknownVariableThrowsExceptionProvider()
    {
        return [
            ['{{ e }}'],
            ['{{ .a }}'],
            ['{{ .a.b }}'],
            ['{{ a.c }}'],
        ];
    }
}
