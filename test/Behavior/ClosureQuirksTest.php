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

class ClosureQuirksTest extends TestCase
{
    private $mustache;

    public function set_up()
    {
        $this->mustache = new Engine();
    }

    public function testClosuresDontLikeItWhenYouTouchTheirProperties()
    {
        $tpl = $this->mustache->loadTemplate('{{ foo.bar }}');
        $this->assertSame('', $tpl->render(['foo' => function () {
            return 'FOO';
        }]));
    }
}
