<?php

use Lechimp\STG\Gen\GClass;
use Lechimp\STG\Gen\GPrivateProperty;
use Lechimp\STG\Gen\GProtectedProperty;
use Lechimp\STG\Gen\GPublicProperty;
use Lechimp\STG\Gen\GPrivateMethod;
use Lechimp\STG\Gen\GProtectedMethod;
use Lechimp\STG\Gen\GPublicMethod;

class GenText extends PHPUnit_Framework_TestCase {
    protected function assertCodeEquals($left, $right) {
        $_left = $this->removeEmptyLines(split("\n", $left));
        $_right = $this->removeEmptyLines(split("\n", $right));
        $this->assertEquals($_left, $_right);
    }

    protected function removeEmptyLines(array $lines) {
        return array_filter($lines, function($line) {
            return trim($line) != "";
        });
    }

    public function test_emptyClass() {
        $gen = new GClass("Lechimp\\STG", "Test", array(), array());
        $generated = $gen->render(0);
        $expected = <<<'PHP'
class Lechimp\\STG\\Test {
}
PHP;
        $this->assertCodeEquals($generated, $expected);
    }

    public function test_filledClass() {
        $gen = new GClass("Lechimp\\STG", "Test"
            , array
                ( new GPrivateProperty("foo")
                , new GProtectedProperty("bar")
                , new GPublicProperty("baz")
                )
            , array
                ( new GPrivateMethod("get_bar", array(), array())
                , new GProtectedMethod("get_foo", array(), array())
                , new GPublicMethod("__construct", array(), array())
                )
            );
        $generated = $gen->render(0);
        $expected = <<<'PHP'
class Lechimp\\STG\\Test {
    private $foo;
    protected $bar;
    public $baz;
    public function get_bar() {
    } 
    protected function get_foo() {
    } 
    public function __construct() {
    }
}
PHP;
        $this->assertCodeEquals($generated, $expected);
    }
}