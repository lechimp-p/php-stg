<?php

namespace Lechimp\STG\Test;

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

class JumpTest extends \PHPUnit\Framework\TestCase
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

    public function test_noUnknownMethod()
    {
        $this->expectException(\InvalidArgumentException::class);

        $jumps = new Jumps();
        $label = new CodeLabel($jumps, "three");
    }

    public function test_noNoneObject()
    {
        $this->expectException(\InvalidArgumentException::class);

        $label = new CodeLabel("foo", "three");
    }

    public function test_noNull()
    {
        $this->expectException(\InvalidArgumentException::class);

        $label = new CodeLabel(null, "three");
    }
}
