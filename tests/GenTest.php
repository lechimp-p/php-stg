<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Gen\GClass;
use Lechimp\STG\Gen\GPrivateProperty;
use Lechimp\STG\Gen\GProtectedProperty;
use Lechimp\STG\Gen\GPublicProperty;
use Lechimp\STG\Gen\GPrivateMethod;
use Lechimp\STG\Gen\GProtectedMethod;
use Lechimp\STG\Gen\GPublicMethod;
use Lechimp\STG\Gen\GArgument;
use Lechimp\STG\Gen\GStatement;
use Lechimp\STG\Gen\GIfThenElse;

class GenTest extends \PHPUnit\Framework\TestCase
{
    protected function assertCodeEquals($left, $right)
    {
        $_left = $this->removeEmptyLines(explode("\n", $left));
        $_right = $this->removeEmptyLines(explode("\n", $right));
        $this->assertEquals($_left, $_right);
    }

    protected function removeEmptyLines(array $lines)
    {
        return array_filter($lines, function ($line) {
            return trim($line) != "";
        });
    }

    public function test_emptyClass()
    {
        $gen = new GClass("Lechimp\\STG", "Test", array(), array());
        $generated = $gen->render(0);
        $expected = <<<'PHP'
namespace Lechimp\STG {

class Test {
}

} // namespace Lechimp\STG
PHP;
        $this->assertCodeEquals($expected, $generated);
    }

    public function test_extendedClass()
    {
        $gen = new GClass("Lechimp\\STG", "Test", array(), array(), "Foo");
        $generated = $gen->render(0);
        $expected = <<<'PHP'
namespace Lechimp\STG {

class Test extends Foo {
}

} // namespace Lechimp\STG
PHP;
        $this->assertCodeEquals($expected, $generated);
    }

    public function test_noNamespaceClass()
    {
        $gen = new GClass("", "Test", array(), array());
        $generated = $gen->render(0);
        $expected = <<<'PHP'
class Test {
}
PHP;
        $this->assertCodeEquals($expected, $generated);
    }

    public function test_filledClass()
    {
        $gen = new GClass(
            "Lechimp\\STG",
            "Test",
            array( new GPrivateProperty("foo")
                , new GProtectedProperty("bar")
                , new GPublicProperty("baz")
                ),
            array( new GPrivateMethod("get_bar", array(), array())
                , new GProtectedMethod("get_foo", array(), array())
                , new GPublicMethod("__construct", array(), array())
                )
        );
        $generated = $gen->render(0);
        $expected = <<<'PHP'
namespace Lechimp\STG {

class Test {
    private $foo;
    protected $bar;
    public $baz;
    private function get_bar() {
    }
    protected function get_foo() {
    }
    public function __construct() {
    }
}

} // namespace Lechimp\STG
PHP;
        $this->assertCodeEquals($expected, $generated);
    }

    public function test_filledMethod()
    {
        $gen = new GClass(
            "Lechimp\\STG",
            "Test",
            array(),
            array( new GPublicMethod(
                "get_bar",
                array( new GArgument(null, "foo")
                        , new GArgument("array", "bar")
                        , new GArgument(null, "baz", "\"baz\"")
                        ),
                array( new GStatement('echo $foo')
                        )
            )
                , new GPublicMethod(
                    "get_indentation",
                    array(),
                    array( new GStatement(function ($indentation) {
                        return
                            "$indentation\$indentation = \"$indentation\";";
                    })
                        )
                )
                )
        );
        $generated = $gen->render(0);
        $expected = <<<'PHP'
namespace Lechimp\STG {

class Test {
    public function get_bar($foo, array $bar, $baz = "baz") {
        echo $foo;
    }
    public function get_indentation() {
        $indentation = "        ";
    }
}

} // namespace Lechimp\STG
PHP;
        $this->assertCodeEquals($expected, $generated);
    }

    public function test_ifThenElse()
    {
        $gen = new GIfThenElse(
            "\$a == \$b",
            array( new GStatement("echo 'equal: '")
                , new GStatement("echo \$b")
                ),
            array( new GStatement("echo 'not equal: '")
                , new GStatement("echo \$a.\" \".\$b")
                )
        );
        $generated = $gen->render(0);
        $expected = <<<'PHP'
if ($a == $b) {
    echo 'equal: ';
    echo $b;
}
else {
    echo 'not equal: ';
    echo $a." ".$b;
}
PHP;
        $this->assertCodeEquals($expected, $generated);
    }

    public function test_ifThenElse2()
    {
        $gen = new GIfThenElse(
            "\$a == \$b",
            array( new GStatement("echo 'equal: '")
                , new GStatement("echo \$b")
                ),
            array( new GIfThenElse(
                    "!is_null(\$a)",
                    array( new GStatement("echo 'not equal: '")
                        , new GStatement("echo \$a.\" \".\$b")
                        ),
                    array( new GStatement("echo 'null'")
                        )
                )
                )
        );
        $generated = $gen->render(0);
        $expected = <<<'PHP'
if ($a == $b) {
    echo 'equal: ';
    echo $b;
}
else {
    if (!is_null($a)) {
        echo 'not equal: ';
        echo $a." ".$b;
    }
    else {
        echo 'null';
    }
}
PHP;
        $this->assertCodeEquals($expected, $generated);
    }

    public function test_filledMethodIfThenElse()
    {
        $gen = new GClass(
            "Lechimp\\STG",
            "Test",
            array(),
            array( new GPublicMethod(
                "get_bar",
                array( new GArgument(null, "foo")
                        , new GArgument("array", "bar")
                        , new GArgument(null, "baz", "\"baz\"")
                        ),
                array( new GIfThenElse(
                        "\$a == \$b",
                        array( new GStatement("echo 'equal: '")
                                , new GStatement("echo \$b")
                                ),
                        array( new GIfThenElse(
                                    "!is_null(\$a)",
                                    array( new GStatement("echo 'not equal: '")
                                        , new GStatement("echo \$a.\" \".\$b")
                                        ),
                                    array( new GStatement("echo 'null'")
                                        )
                                )
                                )
                    )
                        )
            )
                )
        );
        $generated = $gen->render(0);
        $expected = <<<'PHP'
namespace Lechimp\STG {

class Test {
    public function get_bar($foo, array $bar, $baz = "baz") {
        if ($a == $b) {
            echo 'equal: ';
            echo $b;
        }
        else {
            if (!is_null($a)) {
                echo 'not equal: ';
                echo $a." ".$b;
            }
            else {
                echo 'null';
            }
        }
    }
}

} // namespace Lechimp\STG
PHP;
        $this->assertCodeEquals($expected, $generated);
    }
}
