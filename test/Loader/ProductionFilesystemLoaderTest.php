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
use Mustache\Loader\ProductionFilesystemLoader;
use Mustache\Source;
use Mustache\Test\TestCase;

class ProductionFilesystemLoaderTest extends TestCase
{
    public function testConstructor()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new ProductionFilesystemLoader($baseDir, ['extension' => '.ms']);
        $this->assertInstanceOf(Source::class, $loader->load('alpha'));
        $this->assertSame('alpha contents', $loader->load('alpha')->getSource());
        $this->assertInstanceOf(Source::class, $loader->load('beta.ms'));
        $this->assertSame('beta contents', $loader->load('beta.ms')->getSource());
    }

    public function testTrailingSlashes()
    {
        // Not realpath, because it strips trailing slashes
        $baseDir = __DIR__ . '/../fixtures/templates/';
        $loader = new ProductionFilesystemLoader($baseDir);
        $this->assertSame('one contents', $loader->load('one')->getSource());
    }

    public function testConstructorWithProtocol()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');

        $loader = new ProductionFilesystemLoader('file://' . $baseDir, ['extension' => '.ms']);
        $this->assertSame('alpha contents', $loader->load('alpha')->getSource());
        $this->assertSame('beta contents', $loader->load('beta.ms')->getSource());
    }

    public function testLoadTemplates()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new ProductionFilesystemLoader($baseDir);
        $this->assertSame('one contents', $loader->load('one')->getSource());
        $this->assertSame('two contents', $loader->load('two.mustache')->getSource());
    }

    public function testEmptyExtensionString()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');

        $loader = new ProductionFilesystemLoader($baseDir, ['extension' => '']);
        $this->assertSame('one contents', $loader->load('one.mustache')->getSource());
        $this->assertSame('alpha contents', $loader->load('alpha.ms')->getSource());

        $loader = new ProductionFilesystemLoader($baseDir, ['extension' => null]);
        $this->assertSame('two contents', $loader->load('two.mustache')->getSource());
        $this->assertSame('beta contents', $loader->load('beta.ms')->getSource());
    }

    public function testMissingBaseDirThrowsException()
    {
        $this->expectException(RuntimeException::class);
        new ProductionFilesystemLoader(__DIR__ . '/not_a_directory');
    }

    public function testMissingTemplateThrowsException()
    {
        $this->expectException(UnknownTemplateException::class);
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new ProductionFilesystemLoader($baseDir);

        $loader->load('fake');
    }

    public function testLoadWithDifferentStatProps()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $noStatLoader = new ProductionFilesystemLoader($baseDir, ['stat_props' => null]);
        $mtimeLoader = new ProductionFilesystemLoader($baseDir, ['stat_props' => ['mtime']]);
        $sizeLoader = new ProductionFilesystemLoader($baseDir, ['stat_props' => ['size']]);
        $bothLoader = new ProductionFilesystemLoader($baseDir, ['stat_props' => ['mtime', 'size']]);

        $noStatKey = $noStatLoader->load('one.mustache')->getKey();
        $mtimeKey = $mtimeLoader->load('one.mustache')->getKey();
        $sizeKey = $sizeLoader->load('one.mustache')->getKey();
        $bothKey = $bothLoader->load('one.mustache')->getKey();

        $this->assertNotSame($noStatKey, $mtimeKey);
        $this->assertNotSame($noStatKey, $sizeKey);
        $this->assertNotSame($noStatKey, $bothKey);
        $this->assertNotSame($mtimeKey, $sizeKey);
        $this->assertNotSame($mtimeKey, $bothKey);
        $this->assertNotSame($sizeKey, $bothKey);
    }
}
