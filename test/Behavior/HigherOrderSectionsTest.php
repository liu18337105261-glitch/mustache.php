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
use Mustache\Test\FunctionalTestCase;

class HigherOrderSectionsTest extends FunctionalTestCase
{
    private $mustache;

    public function set_up()
    {
        $this->mustache = new Engine();
    }

    /**
     * @dataProvider sectionCallbackData
     */
    public function testSectionCallback($data, $tpl, $expect)
    {
        $this->assertSame($expect, $this->mustache->render($tpl, $data));
    }

    public function sectionCallbackData()
    {
        $foo = new Foo();
        $foo->doublewrap = function ($text) use ($foo) {
            return $foo->wrapWithBoth($text);
        };

        $bar = new Foo();
        $bar->trimmer = function ($text) use ($bar) {
            return $bar::staticTrim($text);
        };

        return [
            [$foo, '{{#doublewrap}}{{name}}{{/doublewrap}}', sprintf('<strong><em>%s</em></strong>', $foo->name)],
            [$bar, '{{#trimmer}}   {{name}}   {{/trimmer}}', $bar->name],
        ];
    }

    public function testViewArraySectionCallback()
    {
        $tpl = $this->mustache->loadTemplate('{{#trim}}    {{name}}    {{/trim}}');

        $foo = new Foo();

        $data = [
            'name' => 'Bob',
            'trim' => function ($text) use ($foo) {
                return $foo::staticTrim($text);
            },
        ];

        $this->assertSame($data['name'], $tpl->render($data));
    }

    public function testMonsters()
    {
        $tpl = $this->mustache->loadTemplate('{{#title}}{{title}} {{/title}}{{name}}');

        $frank = new Monster();
        $frank->title = 'Dr.';
        $frank->name  = 'Frankenstein';
        $this->assertSame('Dr. Frankenstein', $tpl->render($frank));

        $dracula = new Monster();
        $dracula->title = 'Count';
        $dracula->name  = 'Dracula';
        $this->assertSame('Count Dracula', $tpl->render($dracula));
    }

    public function testPassthroughOptimization()
    {
        $mustache = $this->getMockBuilder(Engine::class);
        if (method_exists($mustache, 'onlyMethods')) {
            $mustache->onlyMethods(['loadLambda']);
        } else {
            $mustache->setMethods(['loadLambda']);
        }
        $mustache = $mustache->getMock();

        $mustache->expects($this->never())
            ->method('loadLambda');

        $tpl = $mustache->loadTemplate('{{#wrap}}NAME{{/wrap}}');

        $foo = new Foo();
        $foo->wrap = function ($text) use ($foo) {
            return $foo->wrapWithEm($text);
        };

        $this->assertSame('<em>NAME</em>', $tpl->render($foo));
    }

    public function testWithoutPassthroughOptimization()
    {
        $mustache = $this->getMockBuilder(Engine::class);
        if (method_exists($mustache, 'onlyMethods')) {
            $mustache->onlyMethods(['loadLambda']);
        } else {
            $mustache->setMethods(['loadLambda']);
        }
        $mustache = $mustache->getMock();

        $mustache->expects($this->once())
            ->method('loadLambda')
            ->willReturn($mustache->loadTemplate('<em>{{ name }}</em>'));

        $tpl = $mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

        $foo = new Foo();
        $foo->wrap = function ($text) use ($foo) {
            return $foo->wrapWithEm($text);
        };

        $this->assertSame('<em>' . $foo->name . '</em>', $tpl->render($foo));
    }

    public function testPassthroughOptimizationHonorsCustomDelimiters()
    {
        $tpl = $this->mustache->loadTemplate('{{=<% %>=}}<%#wrap%><%name%><%/wrap%>');

        $foo = new Foo();
        $foo->wrap = function () {
            return '<%name%>';
        };

        $this->assertSame($foo->name, $tpl->render($foo));
    }

    /**
     * @dataProvider cacheLambdaTemplatesData
     */
    public function testCacheLambdaTemplatesOptionWorks($dirName, $tplPrefix, $enable, $expect)
    {
        $cacheDir = $this->setUpCacheDir($dirName);
        $mustache = new Engine([
            'template_class_prefix'  => $tplPrefix,
            'cache'                  => $cacheDir,
            'cache_lambda_templates' => $enable,
        ]);

        $tpl = $mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');
        $foo = new Foo();
        $foo->wrap = function ($text) use ($foo) {
            return $foo->wrapWithEm($text);
        };
        $this->assertSame('<em>' . $foo->name . '</em>', $tpl->render($foo));
        $this->assertCount($expect, glob($cacheDir . '/*.php'));
    }

    public function cacheLambdaTemplatesData()
    {
        return [
            ['test_enabling_lambda_cache',  '_TestEnablingLambdaCache_',  true,  2],
            ['test_disabling_lambda_cache', '_TestDisablingLambdaCache_', false, 1],
        ];
    }

    protected function setUpCacheDir($name)
    {
        $cacheDir = self::$tempDir . '/' . $name;
        if (file_exists($cacheDir)) {
            self::rmdir($cacheDir);
        }
        mkdir($cacheDir, 0777, true);

        return $cacheDir;
    }

    public function testAnonymousFunctionSectionCallback()
    {
        $tpl = $this->mustache->loadTemplate('{{#wrapper}}{{name}}{{/wrapper}}');

        $foo = new Bar();
        $foo->name = 'Mario';
        $foo->wrapper = function ($text) {
            return sprintf('<div class="anonymous">%s</div>', $text);
        };

        $this->assertSame(sprintf('<div class="anonymous">%s</div>', $foo->name), $tpl->render($foo));
    }

    public function testAnonymousSectionCallback()
    {
        $one = $this->mustache->loadTemplate('{{name}}');
        $two = $this->mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

        $foo = new Bar();
        $foo->name = 'Luigi';

        $this->assertSame($foo->name, $one->render($foo));
        $this->assertSame(sprintf('<em>%s</em>', $foo->name), $two->render($foo));
    }

    public function testViewArrayAnonymousSectionCallback()
    {
        $tpl = $this->mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

        $data = [
            'name' => 'Bob',
            'wrap' => function ($text) {
                return sprintf('[[%s]]', $text);
            },
        ];

        $this->assertSame(sprintf('[[%s]]', $data['name']), $tpl->render($data));
    }

    /**
     * @dataProvider nonTemplateLambdasData
     */
    public function testNonTemplateLambdas($tpl, array $data, $expect)
    {
        $this->assertSame($expect, $this->mustache->render($tpl, $data));
    }

    public function nonTemplateLambdasData()
    {
        $data = [
            'lang' => 'en-US',
            'people' => function () {
                return [
                    (object) ['name' => 'Albert', 'lang' => 'en-GB'],
                    (object) ['name' => 'Betty'],
                    (object) ['name' => 'Charles'],
                ];
            },
        ];

        return [
            ["{{# people }} - {{ name }}\n{{/people}}", $data, " - Albert\n - Betty\n - Charles\n"],
            ["{{# people }} - {{ name }}: {{ lang }}\n{{/people}}", $data, " - Albert: en-GB\n - Betty: en-US\n - Charles: en-US\n"],
        ];
    }

    public function testWithLambdasDisabled()
    {
        $mustache = new Engine([
            'lambdas' => false,
            'strict_callables' => false, // strict callables disabled to make testing easier
        ]);

        $tpl = $mustache->loadTemplate('{{#data}}[{{.}}]{{/data}}');

        $this->assertSame(sprintf('[%s][getData]', Baz::class), $tpl->render([
            'data' => [Baz::class, 'getData'],
        ]));
    }

    public function testPreventRender()
    {
        $this->mustache->setHelpers([
            'wrap' => function ($text, $lambda) {
                return $lambda->preventRender('{{ mustache }}');
            },
            'mustache' => 'FAIL',
        ]);

        $tpl = $this->mustache->loadTemplate('{{# wrap }}{{ name }}{{/ wrap }}');

        $this->assertSame(
            '{{ mustache }}',
            $tpl->render(['name' => '{{ mustache }}'])
        );
    }

    public function testImplicitPreventRender()
    {
        $this->mustache->setHelpers([
            'wrap' => function ($text, $lambda) {
                return $lambda->render($text);
            },
            'mustache' => 'FAIL',
        ]);

        $tpl = $this->mustache->loadTemplate('{{# wrap }}{{ name }}{{/ wrap }}');

        $this->assertSame(
            '{{ mustache }}',
            $tpl->render(['name' => '{{ mustache }}'])
        );
    }

    public function testAllowDoubleRender()
    {
        $mustache = new Engine([
            'double_render_lambdas' => true,
            'helpers' => [
                'wrap' => function ($text, $lambda) {
                    return $lambda->render($text);
                },
                'mustache' => 'PASS',
            ],
        ]);

        $tpl = $mustache->loadTemplate('{{# wrap }}{{ name }}{{/ wrap }}');

        $this->assertSame(
            'PASS',
            'PASS',
            $tpl->render(['name' => '{{ mustache }}'])
        );
    }

    public function testPreventRenderWithAllowedDoubleRender()
    {
        $mustache = new Engine([
            'double_render_lambdas' => true,
            'helpers' => [
                'wrap' => function ($text, $lambda) {
                    return $lambda->preventRender($lambda->render($text));
                },
                'mustache' => 'FAIL',
            ],
        ]);

        $tpl = $mustache->loadTemplate('{{# wrap }}{{ name }}{{/ wrap }}');

        $this->assertSame(
            '{{ mustache }}',
            $tpl->render(['name' => '{{ mustache }}'])
        );
    }
}

class Foo
{
    public $name = 'Justin';
    public $lorem = 'Lorem ipsum dolor sit amet,';

    public $wrap;
    public $doublewrap;
    public $trimmer;

    public function wrapWithEm($text)
    {
        return sprintf('<em>%s</em>', $text);
    }

    /**
     * @param string $text
     */
    public function wrapWithStrong($text)
    {
        return sprintf('<strong>%s</strong>', $text);
    }

    public function wrapWithBoth($text)
    {
        return self::wrapWithStrong(self::wrapWithEm($text));
    }

    public static function staticTrim($text)
    {
        return trim($text);
    }
}

class Bar
{
    public $name  = 'Justin';
    public $lorem = 'Lorem ipsum dolor sit amet,';
    public $wrap;
    public $wrapper;

    public function __construct()
    {
        $this->wrap = function ($text) {
            return sprintf('<em>%s</em>', $text);
        };
    }
}

class Baz
{
    public static function getData()
    {
        return ['foo', 'bar', 'baz'];
    }
}

class Monster
{
    public $title;
    public $name;
}
