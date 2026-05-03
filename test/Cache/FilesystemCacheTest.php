<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Cache;

use Mustache\Cache\FilesystemCache;
use Mustache\Test\FunctionalTestCase;

class FilesystemCacheTest extends FunctionalTestCase
{
    public function testCacheGetNone()
    {
        $key = 'some key';
        $cache = new FilesystemCache(self::$tempDir);
        $loaded = $cache->load($key);

        $this->assertFalse($loaded);
    }

    public function testCachePut()
    {
        $key = 'some key';
        $value = '<?php /* some value */';
        $cache = new FilesystemCache(self::$tempDir);
        $cache->cache($key, $value);
        $loaded = $cache->load($key);

        $this->assertTrue($loaded);
    }
}
