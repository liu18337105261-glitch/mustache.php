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
use Mustache\Loader\ArrayLoader;
use Mustache\Test\TestCase;

class MustacheInjectionTest extends TestCase
{
    const INJECTION_PAYLOAD = "x'); \\Mustache\\Test\\Behavior\\CompilerInjectionProbe::mark(); //";

    private $mustache;

    public function set_up()
    {
        $this->mustache = new Engine();
        CompilerInjectionProbe::reset();
    }

    /**
     * @dataProvider injectionData
     */
    public function testInjection($tpl, array $data, array $partials, $expect)
    {
        $this->mustache->setPartials($partials);
        $this->assertSame($expect, $this->mustache->render($tpl, $data));
    }

    public function injectionData()
    {
        $interpolationData = [
            'a' => '{{ b }}',
            'b' => 'FAIL',
        ];

        $sectionData = [
            'a' => true,
            'b' => '{{ c }}',
            'c' => 'FAIL',
        ];

        $lambdaInterpolationData = [
            'a' => function () {
                return '{{ b }}';
            },
            'b' => '{{ c }}',
            'c' => 'FAIL',
        ];

        $lambdaSectionData = [
            'a' => function ($text) {
                return "{{ $text }}";
            },
            'b' => '{{ c }}',
            'c' => 'FAIL',
        ];

        return [
            ['{{ a }}',   $interpolationData, [], '{{ b }}'],
            ['{{{ a }}}', $interpolationData, [], '{{ b }}'],

            ['{{# a }}{{ b }}{{/ a }}',   $sectionData, [], '{{ c }}'],
            ['{{# a }}{{{ b }}}{{/ a }}', $sectionData, [], '{{ c }}'],

            ['{{> partial }}', $interpolationData, ['partial' => '{{ a }}'],   '{{ b }}'],
            ['{{> partial }}', $interpolationData, ['partial' => '{{{ a }}}'], '{{ b }}'],

            ['{{ a }}',           $lambdaInterpolationData, [], '{{ c }}'],
            ['{{# a }}b{{/ a }}', $lambdaSectionData,       [], '{{ c }}'],
        ];
    }

    public function testTemplateControlledPartialNameCannotInjectCompiledPhp()
    {
        $name = self::INJECTION_PAYLOAD;
        $this->mustache->setPartials([$name => 'safe']);

        $this->assertSame('safe', $this->mustache->render('{{> ' . $name . ' }}'));
        $this->assertFalse(CompilerInjectionProbe::$hit);
    }

    public function testTemplateControlledParentNameCannotInjectCompiledPhp()
    {
        $name = self::INJECTION_PAYLOAD;
        $this->mustache->setPartials([$name => 'safe']);

        $this->assertSame('safe', $this->mustache->render('{{< ' . $name . ' }}{{/ ' . $name . ' }}'));
        $this->assertFalse(CompilerInjectionProbe::$hit);
    }

    public function testTemplateSourceNameCannotInjectCompiledDebugMetadata()
    {
        $name = self::INJECTION_PAYLOAD;
        $mustache = new Engine([
            'debug_rendering' => Engine::DEBUG_RENDERING_ALWAYS,
            'loader' => new ArrayLoader([
                $name => '{{ value }}',
            ]),
        ]);

        $this->assertSame('safe', $mustache->loadTemplate($name)->render(['value' => 'safe']));
        $this->assertFalse(CompilerInjectionProbe::$hit);
    }
}

class CompilerInjectionProbe
{
    public static $hit = false;

    public static function mark()
    {
        self::$hit = true;
    }

    public static function reset()
    {
        self::$hit = false;
    }
}
