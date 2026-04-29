<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2025 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test;

use Mustache\Compiler;
use Mustache\Exception\SyntaxException;
use Mustache\Parser;
use Mustache\Tokenizer;

class CompilerTest extends TestCase
{
    /**
     * @dataProvider getCompileValues
     */
    public function testCompile($source, array $tree, $name, $customEscaper, $entityFlags, $charset, array $expected)
    {
        $compiler = new Compiler();

        $compiled = $compiler->compile($source, $tree, $name, $customEscaper, $charset, false, $entityFlags);
        foreach ($expected as $contains) {
            $this->assertStringContainsString($contains, $compiled);
        }
    }

    public function getCompileValues()
    {
        return [
            ['', [], 'Banana', false, ENT_COMPAT, 'ISO-8859-1', [
                "\nclass Banana extends \Mustache\Template",
                'return $buffer;',
            ]],

            ['', [$this->createTextToken('TEXT')], 'Monkey', false, ENT_COMPAT, 'UTF-8', [
                "\nclass Monkey extends \Mustache\Template",
                '$buffer .= $indent . \'TEXT\';',
                'return $buffer;',
            ]],

            [
                '',
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                'Monkey',
                true,
                ENT_COMPAT,
                'ISO-8859-1',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= $indent . ($value === null ? \'\' : call_user_func($this->mustache->getEscape(), $value));',
                    'return $buffer;',
                ],
            ],

            [
                '',
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                'Monkey',
                false,
                ENT_COMPAT,
                'ISO-8859-1',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= $indent . ($value === null ? \'\' : htmlspecialchars($value, ' . ENT_COMPAT . ', \'ISO-8859-1\'));',
                    'return $buffer;',
                ],
            ],

            [
                '',
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                'Monkey',
                false,
                ENT_QUOTES,
                'ISO-8859-1',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= $indent . ($value === null ? \'\' : htmlspecialchars($value, ' . ENT_QUOTES . ', \'ISO-8859-1\'));',
                    'return $buffer;',
                ],
            ],

            [
                '',
                [
                    $this->createTextToken("foo\n"),
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => '.',
                    ],
                    $this->createTextToken("'bar'"),
                ],
                'Monkey',
                false,
                ENT_COMPAT,
                'UTF-8',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    "\$buffer .= \$indent . 'foo\n';",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= ($value === null ? \'\' : htmlspecialchars($value, ' . ENT_COMPAT . ', \'UTF-8\'));',
                    '$value = $this->resolveValue($context->last(), $context);',
                    '$buffer .= \'\\\'bar\\\'\';',
                    'return $buffer;',
                ],
            ],
        ];
    }

    public function testCompilerThrowsSyntaxException()
    {
        $this->expectException(SyntaxException::class);
        $compiler = new Compiler();
        $compiler->compile('', [[Tokenizer::TYPE => 'invalid']], 'SomeClass');
    }

    public function testStaticPartialsInSectionsAreLazyLoadedInsideLoop()
    {
        $compiled = $this->compileSource('{{# items }}{{> row }}{{/ items }}');

        $load = strpos($compiled, '$this->mustache->loadPartial(\'row\')');
        $loop = strpos($compiled, 'foreach ($values as $value)');

        $this->assertNotFalse($load);
        $this->assertNotFalse($loop);
        $this->assertGreaterThan($loop, $load);
    }

    public function testDynamicPartialsInSectionsAreNotCached()
    {
        $compiled = $this->compileSource('{{# items }}{{> *partial }}{{/ items }}');

        $load = strpos($compiled, '$this->mustache->loadPartial($this->resolveValue($context->find(\'partial\'), $context))');
        $loop = strpos($compiled, 'foreach ($values as $value)');

        $this->assertNotFalse($load);
        $this->assertNotFalse($loop);
        $this->assertGreaterThan($loop, $load);
    }

    public function testStaticParentsInSectionsAreLazyLoadedInsideLoop()
    {
        $compiled = $this->compileSource('{{# items }}{{< layout }}{{$ body }}{{ name }}{{/ body }}{{/ layout }}{{/ items }}');

        $load = strpos($compiled, '$this->mustache->loadPartial(\'layout\')');
        $loop = strpos($compiled, 'foreach ($values as $value)');

        $this->assertNotFalse($load);
        $this->assertNotFalse($loop);
        $this->assertGreaterThan($loop, $load);
    }

    /**
     * @param string $value
     */
    private function createTextToken($value)
    {
        return [
            Tokenizer::TYPE => Tokenizer::T_TEXT,
            Tokenizer::VALUE => $value,
        ];
    }

    /**
     * @param string $source
     *
     * @return string
     */
    private function compileSource($source)
    {
        $compiler = new Compiler();
        $tokens = (new Tokenizer())->scan($source);
        $tree = (new Parser())->parse($tokens);

        return $compiler->compile($source, $tree, 'TestTemplate');
    }
}
