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
use Mustache\Exception\RuntimeException;
use Mustache\Test\TestCase;

class OutputCoercionTest extends TestCase
{
    /**
     * @dataProvider defaultOutputCoercionData
     */
    public function testDefaultOutputCoercion($template, $context, $expected)
    {
        $mustache = new Engine();

        $this->assertSame($expected, $mustache->render($template, $context));
    }

    public function defaultOutputCoercionData()
    {
        return [
            'escaped true' => [
                '{{ value }}',
                ['value' => true],
                '1',
            ],
            'escaped false' => [
                '{{ value }}',
                ['value' => false],
                '',
            ],
            'unescaped true' => [
                '{{{ value }}}',
                ['value' => true],
                '1',
            ],
            'unescaped false' => [
                '{{{ value }}}',
                ['value' => false],
                '',
            ],
            'escaped null' => [
                '{{ value }}',
                ['value' => null],
                '',
            ],
            'unescaped null' => [
                '{{{ value }}}',
                ['value' => null],
                '',
            ],
            'escaped stringable object' => [
                '{{ value }}',
                ['value' => new StringableValue('<ok>')],
                '&lt;ok&gt;',
            ],
            'unescaped stringable object' => [
                '{{{ value }}}',
                ['value' => new StringableValue('<ok>')],
                '<ok>',
            ],
            'filter returning stringable object' => [
                '{{% FILTERS }}{{ value | stringable_value }}',
                [
                    'value' => 'ignored',
                    'stringable_value' => function () {
                        return new StringableValue('<ok>');
                    },
                ],
                '&lt;ok&gt;',
            ],
            'lambda returning stringable object' => [
                '{{ value }}',
                [
                    'value' => function () {
                        return new StringableValue('<ok>');
                    },
                ],
                '&lt;ok&gt;',
            ],
        ];
    }

    /**
     * @dataProvider nonStringableOutputData
     */
    public function testNonStringableOutputThrowsExplicitException($template, $context, $type)
    {
        $mustache = new Engine();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot render non-stringable value of type ' . $type);

        $mustache->render($template, $context);
    }

    public function nonStringableOutputData()
    {
        return [
            'escaped array' => [
                '{{ value }}',
                ['value' => ['one']],
                'array',
            ],
            'unescaped array' => [
                '{{{ value }}}',
                ['value' => ['one']],
                'array',
            ],
            'current context array' => [
                '{{ . }}',
                ['value' => 'one'],
                'array',
            ],
            'current context object' => [
                '{{ . }}',
                new \StdClass(),
                'stdClass',
            ],
            'filter returning array' => [
                '{{% FILTERS }}{{ value | array_value }}',
                [
                    'value' => 'ignored',
                    'array_value' => function () {
                        return ['one'];
                    },
                ],
                'array',
            ],
            'lambda returning array' => [
                '{{ value }}',
                [
                    'value' => function () {
                        return ['one'];
                    },
                ],
                'array',
            ],
        ];
    }

    public function testResourceOutputCoercion()
    {
        $resource = fopen('php://memory', 'r');
        $mustache = new Engine();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Cannot render non-stringable value of type resource');

            $mustache->render('{{ value }}|{{{ value }}}', ['value' => $resource]);
        } finally {
            fclose($resource);
        }
    }

    public function testCustomEscapeCanCoerceValues()
    {
        $mustache = new Engine([
            'escape' => function ($value) {
                if (is_array($value)) {
                    return 'array';
                }

                return $value;
            },
        ]);

        $this->assertSame('array', $mustache->render('{{ value }}', ['value' => ['one']]));
    }

    public function testCustomEscapeReturnValueMustBeStringable()
    {
        $mustache = new Engine([
            'escape' => function () {
                return ['one'];
            },
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot render non-stringable value of type array');

        $mustache->render('{{ value }}', ['value' => 'ignored']);
    }

    /**
     * @dataProvider lenientOutputCoercionData
     */
    public function testLenientOutputCoercion($template, $context)
    {
        $mustache = new Engine(['strict_tags' => false]);

        $this->assertSame('', $mustache->render($template, $context));
    }

    public function lenientOutputCoercionData()
    {
        return [
            'escaped array' => [
                '{{ value }}',
                ['value' => ['one']],
            ],
            'unescaped array' => [
                '{{{ value }}}',
                ['value' => ['one']],
            ],
            'current context array' => [
                '{{ . }}',
                ['value' => 'one'],
            ],
            'current context object' => [
                '{{ . }}',
                new \StdClass(),
            ],
            'filter returning array' => [
                '{{% FILTERS }}{{ value | array_value }}',
                [
                    'value' => 'ignored',
                    'array_value' => function () {
                        return ['one'];
                    },
                ],
            ],
            'lambda returning array' => [
                '{{ value }}',
                [
                    'value' => function () {
                        return ['one'];
                    },
                ],
            ],
        ];
    }

    public function testLenientOutputCoercionForCustomEscapeReturnValue()
    {
        $mustache = new Engine([
            'strict_tags' => false,
            'escape' => function () {
                return ['one'];
            },
        ]);

        $this->assertSame('', $mustache->render('{{ value }}', ['value' => 'ignored']));
    }
}

class StringableValue
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
