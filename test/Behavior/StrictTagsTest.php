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
use Mustache\Exception\UnknownBlockException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Exception\UnknownVariableException;
use Mustache\Test\TestCase;

class StrictTagsTest extends TestCase
{
    /**
     * @dataProvider knownInterpolationTemplates
     */
    public function testStrictInterpolationAllowsKnownVariables($template, $expected)
    {
        $mustache = new Engine(['strict_tags' => Engine::STRICT_INTERPOLATION]);
        $context = [
            'a' => ['b' => 'ab'],
            'c' => 'c',
            'd' => 'd',
        ];

        $this->assertSame($expected, $mustache->render($template, $context));
    }

    public function knownInterpolationTemplates()
    {
        return [
            ['{{ c }}', 'c'],
            ['{{# a }}{{ b }}{{/ a }}', 'ab'],
            ['{{ a.b }}', 'ab'],
            ['{{# c }}{{ d }}{{/ c }}', 'd'],
        ];
    }

    /**
     * @dataProvider unknownInterpolationTemplates
     */
    public function testStrictInterpolationThrowsForUnknownVariables($template)
    {
        $this->expectException(UnknownVariableException::class);

        $mustache = new Engine(['strict_tags' => Engine::STRICT_INTERPOLATION]);
        $context = [
            'a' => ['b' => 1],
            'c' => 1,
            'd' => 0,
        ];

        $mustache->render($template, $context);
    }

    public function unknownInterpolationTemplates()
    {
        return [
            ['{{ e }}'],
            ['{{ .a }}'],
            ['{{ .a.b }}'],
            ['{{ a.c }}'],
            ['{{# a }}{{ missing }}{{/ a }}'],
        ];
    }

    /**
     * @dataProvider lenientSectionTemplates
     */
    public function testStrictInterpolationLeavesMissingSectionsLenient($template, $expected)
    {
        $mustache = new Engine(['strict_tags' => Engine::STRICT_INTERPOLATION]);

        $this->assertSame($expected, $mustache->render($template, [
            'a' => ['b' => 1],
            'd' => 0,
        ]));
    }

    public function lenientSectionTemplates()
    {
        return [
            ['{{# x }}{{ missing }}{{/ x }}', ''],
            ['{{^ x }}fallback{{/ x }}', 'fallback'],
            ['{{# a.x }}{{ missing }}{{/ a.x }}', ''],
            ['{{# d.x }}{{ missing }}{{/ d.x }}', ''],
        ];
    }

    /**
     * @dataProvider unknownSectionTemplates
     */
    public function testStrictSectionsThrowForUnknownVariables($template)
    {
        $this->expectException(UnknownVariableException::class);

        $mustache = new Engine(['strict_tags' => Engine::STRICT_SECTIONS]);
        $mustache->render($template, [
            'a' => ['b' => 1],
            'd' => 0,
        ]);
    }

    public function unknownSectionTemplates()
    {
        return [
            ['{{# x }}{{/ x }}'],
            ['{{^ x }}fallback{{/ x }}'],
            ['{{# a.x }}{{/ a.x }}'],
            ['{{# d.x }}{{/ d.x }}'],
        ];
    }

    public function testStrictContextFrameFastPathThrowsExceptionForUnknownVariables()
    {
        $this->expectException(UnknownVariableException::class);
        $this->expectExceptionMessage('Unknown variable: label');

        $mustache = new Engine(['strict_tags' => Engine::STRICT_INTERPOLATION]);
        $mustache->render('{{# items }}{{ name }}{{ label }}{{/ items }}', [
            'items' => [
                ['name' => 'first'],
            ],
        ]);
    }

    public function testStrictPartialsThrowForMissingStaticPartial()
    {
        $this->expectException(UnknownTemplateException::class);
        $this->expectExceptionMessage('Unknown template: missing');

        $mustache = new Engine(['strict_tags' => Engine::STRICT_PARTIALS]);
        $mustache->render('{{> missing }}');
    }

    public function testStrictPartialsThrowForMissingDynamicPartialName()
    {
        $this->expectException(UnknownVariableException::class);
        $this->expectExceptionMessage('Unknown variable: partial');

        $mustache = new Engine(['strict_tags' => Engine::STRICT_PARTIALS]);
        $mustache->render('{{> *partial }}');
    }

    public function testStrictPartialsThrowForMissingDynamicPartial()
    {
        $this->expectException(UnknownTemplateException::class);
        $this->expectExceptionMessage('Unknown template: missing');

        $mustache = new Engine(['strict_tags' => Engine::STRICT_PARTIALS]);
        $mustache->render('{{> *partial }}', ['partial' => 'missing']);
    }

    public function testStrictParentsThrowForMissingStaticParent()
    {
        $this->expectException(UnknownTemplateException::class);
        $this->expectExceptionMessage('Unknown template: missing');

        $mustache = new Engine(['strict_tags' => Engine::STRICT_PARENTS]);
        $mustache->render('{{< missing }}{{/ missing }}');
    }

    public function testStrictParentsThrowForMissingDynamicParentName()
    {
        $this->expectException(UnknownVariableException::class);
        $this->expectExceptionMessage('Unknown variable: parent');

        $mustache = new Engine(['strict_tags' => Engine::STRICT_PARENTS]);
        $mustache->render('{{< *parent }}{{/ *parent }}');
    }

    public function testStrictExtraBlocksLeaveMissingOverridesLenient()
    {
        $mustache = new Engine([
            'partials' => [
                'layout' => '{{$ title }}Default{{/ title }}',
            ],
            'strict_tags' => Engine::STRICT_ALL,
        ]);

        $this->assertSame('Default', $mustache->render('{{< layout }}{{/ layout }}'));
    }

    public function testStrictExtraBlocksThrowForUnusedOverrides()
    {
        $this->expectException(UnknownBlockException::class);
        $this->expectExceptionMessage('Unknown block: typo');

        $mustache = new Engine([
            'partials' => [
                'layout' => '{{$ title }}Default{{/ title }}',
            ],
            'strict_tags' => Engine::STRICT_ALL,
        ]);

        $mustache->render('{{< layout }}{{$ typo }}Oops{{/ typo }}{{/ layout }}');
    }

    public function testStrictExtraBlocksAllowOverridesForConditionallySkippedParentBlocks()
    {
        $mustache = new Engine([
            'partials' => [
                'layout' => '{{# show }}{{$ title }}Default{{/ title }}{{/ show }}',
            ],
            'strict_tags' => Engine::STRICT_ALL,
        ]);

        $this->assertSame('', $mustache->render('{{< layout }}{{$ title }}Override{{/ title }}{{/ layout }}', [
            'show' => false,
        ]));
    }

    public function testStrictExtraBlocksAllowOverridesAcceptedByStaticParentChain()
    {
        $mustache = new Engine([
            'partials' => [
                'base' => '{{$ title }}Default{{/ title }}',
                'layout' => '{{< base }}{{/ base }}',
            ],
            'strict_tags' => Engine::STRICT_ALL,
        ]);

        $this->assertSame('Override', $mustache->render('{{< layout }}{{$ title }}Override{{/ title }}{{/ layout }}'));
    }

    public function testStrictExtraBlocksAllowOverridesForResolvedDynamicParentBlocks()
    {
        $mustache = new Engine([
            'partials' => [
                'layout' => '{{$ title }}Default{{/ title }}',
            ],
            'strict_tags' => Engine::STRICT_ALL,
        ]);

        $this->assertSame('Override', $mustache->render('{{< *parent }}{{$ title }}Override{{/ title }}{{/ *parent }}', [
            'parent' => 'layout',
        ]));
    }

    public function testStrictExtraBlocksThrowForResolvedDynamicParentWithoutBlock()
    {
        $this->expectException(UnknownBlockException::class);
        $this->expectExceptionMessage('Unknown block: title');

        $mustache = new Engine([
            'partials' => [
                'layout' => 'No blocks',
            ],
            'strict_tags' => Engine::STRICT_ALL,
        ]);

        $mustache->render('{{< *parent }}{{$ title }}Override{{/ title }}{{/ *parent }}', [
            'parent' => 'layout',
        ]);
    }

    public function testStrictExtraBlocksThrowForResolvedDynamicParentInStaticParentChain()
    {
        $this->expectException(UnknownBlockException::class);
        $this->expectExceptionMessage('Unknown block: title');

        $mustache = new Engine([
            'partials' => [
                'wrapper' => '{{< *parent }}{{/ *parent }}',
                'layout' => 'No blocks',
            ],
            'strict_tags' => Engine::STRICT_ALL,
        ]);

        $mustache->render('{{< wrapper }}{{$ title }}Override{{/ title }}{{/ wrapper }}', [
            'parent' => 'layout',
        ]);
    }

    public function testStrictExtraBlocksDeferValidationForConditionallySkippedParents()
    {
        $mustache = new Engine([
            'partials' => [
                'wrapper' => '{{# show }}{{< layout }}{{/ layout }}{{/ show }}',
                'layout' => 'No blocks',
            ],
            'strict_tags' => Engine::STRICT_ALL,
        ]);

        $this->assertSame('', $mustache->render('{{< wrapper }}{{$ typo }}Override{{/ typo }}{{/ wrapper }}', [
            'show' => false,
        ]));
    }

    public function testStrictTagsTrueEnablesAllStrictTags()
    {
        $this->expectException(UnknownVariableException::class);

        $mustache = new Engine(['strict_tags' => true]);
        $mustache->render('{{ missing }}');
    }
}
