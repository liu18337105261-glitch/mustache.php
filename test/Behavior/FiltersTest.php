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

use InvalidArgumentException;
use Mustache\Engine;
use Mustache\Exception\UnknownFilterException;
use Mustache\Test\TestCase;

class FiltersTest extends TestCase
{
    private $mustache;

    public function set_up()
    {
        $this->mustache = new Engine();
    }

    /**
     * @dataProvider singleFilterData
     */
    public function testSingleFilter($tpl, array $helpers, $data, $expect)
    {
        $this->mustache->setHelpers($helpers);
        $this->assertSame($expect, $this->mustache->render($tpl, $data));
    }

    public function singleFilterData()
    {
        $helpers = [
            'longdate' => function (\DateTime $value) {
                return $value->format('Y-m-d h:m:s');
            },
            'echo' => function ($value) {
                return [$value, $value, $value];
            },
        ];

        return [
            [
                '{{% FILTERS }}{{ date | longdate }}',
                $helpers,
                (object) ['date' => new \DateTime('1/1/2000', new \DateTimeZone('UTC'))],
                '2000-01-01 12:01:00',
            ],

            [
                '{{% FILTERS }}{{# word | echo }}{{ . }}!{{/ word | echo }}',
                $helpers,
                ['word' => 'bacon'],
                'bacon!bacon!bacon!',
            ],
        ];
    }

    public function testChainedFilters()
    {
        $tpl = $this->mustache->loadTemplate('{{% FILTERS }}{{ date | longdate | withbrackets }}');

        $this->mustache->addHelper('longdate', function (\DateTime $value) {
            return $value->format('Y-m-d h:m:s');
        });

        $this->mustache->addHelper('withbrackets', function ($value) {
            return sprintf('[[%s]]', $value);
        });

        $foo = new \StdClass();
        $foo->date = new \DateTime('1/1/2000', new \DateTimeZone('UTC'));

        $this->assertSame('[[2000-01-01 12:01:00]]', $tpl->render($foo));
    }

    const CHAINED_SECTION_FILTERS_TPL = <<<'EOS'
{{% FILTERS }}
{{# word | echo | with_index }}
{{ key }}: {{ value }}
{{/ word | echo | with_index }}
EOS;

    public function testChainedSectionFilters()
    {
        $tpl = $this->mustache->loadTemplate(self::CHAINED_SECTION_FILTERS_TPL);

        $this->mustache->addHelper('echo', function ($value) {
            return [$value, $value, $value];
        });

        $this->mustache->addHelper('with_index', function ($value) {
            return array_map(function ($k, $v) {
                return [
                    'key'   => $k,
                    'value' => $v,
                ];
            }, array_keys($value), $value);
        });

        $this->assertSame("0: bacon\n1: bacon\n2: bacon\n", $tpl->render(['word' => 'bacon']));
    }

    /**
     * @dataProvider interpolateFirstData
     */
    public function testInterpolateFirst($tpl, array $data, $expect)
    {
        $this->assertSame($expect, $this->mustache->render($tpl, $data));
    }

    public function interpolateFirstData()
    {
        $data = [
            'foo' => 'FOO',
            'bar' => function ($value) {
                return ($value === 'FOO') ? 'win!' : 'fail :(';
            },
        ];

        return [
            ['{{% FILTERS }}{{ foo | bar }}',                         $data, 'win!'],
            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}', $data, 'win!'],
        ];
    }

    /**
     * @dataProvider brokenPipeData
     */
    public function testThrowsExceptionForBrokenPipes($tpl, array $data)
    {
        $this->expectException(UnknownFilterException::class);
        $this->mustache->render($tpl, $data);
    }

    public function brokenPipeData()
    {
        return [
            ['{{% FILTERS }}{{ foo | bar }}',       []],
            ['{{% FILTERS }}{{ foo | bar }}',       ['foo' => 'FOO']],
            ['{{% FILTERS }}{{ foo | bar }}',       ['foo' => 'FOO', 'bar' => 'BAR']],
            ['{{% FILTERS }}{{ foo | bar }}',       ['foo' => 'FOO', 'bar' => [1, 2]]],
            ['{{% FILTERS }}{{ foo | bar | baz }}', ['foo' => 'FOO', 'bar' => function () {
                return 'BAR';
            }]],
            ['{{% FILTERS }}{{ foo | bar | baz }}', ['foo' => 'FOO', 'baz' => function () {
                return 'BAZ';
            }]],
            ['{{% FILTERS }}{{ foo | bar | baz }}', ['bar' => function () {
                return 'BAR';
            }]],
            ['{{% FILTERS }}{{ foo | bar | baz }}', ['baz' => function () {
                return 'BAZ';
            }]],
            ['{{% FILTERS }}{{ foo | bar.baz }}',   ['foo' => 'FOO', 'bar' => function () {
                return 'BAR';
            }, 'baz' => function () {
                return 'BAZ';
            }]],

            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             []],
            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             ['foo' => 'FOO']],
            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             ['foo' => 'FOO', 'bar' => 'BAR']],
            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             ['foo' => 'FOO', 'bar' => [1, 2]]],
            ['{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}', ['foo' => 'FOO', 'bar' => function () {
                return 'BAR';
            }]],
            ['{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}', ['foo' => 'FOO', 'baz' => function () {
                return 'BAZ';
            }]],
            ['{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}', ['bar' => function () {
                return 'BAR';
            }]],
            ['{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}', ['baz' => function () {
                return 'BAZ';
            }]],
            ['{{% FILTERS }}{{# foo | bar.baz }}{{ . }}{{/ foo | bar.baz }}',     ['foo' => 'FOO', 'bar' => function () {
                return 'BAR';
            }, 'baz' => function () {
                return 'BAZ';
            }]],
        ];
    }

    /**
     * @dataProvider lambdaFiltersData
     */
    public function testLambdaFilters($tpl, array $data, $expect)
    {
        $this->assertSame($expect, $this->mustache->render($tpl, $data));
    }

    public function lambdaFiltersData()
    {
        $people = [
            (object) ['name' => 'Albert'],
            (object) ['name' => 'Betty'],
            (object) ['name' => 'Charles'],
        ];

        $data = [
            'noop' => function ($value) {
                return $value;
            },
            'people' => $people,
            'people_lambda' => function () use ($people) {
                return $people;
            },
            'first_name' => function ($arr) {
                return $arr[0]->name;
            },
            'last_name' => function ($arr) {
                $last = end($arr);

                return $last->name;
            },
            'all_names' => function ($arr) {
                return implode(', ', array_map(function ($person) {
                    return $person->name;
                }, $arr));
            },
            'first_person' => function ($arr) {
                return $arr[0];
            },
        ];

        return [
            ['{{% FILTERS }}{{ people | first_name }}', $data, 'Albert'],
            ['{{% FILTERS }}{{ people | last_name }}', $data, 'Charles'],
            ['{{% FILTERS }}{{ people | all_names }}', $data, 'Albert, Betty, Charles'],
            ['{{% FILTERS }}{{# people | first_person }}{{ name }}{{/ people }}', $data, 'Albert'],
            ['{{% FILTERS }}{{# people_lambda | first_person }}{{ name }}{{/ people_lambda }}', $data, 'Albert'],
            ['{{% FILTERS }}{{# people_lambda | noop | first_person }}{{ name }}{{/ people_lambda }}', $data, 'Albert'],
        ];
    }

    public function testFiltersWithoutLambdasThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FILTERS pragma requires lambda support');
        $mustache = new Engine([
            'lambdas' => false,
        ]);
        $mustache->render('{{% FILTERS }}wheee', []);
    }
}
