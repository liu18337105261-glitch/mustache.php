<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Logger;

use Mustache\Logger;
use Mustache\Logger\AbstractLogger;
use Mustache\Test\TestCase;

class AbstractLoggerTest extends TestCase
{
    public function testEverything()
    {
        $logger = new TestLogger();

        $logger->emergency('emergency message');
        $logger->alert('alert message');
        $logger->critical('critical message');
        $logger->error('error message');
        $logger->warning('warning message');
        $logger->notice('notice message');
        $logger->info('info message');
        $logger->debug('debug message');

        $expected = [
            [Logger::EMERGENCY, 'emergency message', []],
            [Logger::ALERT, 'alert message', []],
            [Logger::CRITICAL, 'critical message', []],
            [Logger::ERROR, 'error message', []],
            [Logger::WARNING, 'warning message', []],
            [Logger::NOTICE, 'notice message', []],
            [Logger::INFO, 'info message', []],
            [Logger::DEBUG, 'debug message', []],
        ];

        $this->assertSame($expected, $logger->log);
    }
}

class TestLogger extends AbstractLogger
{
    public $log = [];

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     */
    public function log($level, $message, array $context = [])
    {
        $this->log[] = [$level, $message, $context];
    }
}
