<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test;

use Mustache\Cache\FilesystemCache;
use Mustache\Cache\NoopCache;
use Mustache\Compiler;
use Mustache\Engine;
use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\RenderingException;
use Mustache\Exception\RuntimeException;
use Mustache\Loader\ArrayLoader;
use Mustache\Loader\ProductionFilesystemLoader;
use Mustache\Loader\StringLoader;
use Mustache\Logger;
use Mustache\Logger\StreamLogger;
use Mustache\Parser;
use Mustache\Template;
use Mustache\Tokenizer;

class EngineTest extends FunctionalTestCase
{
    public function testConstructor()
    {
        $logger         = new StreamLogger(tmpfile());
        $loader         = new StringLoader();
        $partialsLoader = new ArrayLoader();
        $mustache       = new Engine([
            'template_class_prefix' => '__whot__',
            'cache'                 => self::$tempDir,
            'cache_file_mode'       => 777,
            'logger'                => $logger,
            'loader'                => $loader,
            'partials_loader'       => $partialsLoader,
            'partials'              => [
                'foo' => '{{ foo }}',
            ],
            'helpers' => [
                'foo' => [$this, 'getFoo'],
                'bar' => 'BAR',
            ],
            'escape'       => 'strtoupper',
            'entity_flags' => ENT_QUOTES,
            'charset'      => 'ISO-8859-1',
            'pragmas'      => [Engine::PRAGMA_FILTERS],
        ]);

        $this->assertSame($logger, $mustache->getLogger());
        $this->assertSame($loader, $mustache->getLoader());
        $this->assertSame($partialsLoader, $mustache->getPartialsLoader());
        $this->assertSame('{{ foo }}', $partialsLoader->load('foo'));
        $this->assertStringContainsString('__whot__', $mustache->getTemplateClassName('{{ foo }}'));
        $this->assertSame('strtoupper', $mustache->getEscape());
        $this->assertSame(ENT_QUOTES, $mustache->getEntityFlags());
        $this->assertSame('ISO-8859-1', $mustache->getCharset());
        $this->assertTrue($mustache->hasHelper('foo'));
        $this->assertTrue($mustache->hasHelper('bar'));
        $this->assertFalse($mustache->hasHelper('baz'));
        $this->assertInstanceOf(FilesystemCache::class, $mustache->getCache());
        $this->assertSame([Engine::PRAGMA_FILTERS], $mustache->getPragmas());
    }

    public static function getFoo()
    {
        return 'foo';
    }

    public function testRender()
    {
        $source = '{{ foo }}';
        $data   = ['bar' => 'baz'];
        $output = 'TEH OUTPUT';

        $template = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mustache = new MustacheStub();
        $mustache->template = $template;

        $template->expects($this->once())
            ->method('render')
            ->with($data)
            ->willReturn($output);

        $this->assertSame($output, $mustache->render($source, $data));
        $this->assertSame($source, $mustache->source);
    }

    public function testSettingServices()
    {
        $logger    = new StreamLogger(tmpfile());
        $loader    = new StringLoader();
        $tokenizer = new Tokenizer();
        $parser    = new Parser();
        $compiler  = new Compiler();
        $mustache  = new Engine();
        $cache     = new FilesystemCache(self::$tempDir);

        $this->assertNotSame($logger, $mustache->getLogger());
        $mustache->setLogger($logger);
        $this->assertSame($logger, $mustache->getLogger());

        $this->assertNotSame($loader, $mustache->getLoader());
        $mustache->setLoader($loader);
        $this->assertSame($loader, $mustache->getLoader());

        $this->assertNotSame($loader, $mustache->getPartialsLoader());
        $mustache->setPartialsLoader($loader);
        $this->assertSame($loader, $mustache->getPartialsLoader());

        $this->assertNotSame($tokenizer, $mustache->getTokenizer());
        $mustache->setTokenizer($tokenizer);
        $this->assertSame($tokenizer, $mustache->getTokenizer());

        $this->assertNotSame($parser, $mustache->getParser());
        $mustache->setParser($parser);
        $this->assertSame($parser, $mustache->getParser());

        $this->assertNotSame($compiler, $mustache->getCompiler());
        $mustache->setCompiler($compiler);
        $this->assertSame($compiler, $mustache->getCompiler());

        $this->assertNotSame($cache, $mustache->getCache());
        $mustache->setCache($cache);
        $this->assertSame($cache, $mustache->getCache());
    }

    public function testCache()
    {
        $mustache = new Engine([
            'template_class_prefix' => '__whot__',
            'cache'                 => self::$tempDir,
        ]);

        $source    = '{{ foo }}';
        $template  = $mustache->loadTemplate($source);
        $className = $mustache->getTemplateClassName($source);

        $this->assertInstanceOf($className, $template);
    }

    public function testLambdaCache()
    {
        $mustache = new MustacheStub([
            'cache'                  => self::$tempDir,
            'cache_lambda_templates' => true,
        ]);

        $this->assertNotInstanceOf(NoopCache::class, $mustache->getProtectedLambdaCache());
        $this->assertSame($mustache->getCache(), $mustache->getProtectedLambdaCache());
    }

    public function testWithoutLambdaCache()
    {
        $mustache = new MustacheStub([
            'cache' => self::$tempDir,
        ]);

        $this->assertInstanceOf(NoopCache::class, $mustache->getProtectedLambdaCache());
        $this->assertNotSame($mustache->getCache(), $mustache->getProtectedLambdaCache());
    }

    /**
     * @dataProvider getInvalidTemplateClassPrefixes
     */
    public function testInvalidTemplatePrefixThrowsException($prefix)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('template_class_prefix');
        new Engine([
            'template_class_prefix' => $prefix,
        ]);
    }

    public function getInvalidTemplateClassPrefixes()
    {
        return [
            [''],
            ['9Mustache_'],
            ['Mustache-Template_'],
            ['Mustache\\Template_'],
            ['A extends \\Mustache\\Template { public function renderInternal(\\Mustache\\Context $context, $indent = "") { return ""; } } echo "PREFIX_INJECTION"; class B'],
        ];
    }

    /**
     * @dataProvider getInvalidStrictTags
     */
    public function testInvalidStrictTagsThrowsException($strictTags)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('strict_tags');
        new Engine([
            'strict_tags' => $strictTags,
        ]);
    }

    public function getInvalidStrictTags()
    {
        return [
            ['true'],
            [[Engine::STRICT_INTERPOLATION]],
            [Engine::STRICT_ALL << 1],
        ];
    }

    /**
     * @dataProvider getBadEscapers
     */
    public function testNonCallableEscapeThrowsException($escape)
    {
        $this->expectException(InvalidArgumentException::class);
        new Engine(['escape' => $escape]);
    }

    public function getBadEscapers()
    {
        return [
            ['nothing'],
            ['foo', 'bar'],
        ];
    }

    public function testImmutablePartialsLoadersThrowException()
    {
        $this->expectException(RuntimeException::class);
        $mustache = new Engine([
            'partials_loader' => new StringLoader(),
        ]);

        $mustache->setPartials(['foo' => '{{ foo }}']);
    }

    public function testMissingPartialsTreatedAsEmptyString()
    {
        $mustache = new Engine([
            'partials_loader' => new ArrayLoader([
                'foo' => 'FOO',
                'baz' => 'BAZ',
            ]),
        ]);

        $this->assertSame('FOOBAZ', $mustache->render('{{>foo}}{{>bar}}{{>baz}}', []));
    }

    public function testHelpers()
    {
        $foo = function () {
            return $this->getFoo();
        };
        $bar = 'BAR';
        $mustache = new Engine(['helpers' => [
            'foo' => $foo,
            'bar' => $bar,
        ]]);

        $helpers = $mustache->getHelpers();
        $this->assertTrue($mustache->hasHelper('foo'));
        $this->assertTrue($mustache->hasHelper('bar'));
        $this->assertTrue($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));
        $this->assertSame($foo, $mustache->getHelper('foo'));
        $this->assertSame($bar, $mustache->getHelper('bar'));

        $mustache->removeHelper('bar');
        $this->assertFalse($mustache->hasHelper('bar'));
        $mustache->addHelper('bar', $bar);
        $this->assertSame($bar, $mustache->getHelper('bar'));

        $baz = function ($text) {
            return self::wrapWithUnderscores($text);
        };
        $this->assertFalse($mustache->hasHelper('baz'));
        $this->assertFalse($helpers->has('baz'));

        $mustache->addHelper('baz', $baz);
        $this->assertTrue($mustache->hasHelper('baz'));
        $this->assertTrue($helpers->has('baz'));

        // ... and a functional test
        $tpl = $mustache->loadTemplate('{{foo}} - {{bar}} - {{#baz}}qux{{/baz}}');
        $this->assertSame('foo - BAR - __qux__', $tpl->render());
        $this->assertSame('foo - BAR - __qux__', $tpl->render(['qux' => "won't mess things up"]));
    }

    public static function wrapWithUnderscores($text)
    {
        return '__' . $text . '__';
    }

    public function testSetHelpersThrowsExceptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $mustache = new Engine();
        $mustache->setHelpers('monkeymonkeymonkey');
    }

    public function testSetLoggerThrowsExceptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $mustache = new Engine();
        $mustache->setLogger(new \StdClass());
    }

    public function testLoadPartialCascading()
    {
        $loader = new ArrayLoader([
            'foo' => 'FOO',
        ]);

        $mustache = new Engine(['loader' => $loader]);

        $tpl = $mustache->loadTemplate('foo');

        $this->assertSame($tpl, $mustache->loadPartial('foo'));

        $mustache->setPartials([
            'foo' => 'f00',
        ]);

        // setting partials overrides the default template loading fallback.
        $this->assertNotSame($tpl, $mustache->loadPartial('foo'));

        // but it didn't overwrite the original template loader templates.
        $this->assertSame($tpl, $mustache->loadTemplate('foo'));
    }

    public function testPartialLoadFailLogging()
    {
        $name     = tempnam(sys_get_temp_dir(), 'mustache-test');
        $mustache = new Engine([
            'logger'   => new StreamLogger($name, Logger::WARNING),
            'partials' => [
                'foo' => 'FOO',
                'bar' => 'BAR',
            ],
        ]);

        $result = $mustache->render('{{> foo }}{{> bar }}{{> baz }}', []);
        $this->assertSame('FOOBAR', $result);

        $this->assertStringContainsString('WARNING: Partial not found: "baz"', file_get_contents($name));
    }

    public function testCacheWarningLogging()
    {
        list($name, $mustache) = $this->getLoggedMustache(Logger::WARNING);
        $mustache->render('{{ foo }}', ['foo' => 'FOO']);
        $this->assertStringContainsString('WARNING: Template cache disabled, evaluating', file_get_contents($name));
    }

    public function testDebugRenderingExceptionIncludesRenderingStack()
    {
        $mustache = new Engine([
            'debug_rendering' => true,
            'loader' => new ArrayLoader([
                'main' => '{{# items }}{{> row }}{{/ items }}',
            ]),
            'partials' => [
                'row' => '{{ bad }}',
            ],
            'escape' => function ($value) {
                if (is_array($value)) {
                    throw new \RuntimeException('array value');
                }

                return $value;
            },
        ]);

        try {
            $mustache->loadTemplate('main')->render([
                'items' => [
                    ['bad' => []],
                ],
            ]);
        } catch (RenderingException $e) {
            $frames = $e->getFrames();

            $this->assertCount(3, $frames);
            $this->assertSame('section', $frames[0]['type']);
            $this->assertSame('items', $frames[0]['name']);
            $this->assertSame('main', $frames[0]['source']);
            $this->assertSame('partial', $frames[1]['type']);
            $this->assertSame('row', $frames[1]['name']);
            $this->assertSame('main', $frames[1]['source']);
            $this->assertSame('variable', $frames[2]['type']);
            $this->assertSame('bad', $frames[2]['name']);
            $this->assertSame('row', $frames[2]['source']);

            $this->assertStringContainsString('Error rendering variable "bad" on line 0 of row: array value', $e->getMessage());
            $this->assertStringContainsString('while rendering partial "row" on line 0 of main', $e->getMessage());
            $this->assertStringContainsString('while rendering section "items" on line 0 of main', $e->getMessage());
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());

            return;
        }

        $this->fail('Expected RenderingException');
    }

    public function testLoggingIsNotTooAnnoying()
    {
        list($name, $mustache) = $this->getLoggedMustache();
        $mustache->render('{{ foo }}{{> bar }}', ['foo' => 'FOO']);
        $this->assertEmpty(file_get_contents($name));
    }

    public function testVerboseLoggingIsVerbose()
    {
        list($name, $mustache) = $this->getLoggedMustache(Logger::DEBUG);
        $mustache->render('{{ foo }}{{> bar }}', ['foo' => 'FOO']);
        $log = file_get_contents($name);
        $this->assertStringContainsString('DEBUG: Instantiating template: ', $log);
        $this->assertStringContainsString('WARNING: Partial not found: "bar"', $log);
    }

    public function testUnknownPragmaThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        new Engine([
            'pragmas' => ['UNKNOWN'],
        ]);
    }

    public function testCompileFromMustacheSourceInstance()
    {
        $baseDir = realpath(__DIR__ . '/fixtures/templates');
        $mustache = new Engine([
            'loader' => new ProductionFilesystemLoader($baseDir),
        ]);
        $this->assertSame('one contents', $mustache->render('one'));
    }

    private function getLoggedMustache($level = Logger::ERROR)
    {
        $name     = tempnam(sys_get_temp_dir(), 'mustache-test');
        $mustache = new Engine([
            'logger' => new StreamLogger($name, $level),
        ]);

        return [$name, $mustache];
    }

    public function testCustomDelimiters()
    {
        $mustache = new Engine([
            'delimiters' => '[[ ]]',
            'partials'   => [
                'one' => '[[> two ]]',
                'two' => '[[ a ]]',
            ],
        ]);

        $tpl = $mustache->loadTemplate('[[# a ]][[ b ]][[/a ]]');
        $this->assertSame('c', $tpl->render(['a' => true, 'b' => 'c']));

        $tpl = $mustache->loadTemplate('[[> one ]]');
        $this->assertSame('b', $tpl->render(['a' => 'b']));
    }

    public function testBuggyPropertyShadowing()
    {
        $mustache = new Engine();
        $this->assertFalse($mustache->getBuggyPropertyShadowing());

        $mustache = new Engine(['buggy_property_shadowing' => true]);
        $this->assertTrue($mustache->getBuggyPropertyShadowing());

        $mustache = new Engine(['buggy_property_shadowing' => false]);
        $this->assertFalse($mustache->getBuggyPropertyShadowing());
    }

    public function testDoubleRenderLambdas()
    {
        $mustache = new Engine();
        $this->assertFalse($mustache->getDoubleRenderLambdas());

        $mustache = new Engine(['double_render_lambdas' => true]);
        $this->assertTrue($mustache->getDoubleRenderLambdas());

        $mustache = new Engine(['double_render_lambdas' => false]);
        $this->assertFalse($mustache->getDoubleRenderLambdas());
    }

    /**
     * @dataProvider pragmaData
     */
    public function testPragmasConstructorOption(array $pragmas, array $helpers, array $data, $tpl, $expect)
    {
        $mustache = new Engine([
            'pragmas' => $pragmas,
            'helpers' => $helpers,
        ]);

        $this->assertSame($expect, $mustache->render($tpl, $data));
    }

    public function pragmaData()
    {
        $helpers = [
            'longdate' => function (\DateTime $value) {
                return $value->format('Y-m-d h:m:s');
            },
        ];

        $data = [
            'date' => new \DateTime('1/1/2000', new \DateTimeZone('UTC')),
        ];

        $tpl = '{{ date | longdate }}';

        return [
            [[Engine::PRAGMA_FILTERS], $helpers, $data, $tpl, '2000-01-01 12:01:00'],
            [[],                       $helpers, $data, $tpl, ''],
        ];
    }

    public function testFiltersPragmaWithoutLambdasThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The FILTERS pragma requires lambda support');
        new Engine([
            'lambdas' => false,
            'pragmas' => [Engine::PRAGMA_FILTERS],
        ]);
    }
}

class MustacheStub extends Engine
{
    public $source;
    public $template;

    public function loadTemplate($source)
    {
        $this->source = $source;

        return $this->template;
    }

    public function getProtectedLambdaCache()
    {
        return $this->getLambdaCache();
    }
}
