<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Whitespace
{
    public $foo = 'alpha';

    public $bar = 'beta';

    public function baz()
    {
        return 'gamma';
    }

    public function qux()
    {
        return [
            ['key with space' => 'A'],
            ['key with space' => 'B'],
            ['key with space' => 'C'],
            ['key with space' => 'D'],
            ['key with space' => 'E'],
            ['key with space' => 'F'],
            ['key with space' => 'G'],
        ];
    }
}
