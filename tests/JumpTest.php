<?php

class Jumps
{
    public function one($_)
    {
        return 1;
    }
    public function two($_)
    {
        return 2;
    }
}

use Lechimp\STG\CodeLabel;

class JumpTest extends PHPUnit_Framework_TestCase
{
    public function test_jump1()
    {
        $jumps = new Jumps();
        $label = new CodeLabel($jumps, "one");
        $res = $label->jump(null);
        $this->assertEquals(1, $res);
    }

    public function test_jump2()
    {
        $jumps = new Jumps();
        $label = new CodeLabel($jumps, "two");
        $res = $label->jump(null);
        $this->assertEquals(2, $res);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_noUnknownMethod()
    {
        $jumps = new Jumps();
        $label = new CodeLabel($jumps, "three");
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_noNoneObject()
    {
        $label = new CodeLabel("foo", "three");
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_noNull()
    {
        $label = new CodeLabel(null, "three");
    }
}
