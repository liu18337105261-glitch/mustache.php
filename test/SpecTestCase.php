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

use Mustache\Engine;

abstract class SpecTestCase extends TestCase
{
    protected static $mustache;

    public static function set_up_before_class()
    {
        self::$mustache = new Engine();
    }

    protected static function loadTemplate($source, $partials)
    {
        self::$mustache->setPartials($partials);

        return self::$mustache->loadTemplate($source);
    }

    /**
     * Data provider for the mustache spec test.
     *
     * Loads JSON files from the spec and converts them to PHPisms.
     *
     * @param string $name
     *
     * @return array
     */
    protected function loadSpec($name)
    {
        $filename = __DIR__ . '/../spec/specs/' . $name . '.json';
        if (!file_exists($filename)) {
            return [];
        }

        $data = [];
        $file = file_get_contents($filename);
        $spec = json_decode($file, true);

        foreach ($spec['tests'] as $test) {
            $data[] = [
                $test['name'] . ': ' . $test['desc'],
                $test['template'],
                isset($test['partials']) ? $test['partials'] : [],
                $test['data'],
                $test['expected'],
            ];
        }

        return $data;
    }
}
