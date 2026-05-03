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
use Mustache\Exception\SyntaxException;
use Mustache\Test\TestCase;

class DynamicNamesTest extends TestCase
{
    private $mustache;

    public function set_up()
    {
        $this->mustache = new Engine();
    }

    public function getValidDynamicNamesExamples()
    {
        // technically not all dynamic names, but also not invalid
        return [
            ['{{>* foo }}'],
            ['{{>* foo.bar.baz }}'],
            ['{{=* *=}}'],
            ['{{! *foo }}'],
            ['{{! foo.*bar }}'],
            ['{{% FILTERS }}{{! foo | *bar }}'],
            ['{{% BLOCKS }}{{< *foo }}{{/ *foo }}'],
            ['{{< *foo }}{{/ *foo }}'],
        ];
    }

    /**
     * @dataProvider getValidDynamicNamesExamples
     */
    public function testLegalInheritanceExamples($template)
    {
        $this->assertSame('', $this->mustache->render($template));
    }

    public function getDynamicNameParseErrors()
    {
        return [
            ['{{# foo }}{{/ *foo }}'],
            ['{{^ foo }}{{/ *foo }}'],
            ['{{% BLOCKS }}{{< foo }}{{/ *foo }}'],
            ['{{% BLOCKS }}{{$ foo }}{{/ *foo }}'],
            ['{{< foo }}{{/ *foo }}'],
            ['{{$ foo }}{{/ *foo }}'],
        ];
    }

    /**
     * @dataProvider getDynamicNameParseErrors
     */
    public function testDynamicNameParseErrors($template)
    {
        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Nesting error:');
        $this->mustache->render($template);
    }

    public function testDynamicBlocks()
    {
        $tpl = '{{< *partial }}{{$ bar }}{{ value }}{{/ bar }}{{/ *partial }}';

        $this->mustache->setPartials([
            'foobarbaz' => '{{$ foo }}foo{{/ foo }}{{$ bar }}bar{{/ bar }}{{$ baz }}baz{{/ baz }}',
            'qux' => 'qux',
        ]);

        $result = $this->mustache->render($tpl, [
            'partial' => 'foobarbaz',
            'value' => 'BAR',
        ]);

        $this->assertSame($result, 'fooBARbaz');
    }

    public function testDisabledDynamicNames()
    {
        $mustache = new Engine([
            'dynamic_names' => false,
        ]);

        $tpl = '{{> *partial }}';

        $mustache->setPartials([
            'foo' => '{{ value }}',
        ]);

        // The partial `foo` is defined, but dynamic names are disabled, so
        // `*partial` will not resolve to 'foo' and the partial will not be
        // rendered.

        $result = $mustache->render($tpl, [
            'partial' => 'foo',
            'value' => 'BAR',
        ]);

        $this->assertSame($result, '');
    }
}
