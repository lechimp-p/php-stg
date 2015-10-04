<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class BasicTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} -> swapAB a
         * a = \{} \n \{} -> A
         * swapAB = \{} \n \{a} -> 
         *     case a of
         *         A -> B
         *         B -> A   
         *         default -> a   
         */
        $program = $l->prg(array
            ( "main" => $l->lam
                ( array("swapAB", "a")
                , array()
                , $l->app("swapAB", "a")
                )
            , "a" => $l->lam
                ( array()
                , array()
                , $l->constructor("A", array())
                )
            , "swapAB" => $l->lam
                ( array()
                , array("a")
                , $l->cse
                    ( $l->app("a")
                    , array
                        ( "A" => $l->constructor("B", array())
                        , "B" => $l->constructor("A", array())
                        , ""  => $l->app("a")
                        )
                    )
                , false
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "BasicTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new BasicTest\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals("B", $result[1]);
    }
}
