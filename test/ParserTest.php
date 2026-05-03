<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test;

use Mustache\Exception\SyntaxException;
use Mustache\Parser;
use Mustache\Tokenizer;

class ParserTest extends TestCase
{
    /**
     * @dataProvider getTokenSets
     */
    public function testParse(array $tokens, array $expected)
    {
        $parser = new Parser();
        $this->assertEquals($expected, $parser->parse($tokens));
    }

    public function getTokenSets()
    {
        return [
            [
                [],
                [],
            ],

            [
                [[
                    Tokenizer::TYPE  => Tokenizer::T_TEXT,
                    Tokenizer::LINE  => 0,
                    Tokenizer::VALUE => 'text',
                ]],
                [[
                    Tokenizer::TYPE  => Tokenizer::T_TEXT,
                    Tokenizer::LINE  => 0,
                    Tokenizer::VALUE => 'text',
                ]],
            ],

            [
                [[
                    Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                    Tokenizer::LINE => 0,
                    Tokenizer::NAME => 'name',
                ]],
                [[
                    Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                    Tokenizer::LINE => 0,
                    Tokenizer::NAME => 'name',
                ]],
            ],

            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'foo',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_INVERTED,
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                        Tokenizer::NAME  => 'parent',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::LINE  => 0,
                        Tokenizer::NAME  => 'name',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 456,
                        Tokenizer::NAME  => 'parent',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ],
                ],

                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'foo',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_INVERTED,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                        Tokenizer::END   => 456,
                        Tokenizer::NODES => [
                            [
                                Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                                Tokenizer::LINE => 0,
                                Tokenizer::NAME => 'name',
                            ],
                        ],
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ],
                ],
            ],

            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => '  ',
                    ],
                    [
                        Tokenizer::TYPE => Tokenizer::T_DELIM_CHANGE,
                        Tokenizer::LINE => 0,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => "  \n",
                    ],
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '[[',
                        Tokenizer::CTAG => ']]',
                        Tokenizer::LINE => 1,
                    ],
                ],
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '[[',
                        Tokenizer::CTAG => ']]',
                        Tokenizer::LINE => 1,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getInheritanceTokenSets
     */
    public function testParseWithInheritance(array $tokens, array $expected)
    {
        $parser = new Parser();
        $this->assertSame($expected, $parser->parse($tokens));
    }

    public function getInheritanceTokenSets()
    {
        return [
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_PARENT,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 8,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME  => 'bar',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 16,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'baz',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'bar',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 19,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 27,
                    ],
                ],
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_PARENT,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 8,
                        Tokenizer::END   => 27,
                        Tokenizer::NODES => [
                            [
                                Tokenizer::TYPE  => Tokenizer::T_BLOCK_ARG,
                                Tokenizer::NAME  => 'bar',
                                Tokenizer::OTAG  => '{{',
                                Tokenizer::CTAG  => '}}',
                                Tokenizer::LINE  => 0,
                                Tokenizer::INDEX => 16,
                                Tokenizer::END   => 19,
                                Tokenizer::NODES => [
                                    [
                                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                                        Tokenizer::LINE  => 0,
                                        Tokenizer::VALUE => 'baz',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '{{',
                        Tokenizer::CTAG => '}}',
                        Tokenizer::LINE => 0,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 11,
                    ],
                ],
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::END   => 11,
                        Tokenizer::NODES => [
                            [
                                Tokenizer::TYPE  => Tokenizer::T_TEXT,
                                Tokenizer::LINE  => 0,
                                Tokenizer::VALUE => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getBadParseTrees
     */
    public function testParserThrowsExceptions(array $tokens)
    {
        $this->expectException(SyntaxException::class);
        $parser = new Parser();
        $parser->parse($tokens);
    }

    public function getBadParseTrees()
    {
        return [
            // no close
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                ],
            ],

            // no close inverted
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_INVERTED,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                ],
            ],

            // no opening inverted
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                ],
            ],

            // weird nesting
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'child',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'child',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                ],
            ],
        ];
    }

    public function testParserThrowsWhenInheritanceIsDisabled()
    {
        $parser = new Parser();
        $parser->setOptions([
            'inheritance' => false,
        ]);

        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Unexpected closing tag: /foo on line 0');

        $parser->parse(
            [
                [
                    Tokenizer::TYPE => Tokenizer::T_BLOCK_VAR,
                    Tokenizer::NAME => 'foo',
                    Tokenizer::OTAG => '{{',
                    Tokenizer::CTAG => '}}',
                    Tokenizer::LINE => 0,
                ],
                [
                    Tokenizer::TYPE  => Tokenizer::T_TEXT,
                    Tokenizer::LINE  => 0,
                    Tokenizer::VALUE => 'bar',
                ],
                [
                    Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                    Tokenizer::NAME  => 'foo',
                    Tokenizer::OTAG  => '{{',
                    Tokenizer::CTAG  => '}}',
                    Tokenizer::LINE  => 0,
                    Tokenizer::INDEX => 11,
                ],
            ]
        );
    }
}
