<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Delimiters
{
    public $start = 'It worked the first time.';

    public function middle()
    {
        return [
            ['item' => 'And it worked the second time.'],
            ['item' => 'As well as the third.'],
        ];
    }

    public $final = 'Then, surprisingly, it worked the final time.';
}
