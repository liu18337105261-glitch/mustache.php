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

class PartialLambdaIndentTest extends TestCase
{
    public function testLambdasInsidePartialsAreIndentedProperly()
    {
        $src = <<<'EOS'
<fieldset>
  {{> input }}
</fieldset>

EOS;
        $partial = <<<'EOS'
<input placeholder="{{# _t }}Enter your name{{/ _t }}">

EOS;

        $expected = <<<'EOS'
<fieldset>
  <input placeholder="ENTER YOUR NAME">
</fieldset>

EOS;

        $m = new Engine([
            'partials' => ['input' => $partial],
        ]);

        $tpl = $m->loadTemplate($src);

        $data = new ClassWithLambda();
        $this->assertSame($expected, $tpl->render($data));
    }

    public function testLambdaInterpolationsInsidePartialsAreIndentedProperly()
    {
        $src = <<<'EOS'
<fieldset>
  {{> input }}
</fieldset>

EOS;
        $partial = <<<'EOS'
<input placeholder="{{ placeholder }}">

EOS;

        $expected = <<<'EOS'
<fieldset>
  <input placeholder="Enter your name">
</fieldset>

EOS;

        $m = new Engine([
            'partials' => ['input' => $partial],
        ]);

        $tpl = $m->loadTemplate($src);

        $data = new ClassWithLambda();
        $this->assertSame($expected, $tpl->render($data));
    }
}

class ClassWithLambda
{
    public function _t()
    {
        return function ($val) {
            return strtoupper($val);
        };
    }

    public function placeholder()
    {
        return function () {
            return 'Enter your name';
        };
    }
}
