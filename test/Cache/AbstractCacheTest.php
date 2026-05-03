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

use Mustache\Cache\AbstractCache;
use Mustache\Exception\InvalidArgumentException;
use Mustache\Logger\StreamLogger;
use Mustache\Test\TestCase;

class AbstractCacheTest extends TestCase
{
    public function testGetSetLogger()
    {
        $cache  = new CacheStub();
        $logger = new StreamLogger('php://stdout');
        $cache->setLogger($logger);
        $this->assertSame($logger, $cache->getLogger());
    }

    public function testSetLoggerThrowsExceptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $cache  = new CacheStub();
        $logger = new \StdClass();
        $cache->setLogger($logger);
    }
}

class CacheStub extends AbstractCache
{
    public function load($key)
    {
        // nada
    }

    public function cache($key, $value)
    {
        // nada
    }
}
