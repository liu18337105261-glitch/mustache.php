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

use Mustache\Engine;
use Mustache\Exception\RuntimeException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\FilesystemLoader;
use Mustache\Test\TestCase;

class FilesystemLoaderTest extends TestCase
{
    use TraversalFixtureTrait;

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

    public function testCustomStreamWrapperBaseDoesNotUseLocalPathChecks()
    {
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new FilesystemLoader('test://' . $baseDir, ['extension' => '']);

        $this->assertSame('alpha contents', $loader->load('alpha.ms'));
        $this->assertSame('alpha contents', $loader->load('/alpha.ms'));
        $this->assertInstanceOf(FilesystemLoader::class, new FilesystemLoader('test://not_a_local_directory'));
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

    public function testRejectsTraversalInName()
    {
        $paths = $this->createTraversalFixture('mustache_loader_test');
        $loader = new FilesystemLoader($paths['base']);

        try {
            try {
                $loader->load('../../secret/leaked');
                $this->fail('Expected traversal to throw');
            } catch (UnknownTemplateException $e) {
                $this->assertSame('../../secret/leaked', $e->getTemplateName());
            }
        } finally {
            $this->removeTraversalFixture($paths);
        }
    }

    public function testRejectsTraversalWithEmptyExtension()
    {
        $paths = $this->createTraversalFixture('mustache_loader_test');
        $loader = new FilesystemLoader($paths['base'], ['extension' => '']);

        try {
            try {
                $loader->load('../../secret/raw.txt');
                $this->fail('Expected traversal to throw');
            } catch (UnknownTemplateException $e) {
                $this->assertSame('../../secret/raw.txt', $e->getTemplateName());
            }
        } finally {
            $this->removeTraversalFixture($paths);
        }
    }

    public function testAllowsUnsafeTemplateNamesWhenConfigured()
    {
        $paths = $this->createTraversalFixture('mustache_loader_test');
        $loader = new FilesystemLoader($paths['base'], [
            'allow_unsafe_template_names' => true,
        ]);

        try {
            $this->assertSame('leaked contents', $loader->load('../../secret/leaked'));
        } finally {
            $this->removeTraversalFixture($paths);
        }
    }

    public function testRejectsNullByteInName()
    {
        $this->expectException(UnknownTemplateException::class);
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new FilesystemLoader($baseDir);

        $loader->load("one\0");
    }

    public function testSchemeLikeNameDoesNotActivateStreamWrapper()
    {
        $this->expectException(UnknownTemplateException::class);
        $baseDir = realpath(__DIR__ . '/../fixtures/templates');
        $loader = new FilesystemLoader($baseDir, ['extension' => '']);

        $loader->load('test://' . $baseDir . '/alpha.ms');
    }

    public function testEngineRejectsDynamicPartialTraversal()
    {
        $this->expectException(UnknownTemplateException::class);
        $paths = $this->createTraversalFixture('mustache_loader_test');
        $mustache = new Engine([
            'partials_loader' => new FilesystemLoader($paths['base']),
            'strict_tags' => Engine::STRICT_PARTIALS,
        ]);

        try {
            $mustache->render('{{> *target }}', ['target' => '../../secret/leaked']);
        } finally {
            $this->removeTraversalFixture($paths);
        }
    }

    public function testEngineRejectsParentTraversal()
    {
        $this->expectException(UnknownTemplateException::class);
        $paths = $this->createTraversalFixture('mustache_loader_test');
        $mustache = new Engine([
            'partials_loader' => new FilesystemLoader($paths['base']),
            'strict_tags' => Engine::STRICT_PARENTS,
        ]);

        try {
            $mustache->render('{{< ../../secret/leaked }}{{/ ../../secret/leaked }}');
        } finally {
            $this->removeTraversalFixture($paths);
        }
    }

    public function testEngineRejectsDynamicParentTraversal()
    {
        $this->expectException(UnknownTemplateException::class);
        $paths = $this->createTraversalFixture('mustache_loader_test');
        $mustache = new Engine([
            'partials_loader' => new FilesystemLoader($paths['base']),
            'strict_tags' => Engine::STRICT_PARENTS,
        ]);

        try {
            $mustache->render('{{< *parent }}{{/ *parent }}', ['parent' => '../../secret/leaked']);
        } finally {
            $this->removeTraversalFixture($paths);
        }
    }
}
