<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Loader;

use Mustache\Exception\RuntimeException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\FilesystemLoader;
use Mustache\Test\TestCase;

class FilesystemLoaderTest extends TestCase
{
    public function testConstructor()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new FilesystemLoader($baseDir, ['extension' => '.ms']);
        $this->assertSame('alpha contents', $loader->load('alpha'));
        $this->assertSame('beta contents', $loader->load('beta.ms'));
    }

    public function testTrailingSlashes()
    {
        // Not realpath, because it strips trailing slashes
        $baseDir = __DIR__ . '/../fixtures/templates/';
        $loader = new FilesystemLoader($baseDir);
        $this->assertSame('one contents', $loader->load('one'));
    }

    public function testConstructorWithProtocol()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new FilesystemLoader('test://' . $baseDir, ['extension' => '.ms']);
        $this->assertSame('alpha contents', $loader->load('alpha'));
        $this->assertSame('beta contents', $loader->load('beta.ms'));
    }

    public function testLoadTemplates()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new FilesystemLoader($baseDir);
        $this->assertSame('one contents', $loader->load('one'));
        $this->assertSame('two contents', $loader->load('two.mustache'));
    }

    public function testEmptyExtensionString()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new FilesystemLoader($baseDir, ['extension' => '']);
        $this->assertSame('one contents', $loader->load('one.mustache'));
        $this->assertSame('alpha contents', $loader->load('alpha.ms'));

        $loader = new FilesystemLoader($baseDir, ['extension' => null]);
        $this->assertSame('two contents', $loader->load('two.mustache'));
        $this->assertSame('beta contents', $loader->load('beta.ms'));
    }

    public function testMissingBaseDirThrowsException()
    {
        $this->expectException(RuntimeException::class);
        new FilesystemLoader(__DIR__ . '/not_a_directory');
    }

    public function testMissingTemplateThrowsException()
    {
        $this->expectException(UnknownTemplateException::class);
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new FilesystemLoader($baseDir);

        $loader->load('fake');
    }
}
