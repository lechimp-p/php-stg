<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class PartialApplicationTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{} \n \{} ->
         *  letrec tc = \{} \n \{a,b} -> T a b
         *         vt = \{tc} \u \{}  -> tc 42
         *         v  = \{vt} \n \{}  -> vt 23
         *  in v 
         */
        $program = $l->prg(array
            ( "main" => $l->lam_n
                ( $l->ltr(array
                    ( "tc" => $l->lam_a
                        ( array("a", "b")
                        , $l->con("T", "a", "b")
                        , false 
                        )
                    , "vt" => $l->lam_f
                        ( array("tc")
                        , $l->app("tc", $l->lit(42)) 
                        )
                    , "v" => $l->lam_f
                        ( array("vt")
                        , $l->app("vt", $l->lit(23))
                        )
                    )
                    , $l->app("v")
                    )
                , false 
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "PartialApplicationTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new PartialApplicationTest\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals("T", $result[1]);
        $this->assertEquals(42, $result[2]);
        $this->assertEquals(23, $result[3]);
    }

    // TODO: I need a test that uses a partial application closure twice.
}
