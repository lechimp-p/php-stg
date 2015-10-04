<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class ConstructorWithValuesTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{extract, a} \u \{} -> extract a
         * a = \{} \n \{} -> Wrapped 42 23
         * extract = \{} \n \{w} -> 
         *     case w of
         *         Wrapped a b -> Result a b
         */
        $program = $l->prg(array
            ( "main" => $l->lam_f
                    ( array("extract", "a")
                    , $l->app("extract", "a")
                    )
            , "a" => $l->lam_n
                    ( $l->con("Wrapped", $l->lit(42), $l->lit(23))
                    )
            , "extract" => $l->lam_a
                    ( array("w")
                    , $l->cse
                        ( $l->app("w")
                        , array
                            ( "Wrapped a b" => $l->con("Result", "a", "b")
                            )
                        )
                    , false
                    )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "ConstructorWithValuesTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new ConstructorWithValuesTest\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals(42, $result[2]);
        $this->assertEquals(23, $result[3]);
    }
}
