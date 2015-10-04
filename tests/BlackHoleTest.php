<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class BlackHoleTest extends ProgramTestBase {
    /**
     * @expectedException Lechimp\STG\Exceptions\BlackHole
     */
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{} \u \{} -> 
         *      letrec a = \{a} \u \{} -> 
         *                  case a of
         *                      b -> b
         *      in a 
         */
        $program = $l->prg(array
            ( "main" => $l->lam_n
                ( $l->ltr( array
                    ( "a" => $l->lam_f
                        ( array("a")
                        , $l->cse
                            ( $l->app("a")
                            , array
                                ( "default b" => $l->app("b")
                                )
                            )
                        )
                    )
                    , $l->app("a")
                    )
                )
            ));

        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "BlackHoleTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new BlackHoleTest\TheMachine();
        $result = $this->machine_result($machine);
    } 
}
