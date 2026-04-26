<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2025 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Spec;

use Mustache\Engine;
use Mustache\Test\SpecTestCase;

/**
 * A PHPUnit test case wrapping the Mustache Spec.
 *
 * @group spec
 */
class MustacheInheritanceSpecTest extends SpecTestCase
{
    public static function set_up_before_class()
    {
        self::$mustache = new Engine();
    }

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
     * @dataProvider loadInheritanceSpec
     */
    public function testInheritanceSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertSame($expected, $template->render($data), $desc);
    }

    public function loadInheritanceSpec()
    {
        // return $this->loadSpec('sections');
        // return [];
        // die;
        return $this->loadSpec('~inheritance');
    }
}
