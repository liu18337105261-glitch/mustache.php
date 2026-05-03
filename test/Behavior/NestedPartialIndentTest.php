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

class NestedPartialIndentTest extends TestCase
{
    /**
     * @dataProvider partialsAndStuff
     */
    public function testNestedPartialsAreIndentedProperly($src, array $partials, $expected)
    {
        $m = new Engine([
            'partials' => $partials,
        ]);
        $tpl = $m->loadTemplate($src);
        $this->assertSame($expected, $tpl->render());
    }

    public function partialsAndStuff()
    {
        $partials = [
            'a' => ' {{> b }}',
            'b' => ' {{> d }}',
            'c' => ' {{> d }}{{> d }}',
            'd' => 'D!',
        ];

        return [
            [' {{> a }}', $partials, '   D!'],
            [' {{> b }}', $partials, '  D!'],
            [' {{> c }}', $partials, '  D!D!'],
        ];
    }
}
