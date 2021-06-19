<?php

use Lechimp\STG\CodeLabel;

class ProgramTestBase extends PHPUnit_Framework_TestCase
{
    protected function echo_program($program)
    {
        echo "\n\n-------- PROGRAM --------\n\n";
        $prg = split("\n", $program);
        foreach ($prg as $no => $line) {
            echo sprintf("%3d", $no + 1) . ": $line\n";
        }
    }

    protected function machine_result(Lechimp\STG\STG $machine)
    {
        $this->result = null;
        $machine->push_b_stack(new CodeLabel($this, "catch_result"));
        $machine->run();
        return $this->result;
    }

    public function catch_result(Lechimp\STG\STG $stg)
    {
        $this->result = $stg->pop_register();
    }
}
