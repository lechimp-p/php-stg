<?php

class Jumps {
    static function one($_) {
        return 1;
    }
    static function two($_) {
        return 2;
    }
}

require_once(__DIR__."/../src/STG.php");

class STGTest extends PHPUnit_Framework_TestCase {
    public function test_jump1() {
        $label = code_label("Jumps", "one");
        $res = jump(null, $label);
        $this->assertEquals(1, $res);
    }

    public function test_jump2() {
        $label = code_label("Jumps", "two");
        $res = jump(null, $label);
        $this->assertEquals(2, $res);
    }
}
