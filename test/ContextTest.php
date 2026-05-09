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
use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\UnknownVariableException;

class ContextTest extends TestCase
{
    public function testConstructor()
    {
        $one = new Context();
        $this->assertSame('', $one->find('foo'));
        $this->assertSame('', $one->find('bar'));

        $two = new Context([
            'foo' => 'FOO',
            'bar' => '<BAR>',
        ]);
        $this->assertSame('FOO', $two->find('foo'));
        $this->assertSame('<BAR>', $two->find('bar'));

        $obj = new \StdClass();
        $obj->name = 'NAME';
        $three = new Context($obj);
        $this->assertSame($obj, $three->last());
        $this->assertSame('NAME', $three->find('name'));
    }

    public function testPushPopAndLast()
    {
        $context = new Context();
        $this->assertFalse($context->last());

        $dummy = new TestDummy();
        $context->push($dummy);
        $this->assertSame($dummy, $context->last());
        $this->assertSame($dummy, $context->pop());
        $this->assertFalse($context->last());

        $obj = new \StdClass();
        $context->push($dummy);
        $this->assertSame($dummy, $context->last());
        $context->push($obj);
        $this->assertSame($obj, $context->last());
        $this->assertSame($obj, $context->pop());
        $this->assertSame($dummy, $context->pop());
        $this->assertFalse($context->last());
    }

    public function testFind()
    {
        $context = new Context();

        $dummy = new TestDummy();

        $obj = new \StdClass();
        $obj->name = 'obj';

        $arr = [
            'a' => ['b' => ['c' => 'see']],
            'b' => 'bee',
        ];

        $string = 'some arbitrary string';

        $context->push($dummy);
        $this->assertSame('dummy', $context->find('name'));

        $context->push($obj);
        $this->assertSame('obj', $context->find('name'));

        $context->pop();
        $this->assertSame('dummy', $context->find('name'));

        $dummy->name = 'dummyer';
        $this->assertSame('dummyer', $context->find('name'));

        $context->push($arr);
        $this->assertSame('bee', $context->find('b'));
        $this->assertSame('see', $context->findDot('a.b.c'));

        $dummy->name = 'dummy';

        $context->push($string);
        $this->assertSame($string, $context->last());
        $this->assertSame('dummy', $context->find('name'));
        $this->assertSame('see', $context->findDot('a.b.c'));
        $this->assertSame('<foo>', $context->find('foo'));
        $this->assertSame('<bar>', $context->findDot('bar'));
    }

    public function testArrayAccessFind()
    {
        $access = new TestArrayAccess([
            'a' => ['b' => ['c' => 'see']],
            'b' => 'bee',
        ]);

        $context = new Context($access);
        $this->assertSame('bee', $context->find('b'));
        $this->assertSame('see', $context->findDot('a.b.c'));
        $this->assertEquals(null, $context->findDot('a.b.c.d'));
    }

    public function testAccessorPriority()
    {
        $context = new Context(new AllTheThings());

        $this->assertSame('win', $context->find('foo'), 'method beats property');
        $this->assertSame('win', $context->find('bar'), 'property beats ArrayAccess');
        $this->assertSame('win', $context->find('baz'), 'ArrayAccess stands alone');
        $this->assertSame('win', $context->find('qux'), 'ArrayAccess beats private property');
    }

    public function testNonPublicMethodsDoNotBlockPropertyLookup()
    {
        $context = new Context(new NonPublicMethodAndProperty());

        $this->assertSame('win', $context->find('privateValue'));
        $this->assertSame('win', $context->find('protectedValue'));
    }

    public function testNonPublicMethodsDoNotBlockMagicPropertyLookup()
    {
        $context = new Context(new NonPublicMethodAndMagicProperty());

        $this->assertSame('win', $context->find('privateValue'));
        $this->assertSame('win', $context->find('protectedValue'));
    }

    public function testNonPublicMethodsDoNotBlockArrayAccessLookup()
    {
        $context = new Context(new NonPublicMethodAndArrayAccess());

        $this->assertSame('win', $context->find('privateValue'));
        $this->assertSame('win', $context->find('protectedValue'));
    }

    public function testNonPublicMethodsAreTreatedAsUnknownVariables()
    {
        $context = new Context(new NonPublicMethodsOnly());

        $this->assertSame('', $context->find('privateValue'));
        $this->assertSame('', $context->find('protectedValue'));
    }

    public function testNonPublicMethodsAreTreatedAsUnknownVariablesInStrictMode()
    {
        $this->expectException(UnknownVariableException::class);
        $this->expectExceptionMessage('Unknown variable: privateValue');

        $context = new Context(new NonPublicMethodsOnly(), false, Engine::STRICT_INTERPOLATION);
        $context->find('privateValue');
    }

    public function testAnchoredDotNotation()
    {
        $context = new Context();

        $a = [
            'name'   => 'a',
            'number' => 1,
        ];

        $b = [
            'number' => 2,
            'child'  => [
                'name' => 'baby bee',
            ],
        ];

        $c = [
            'name' => 'cee',
        ];

        $context->push($a);
        $this->assertSame('a', $context->find('name'));
        $this->assertSame('', $context->findDot('.name'));
        $this->assertSame('a', $context->findAnchoredDot('.name'));
        $this->assertSame(1, $context->find('number'));
        $this->assertSame('', $context->findDot('.number'));
        $this->assertSame(1, $context->findAnchoredDot('.number'));

        $context->push($b);
        $this->assertSame('a', $context->find('name'));
        $this->assertSame(2, $context->find('number'));
        $this->assertSame('', $context->findDot('.name'));
        $this->assertSame('', $context->findDot('.number'));
        $this->assertSame('', $context->findAnchoredDot('.name'));
        $this->assertSame(2, $context->findAnchoredDot('.number'));
        $this->assertSame('baby bee', $context->findDot('child.name'));
        $this->assertSame('', $context->findDot('.child.name'));
        $this->assertSame('baby bee', $context->findAnchoredDot('.child.name'));

        $context->push($c);
        $this->assertSame('cee', $context->find('name'));
        $this->assertSame('', $context->findDot('.name'));
        $this->assertSame('cee', $context->findAnchoredDot('.name'));
        $this->assertSame(2, $context->find('number'));
        $this->assertSame('', $context->findDot('.number'));
        $this->assertSame('', $context->findAnchoredDot('.number'));
        $this->assertSame('baby bee', $context->findDot('child.name'));
        $this->assertSame('', $context->findDot('.child.name'));
        $this->assertSame('', $context->findAnchoredDot('.child.name'));
    }

    public function testAnchoredDotNotationThrowsExceptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $context = new Context();
        $context->push(['a' => 1]);
        $context->findAnchoredDot('a');
    }

    public function testUnknownVariableThrowsExceptionWhenStrictInterpolationIsEnabled()
    {
        $this->expectException(UnknownVariableException::class);
        $this->expectExceptionMessage('Unknown variable: b');

        $context = new Context(null, false, Engine::STRICT_INTERPOLATION);
        $context->push(['a' => 1]);
        $context->find('b');
    }

    public function testUnknownDottedVariableThrowsExceptionWhenStrictInterpolationIsEnabled()
    {
        $this->expectException(UnknownVariableException::class);
        $this->expectExceptionMessage('Unknown variable: c');

        $context = new Context(null, false, Engine::STRICT_INTERPOLATION);
        $context->push(['a' => ['b' => 1]]);
        $context->findDot('a.c');
    }

    public function testUnknownAnchoredDottedVariableThrowsExceptionWhenStrictInterpolationIsEnabled()
    {
        $this->expectException(UnknownVariableException::class);
        $this->expectExceptionMessage('Unknown variable: c');

        $context = new Context(null, false, Engine::STRICT_INTERPOLATION);
        $context->push(['a' => ['b' => 1]]);
        $context->findAnchoredDot('.a.c');
    }

    public function testNullArrayValueMasking()
    {
        $context = new Context();

        $a = [
            'name' => 'not null',
        ];
        $b = [
            'name' => null,
        ];

        $context->push($a);
        $context->push($b);

        $this->assertNull($context->find('name'));
    }

    public function testNullPropertyValueMasking()
    {
        $context = new Context();

        $a = (object) [
            'name' => 'not null',
        ];
        $b = (object) [
            'name' => null,
        ];

        $context->push($a);
        $context->push($b);

        $this->assertNull($context->find('name'));
    }

    public function testBuggyNullPropertyValueMasking()
    {
        $context = new Context(null, true);

        $a = (object) [
            'name' => 'not null',
        ];
        $b = (object) [
            'name' => null,
        ];

        $context->push($a);
        $context->push($b);

        $this->assertSame($context->find('name'), 'not null');
    }
}

class TestDummy
{
    public $name = 'dummy';

    public function __invoke()
    {
        // nothing
    }

    public static function foo()
    {
        return '<foo>';
    }

    public function bar()
    {
        return '<bar>';
    }
}

class TestArrayAccess implements \ArrayAccess
{
    private $container = [];

    public function __construct($array)
    {
        foreach ($array as $key => $value) {
            $this->container[$key] = $value;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
}

class AllTheThings implements \ArrayAccess
{
    public $foo  = 'fail';
    public $bar  = 'win';
    private $qux = 'fail';

    public function foo()
    {
        return 'win';
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        switch ($offset) {
            case 'foo':
            case 'bar':
                return 'fail';

            case 'baz':
            case 'qux':
                return 'win';

            default:
                return 'lolwhut';
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        // nada
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        // nada
    }
}

class NonPublicMethodAndProperty
{
    public $privateValue = 'win';
    public $protectedValue = 'win';

    private function privateValue()
    {
        return 'fail';
    }

    protected function protectedValue()
    {
        return 'fail';
    }
}

class NonPublicMethodAndMagicProperty
{
    private $values = [
        'privateValue'   => 'win',
        'protectedValue' => 'win',
    ];

    public function __isset($name)
    {
        return isset($this->values[$name]);
    }

    public function __get($name)
    {
        return $this->values[$name];
    }

    private function privateValue()
    {
        return 'fail';
    }

    protected function protectedValue()
    {
        return 'fail';
    }
}

class NonPublicMethodAndArrayAccess implements \ArrayAccess
{
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $offset === 'privateValue' || $offset === 'protectedValue';
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return 'win';
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        // nada
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        // nada
    }

    private function privateValue()
    {
        return 'fail';
    }

    protected function protectedValue()
    {
        return 'fail';
    }
}

class NonPublicMethodsOnly
{
    private function privateValue()
    {
        return 'fail';
    }

    protected function protectedValue()
    {
        return 'fail';
    }
}
