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

use Mustache\Context;
use Mustache\Engine;
use Mustache\Template;

class TemplateTest extends TestCase
{
    public function testConstructor()
    {
        $mustache = new Engine();
        $template = new TemplateStub($mustache);
        $this->assertSame($mustache, $template->getMustache());
    }

    public function testRendering()
    {
        $rendered = '<< wheee >>';
        $mustache = new Engine();
        $template = new TemplateStub($mustache);
        $template->rendered = $rendered;
        $context  = new Context();

        $this->assertSame($rendered, $template());
        $this->assertSame($rendered, $template->render());
        $this->assertSame($rendered, $template->renderInternal($context));
        $this->assertSame($rendered, $template->render(['foo' => 'bar']));
    }

    public function testResolveValueWithoutLambdas()
    {
        $mustache = new Engine();
        $template = new NoLambdasTemplateStub($mustache);
        $context = new Context();

        $this->assertSame([NoLambdasTemplateStub::class, 'staticMethod'], $template->getResolvedValue([NoLambdasTemplateStub::class, 'staticMethod'], $context));
    }
}

class TemplateStub extends Template
{
    public $rendered;

    public function getMustache()
    {
        return $this->mustache;
    }

    public function renderInternal(Context $context, $indent = '', $escape = false)
    {
        return $this->rendered;
    }
}

class NoLambdasTemplateStub extends Template
{
    protected $strictCallables = false; // Disabling strict callables for testing purposes
    protected $lambdas = false;

    public function getResolvedValue($value, Context $context)
    {
        return $this->resolveValue($value, $context);
    }

    public static function staticMethod()
    {
        return 'fail';
    }

    public function renderInternal(Context $context, $indent = '', $escape = false)
    {
        return '';
    }
}
