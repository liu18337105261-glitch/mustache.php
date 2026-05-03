<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Complex
{
    public $header = 'Colors';

    public $item = [
        ['name' => 'red', 'current' => true, 'url' => '#Red'],
        ['name' => 'green', 'current' => false, 'url' => '#Green'],
        ['name' => 'blue', 'current' => false, 'url' => '#Blue'],
    ];

    public function notEmpty()
    {
        return !($this->isEmpty());
    }

    public function isEmpty()
    {
        return count($this->item) === 0;
    }
}
