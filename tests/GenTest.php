<?php

use Lechimp\STG\Gen\GClass;

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
}
