<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class LetTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{} \u \{} -> 
         *      let a = \{} \n \{} -> A
         *          swapAB = \{} \n \{a} -> 
         *              case a of
         *                  A -> B
         *                  B -> A   
         *      in swapAB a
         */
        $program = $l->prg(array
            ( "main" => $l->lam_n
                ( $l->lt( array
                    ( "a" => $l->lam_n
                            ( $l->con("A")
                            )
                    , "swapAB" => $l->lam_a
                            ( array("a")
                            , $l->cse
                                ( $l->app("a")
                                , array
                                    ( "A" => $l->con("B")
                                    , "B" => $l->con("A")
                                    )
                                )
                            , false
                            )
                    )
                    , $l->app("swapAB", "a")
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "LetTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new LetTest\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals("B", $result[1]);
    }
}
