<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class DefaultAlternativeBindTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} -> swapAB a
         * a = \{} \n \{} -> A
         * swapAB = \{} \n \{a} -> 
         *     case a of
         *         b -> b
         */
        $program = $l->prg(array
            ( "main" => $l->lam_f
                ( array("swapAB", "a")
                , $l->app("swapAB", "a")
                )
            , "a" => $l->lam_n
                ( $l->con("A")
                , false 
                )
            , "swapAB" => $l->lam_a
                ( array("a")
                , $l->cse
                    ( $l->app("a")
                    , array
                        ( "default b" => $l->app("b")
                        )
                    )
                , false
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "DefaultAlternativeBindTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new DefaultAlternativeBindTest\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals("A", $result[1]);
    }
}
