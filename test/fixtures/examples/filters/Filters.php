<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Filters
{
    public $states = [
        'al' => 'Alabama',
        'ak' => 'Alaska',
        'az' => 'Arizona',
        'ar' => 'Arkansas',
        'ca' => 'California',
        'co' => 'Colorado',
        'ct' => 'Connecticut',
        'de' => 'Delaware',
        'fl' => 'Florida',
        'ga' => 'Georgia',
        'hi' => 'Hawaii',
        'id' => 'Idaho',
        'il' => 'Illinois',
        'in' => 'Indiana',
        'ia' => 'Iowa',
        'ks' => 'Kansas',
        'ky' => 'Kentucky',
        'la' => 'Louisiana',
        'me' => 'Maine',
        'md' => 'Maryland',
        'ma' => 'Massachusetts',
        'mi' => 'Michigan',
        'mn' => 'Minnesota',
        'ms' => 'Mississippi',
        'mo' => 'Missouri',
        'mt' => 'Montana',
        'ne' => 'Nebraska',
        'nv' => 'Nevada',
        'nh' => 'New Hampshire',
        'nj' => 'New Jersey',
        'nm' => 'New Mexico',
        'ny' => 'New York',
        'nc' => 'North Carolina',
        'nd' => 'North Dakota',
        'oh' => 'Ohio',
        'ok' => 'Oklahoma',
        'or' => 'Oregon',
        'pa' => 'Pennsylvania',
        'ri' => 'Rhode Island',
        'sc' => 'South Carolina',
        'sd' => 'South Dakota',
        'tn' => 'Tennessee',
        'tx' => 'Texas',
        'ut' => 'Utah',
        'vt' => 'Vermont',
        'va' => 'Virginia',
        'wa' => 'Washington',
        'wv' => 'West Virginia',
        'wi' => 'Wisconsin',
        'wy' => 'Wyoming',
    ];

    public function upcase()
    {
        return function ($val) {
            return strtoupper($val);
        };
    }

    public function eachPair()
    {
        return function ($val) {
            $ret = [];
            foreach ($val as $key => $value) {
                array_push($ret, compact('key', 'value'));
            }

            return $ret;
        };
    }
}
