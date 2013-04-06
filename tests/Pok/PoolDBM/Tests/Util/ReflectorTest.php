<?php

namespace Pok\PoolDBM\Tests\Util;

use Pok\PoolDBM\Util\Reflector;

class ReflectorTest extends \PHPUnit_Framework_TestCase
{
    public function assertReflFunctionEquals($expected, $name)
    {
        $refl = new \ReflectionFunction(__NAMESPACE__ . '\\' . $name);
        $this->assertEquals($expected, Reflector::parameters($refl->getParameters()));
    }

    public function testParameters()
    {
        $this->assertReflFunctionEquals(array('$a','$b','$c'), 'foo');

        $this->assertReflFunctionEquals(array('$a','&$b','$c'), 'bar');

        $this->assertReflFunctionEquals(array('$a','$b = 1','$c = null'), 'baz');

        $this->assertReflFunctionEquals(array('$a = \'hello \\\'you\\\'\'','$b = false'), 'guy');

        $this->assertReflFunctionEquals(array(), 'abc');
    }
}

function foo($a, $b, $c) { }
function bar(\Exception $a, &$b, $c) { }
function baz(\ReflectionFunction $a, $b = 1, $c = null) { }
function guy($a = 'hello \'you\'', $b = false) { }
function abc() { }
