<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class SectionsNested
{
    public $name = 'Little Mac';

    public function enemies()
    {
        return [
            [
                'name'    => 'Von Kaiser',
                'enemies' => [
                    ['name' => 'Super Macho Man'],
                    ['name' => 'Piston Honda'],
                    ['name' => 'Mr. Sandman'],
                ],
            ],
            [
                'name'    => 'Mike Tyson',
                'enemies' => [
                    ['name' => 'Soda Popinski'],
                    ['name' => 'King Hippo'],
                    ['name' => 'Great Tiger'],
                    ['name' => 'Glass Joe'],
                ],
            ],
            [
                'name'    => 'Don Flamenco',
                'enemies' => [
                    ['name' => 'Bald Bull'],
                ],
            ],
        ];
    }
}
