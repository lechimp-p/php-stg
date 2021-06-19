<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler\Compiler;

require_once(__DIR__ . "/ProgramTestBase.php");

abstract class OneProgramTestBase extends ProgramTestBase
{
    public function setUp()
    {
        $this->echo_program = false;
    }

    public function test_program()
    {
        $cls = get_class($this);
        $l = new Lang();
        $program = $this->program($l);
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", $cls);
        if ($this->echo_program) {
            $this->echo_program($compiled["main.php"]);
        }
        eval($compiled["main.php"]);
        $m_cls = "$cls\TheMachine";
        $machine = new $m_cls;
        $result = $this->machine_result($machine);
        $this->assertions($result);
    }

    abstract protected function program(Lang $l);
    abstract protected function assertions($result);
}
