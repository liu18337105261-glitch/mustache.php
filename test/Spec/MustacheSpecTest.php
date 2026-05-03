<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Spec;

use Mustache\Test\SpecTestCase;

/**
 * A PHPUnit test case wrapping the Mustache Spec.
 *
 * @group spec
 */
class MustacheSpecTest extends SpecTestCase
{
    /**
     * For some reason data providers can't mark tests skipped, so this test exists
     * simply to provide a 'skipped' test if the `spec` submodule isn't initialized.
     */
    public function testSpecInitialized()
    {
        if (!file_exists(__DIR__ . '/../../spec/specs/')) {
            $this->markTestSkipped('Mustache spec submodule not initialized: run "git submodule update --init"');
        }
        $this->assertTrue(true);
    }

    /**
     * @dataProvider loadCommentSpec
     */
    public function testCommentSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertSame($expected, $template->render($data), $desc);
    }

    public function loadCommentSpec()
    {
        return $this->loadSpec('comments');
    }

    /**
     * @dataProvider loadDelimitersSpec
     */
    public function testDelimitersSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertSame($expected, $template->render($data), $desc);
    }

    public function loadDelimitersSpec()
    {
        return $this->loadSpec('delimiters');
    }

    /**
     * @dataProvider loadInterpolationSpec
     */
    public function testInterpolationSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertSame($expected, $template->render($data), $desc);
    }

    public function loadInterpolationSpec()
    {
        return $this->loadSpec('interpolation');
    }

    /**
     * @dataProvider loadInvertedSpec
     */
    public function testInvertedSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertSame($expected, $template->render($data), $desc);
    }

    public function loadInvertedSpec()
    {
        return $this->loadSpec('inverted');
    }

    /**
     * @dataProvider loadPartialsSpec
     */
    public function testPartialsSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertSame($expected, $template->render($data), $desc);
    }

    public function loadPartialsSpec()
    {
        return $this->loadSpec('partials');
    }

    /**
     * @dataProvider loadSectionsSpec
     */
    public function testSectionsSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertSame($expected, $template->render($data), $desc);
    }

    public function loadSectionsSpec()
    {
        return $this->loadSpec('sections');
    }

    /**
     * @dataProvider loadLambdasSpec
     */
    public function testLambdasSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertSame($expected, $template($this->prepareLambdasSpec($data)), $desc);
    }

    public function loadLambdasSpec()
    {
        return $this->loadSpec('~lambdas');
    }

    /**
     * Extract and lambdafy any 'lambda' values found in the $data array.
     */
    private function prepareLambdasSpec(array $data)
    {
        foreach ($data as $key => $val) {
            if (isset($val['__tag__']) && $val['__tag__'] === 'code') {
                if (!isset($val['php'])) {
                    $this->markTestSkipped(sprintf('PHP lambda test not implemented for this test.'));

                    return;
                }

                $func = $val['php'];
                $data[$key] = function ($text = null) use ($func) {
                    return eval($func);
                };
            } elseif (is_array($val)) {
                $data[$key] = $this->prepareLambdasSpec($val);
            }
        }

        return $data;
    }
}
