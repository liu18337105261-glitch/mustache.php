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

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\LogicException;
use Mustache\Logger;
use Mustache\Logger\StreamLogger;
use Mustache\Test\TestCase;

class StreamLoggerTest extends TestCase
{
    /**
     * @dataProvider acceptsStreamData
     */
    public function testAcceptsStream($name, $stream)
    {
        $logger = new StreamLogger($stream);
        $logger->log(Logger::CRITICAL, 'message');

        $this->assertSame("CRITICAL: message\n", file_get_contents($name));
    }

    public function acceptsStreamData()
    {
        $one = tempnam(sys_get_temp_dir(), 'mustache-test');
        $two = tempnam(sys_get_temp_dir(), 'mustache-test');

        return [
            [$one, $one],
            [$two, fopen($two, 'a')],
        ];
    }

    public function testPrematurelyClosedStreamThrowsException()
    {
        $this->expectException(LogicException::class);
        $stream = tmpfile();
        $logger = new StreamLogger($stream);
        fclose($stream);
        $logger->log(Logger::CRITICAL, 'message');
    }

    /**
     * @dataProvider getLevels
     */
    public function testLoggingThresholds($logLevel, $level, $shouldLog)
    {
        $stream = tmpfile();
        $logger = new StreamLogger($stream, $logLevel);
        $logger->log($level, 'logged');

        rewind($stream);
        $result = fread($stream, 1024);

        if ($shouldLog) {
            $this->assertStringContainsString('logged', $result);
        } else {
            $this->assertEmpty($result);
        }
    }

    public function getLevels()
    {
        // $logLevel, $level, $shouldLog
        return [
            // identities
            [Logger::EMERGENCY, Logger::EMERGENCY, true],
            [Logger::ALERT,     Logger::ALERT,     true],
            [Logger::CRITICAL,  Logger::CRITICAL,  true],
            [Logger::ERROR,     Logger::ERROR,     true],
            [Logger::WARNING,   Logger::WARNING,   true],
            [Logger::NOTICE,    Logger::NOTICE,    true],
            [Logger::INFO,      Logger::INFO,      true],
            [Logger::DEBUG,     Logger::DEBUG,     true],

            // one above
            [Logger::ALERT,     Logger::EMERGENCY, true],
            [Logger::CRITICAL,  Logger::ALERT,     true],
            [Logger::ERROR,     Logger::CRITICAL,  true],
            [Logger::WARNING,   Logger::ERROR,     true],
            [Logger::NOTICE,    Logger::WARNING,   true],
            [Logger::INFO,      Logger::NOTICE,    true],
            [Logger::DEBUG,     Logger::INFO,      true],

            // one below
            [Logger::EMERGENCY, Logger::ALERT,     false],
            [Logger::ALERT,     Logger::CRITICAL,  false],
            [Logger::CRITICAL,  Logger::ERROR,     false],
            [Logger::ERROR,     Logger::WARNING,   false],
            [Logger::WARNING,   Logger::NOTICE,    false],
            [Logger::NOTICE,    Logger::INFO,      false],
            [Logger::INFO,      Logger::DEBUG,     false],
        ];
    }

    /**
     * @dataProvider getLogMessages
     */
    public function testLogging($level, $message, array $context, $expected)
    {
        $stream = tmpfile();
        $logger = new StreamLogger($stream, Logger::DEBUG);
        $logger->log($level, $message, $context);

        rewind($stream);
        $result = fread($stream, 1024);

        $this->assertSame($expected, $result);
    }

    public function getLogMessages()
    {
        // $level, $message, $context, $expected
        return [
            [Logger::DEBUG,     'debug message',     [],  "DEBUG: debug message\n"],
            [Logger::INFO,      'info message',      [],  "INFO: info message\n"],
            [Logger::NOTICE,    'notice message',    [],  "NOTICE: notice message\n"],
            [Logger::WARNING,   'warning message',   [],  "WARNING: warning message\n"],
            [Logger::ERROR,     'error message',     [],  "ERROR: error message\n"],
            [Logger::CRITICAL,  'critical message',  [],  "CRITICAL: critical message\n"],
            [Logger::ALERT,     'alert message',     [],  "ALERT: alert message\n"],
            [Logger::EMERGENCY, 'emergency message', [],  "EMERGENCY: emergency message\n"],

            // with context
            [
                Logger::ERROR,
                'error message',
                ['name' => 'foo', 'number' => 42],
                "ERROR: error message\n",
            ],

            // with interpolation
            [
                Logger::ERROR,
                'error {name}-{number}',
                ['name' => 'foo', 'number' => 42],
                "ERROR: error foo-42\n",
            ],

            // with iterpolation false positive
            [
                Logger::ERROR,
                'error {nothing}',
                ['name' => 'foo', 'number' => 42],
                "ERROR: error {nothing}\n",
            ],

            // with interpolation injection
            [
                Logger::ERROR,
                '{foo}',
                ['foo' => '{bar}', 'bar' => 'FAIL'],
                "ERROR: {bar}\n",
            ],
        ];
    }

    public function testChangeLoggingLevels()
    {
        $stream = tmpfile();
        $logger = new StreamLogger($stream);

        $logger->setLevel(Logger::ERROR);
        $this->assertSame(Logger::ERROR, $logger->getLevel());

        $logger->log(Logger::WARNING, 'ignore this');

        $logger->setLevel(Logger::INFO);
        $this->assertSame(Logger::INFO, $logger->getLevel());

        $logger->log(Logger::WARNING, 'log this');

        $logger->setLevel(Logger::CRITICAL);
        $this->assertSame(Logger::CRITICAL, $logger->getLevel());

        $logger->log(Logger::ERROR, 'ignore this');

        rewind($stream);
        $result = fread($stream, 1024);

        $this->assertSame("WARNING: log this\n", $result);
    }

    public function testThrowsInvalidArgumentExceptionWhenSettingUnknownLevels()
    {
        $this->expectException(InvalidArgumentException::class);
        $logger = new StreamLogger(tmpfile());
        $logger->setLevel('bacon');
    }

    public function testThrowsInvalidArgumentExceptionWhenLoggingUnknownLevels()
    {
        $this->expectException(InvalidArgumentException::class);
        $logger = new StreamLogger(tmpfile());
        $logger->log('bacon', 'CODE BACON ERROR!');
    }
}
