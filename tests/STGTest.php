<?php

class Jumps {
    static function one($_) {
        return 1;
    }
    static function two($_) {
        return 2;
    }
}

use Lechimp\STG\STG;

class STGTest extends PHPUnit_Framework_TestCase {
    public function test_jump1() {
        $label = STG::code_label("Jumps", "one");
        $res = STG::jump(null, $label);
        $this->assertEquals(1, $res);
    }

    public function test_jump2() {
        $label = STG::code_label("Jumps", "two");
        $res = STG::jump(null, $label);
        $this->assertEquals(2, $res);
    }
}
