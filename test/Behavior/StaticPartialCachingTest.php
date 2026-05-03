<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2025 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Behavior;

use Mustache\Engine;
use Mustache\Test\TestCase;

class StaticPartialCachingTest extends TestCase
{
    public function testStaticPartialsInSectionsAreLoadedOnce()
    {
        $mustache = new CountingPartialLoadEngine([
            'partials' => [
                'row' => '{{ name }}',
            ],
        ]);

        $result = $mustache->render('{{# items }}{{> row }}{{/ items }}', [
            'items' => [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
        ]);

        $this->assertSame('abc', $result);
        $this->assertSame(1, $mustache->getPartialLoadCount('row'));
    }

    public function testStaticPartialsInTraversableSectionsAreLoadedAfterIterationStarts()
    {
        $mustache = new CountingPartialLoadEngine();
        $items = function () use ($mustache) {
            $mustache->setPartials([
                'row' => '{{ name }}',
            ]);

            yield ['name' => 'a'];
            yield ['name' => 'b'];
        };

        $result = $mustache->render('{{# items }}{{> row }}{{/ items }}', [
            'items' => $items(),
        ]);

        $this->assertSame('ab', $result);
        $this->assertSame(1, $mustache->getPartialLoadCount('row'));
    }

    public function testMissingStaticPartialsInSectionsAreLoadedOnce()
    {
        $mustache = new CountingPartialLoadEngine();

        $result = $mustache->render('{{# items }}{{> missing }}{{/ items }}', [
            'items' => [1, 2, 3],
        ]);

        $this->assertSame('', $result);
        $this->assertSame(1, $mustache->getPartialLoadCount('missing'));
    }

    public function testMissingStaticPartialsInEmptySectionsAreNotLoaded()
    {
        $mustache = new CountingPartialLoadEngine();

        $result = $mustache->render('{{# items }}{{> missing }}{{/ items }}', [
            'items' => [],
        ]);

        $this->assertSame('', $result);
        $this->assertSame(0, $mustache->getPartialLoadCount('missing'));
    }

    public function testDynamicPartialsInSectionsAreNotCached()
    {
        $mustache = new CountingPartialLoadEngine([
            'partials' => [
                'row' => '{{ name }}',
            ],
        ]);

        $result = $mustache->render('{{# items }}{{> *partial }}{{/ items }}', [
            'items' => [
                ['name' => 'a', 'partial' => 'row'],
                ['name' => 'b', 'partial' => 'row'],
                ['name' => 'c', 'partial' => 'row'],
            ],
        ]);

        $this->assertSame('abc', $result);
        $this->assertSame(3, $mustache->getPartialLoadCount('row'));
    }

    public function testStaticParentsInSectionsAreLoadedOnce()
    {
        $mustache = new CountingPartialLoadEngine([
            'partials' => [
                'layout' => '[{{$ body }}default{{/ body }}]',
            ],
        ]);

        $result = $mustache->render('{{# items }}{{< layout }}{{$ body }}{{ name }}{{/ body }}{{/ layout }}{{/ items }}', [
            'items' => [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
        ]);

        $this->assertSame('[a][b][c]', $result);
        $this->assertSame(1, $mustache->getPartialLoadCount('layout'));
    }

    public function testStaticParentsInTraversableSectionsAreLoadedAfterIterationStarts()
    {
        $mustache = new CountingPartialLoadEngine();
        $items = function () use ($mustache) {
            $mustache->setPartials([
                'layout' => '[{{$ body }}default{{/ body }}]',
            ]);

            yield ['name' => 'a'];
            yield ['name' => 'b'];
        };

        $result = $mustache->render('{{# items }}{{< layout }}{{$ body }}{{ name }}{{/ body }}{{/ layout }}{{/ items }}', [
            'items' => $items(),
        ]);

        $this->assertSame('[a][b]', $result);
        $this->assertSame(1, $mustache->getPartialLoadCount('layout'));
    }

    public function testDynamicParentsInSectionsAreNotCached()
    {
        $mustache = new CountingPartialLoadEngine([
            'partials' => [
                'layout' => '[{{$ body }}default{{/ body }}]',
            ],
        ]);

        $result = $mustache->render('{{# items }}{{< *layout }}{{$ body }}{{ name }}{{/ body }}{{/ *layout }}{{/ items }}', [
            'items' => [
                ['name' => 'a', 'layout' => 'layout'],
                ['name' => 'b', 'layout' => 'layout'],
                ['name' => 'c', 'layout' => 'layout'],
            ],
        ]);

        $this->assertSame('[a][b][c]', $result);
        $this->assertSame(3, $mustache->getPartialLoadCount('layout'));
    }
}

class CountingPartialLoadEngine extends Engine
{
    private $partialLoadCounts = [];

    public function loadPartial($name, $strict = false)
    {
        if (!isset($this->partialLoadCounts[$name])) {
            $this->partialLoadCounts[$name] = 0;
        }

        $this->partialLoadCounts[$name]++;

        return parent::loadPartial($name, $strict);
    }

    public function getPartialLoadCount($name)
    {
        return isset($this->partialLoadCounts[$name]) ? $this->partialLoadCounts[$name] : 0;
    }
}
