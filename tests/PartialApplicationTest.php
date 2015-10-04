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
         *         v  = \{vt} \u \{}  -> vt 23
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

    public function test_program2() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{} \n \{} ->
         *  letrec tc = \{} \n \{a,b} -> T a b
         *         vt = \{tc} \u \{}  -> tc 42
         *         v1 = \{vt} \u \{}  -> vt 23
         *         v2 = \{vt} \u \{}  -> vt 5 
         *  in case v1 of
         *      T a b -> case v2 of
         *          T c d -> T4 a b c d
         *   
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
                    , "v1" => $l->lam_f
                        ( array("vt")
                        , $l->app("vt", $l->lit(23))
                        )
                    , "v2" => $l->lam_f
                        ( array("vt")
                        , $l->app("vt", $l->lit(5))
                        )
                    )
                    , $l->cse
                        ( $l->app("v1")
                        , array
                            ( "T a b" => $l->cse
                                ( $l->app("v2")
                                , array
                                    ( "T c d" => $l->con("T4", "a", "b", "c", "d") )
                                )
                            )
                        )
                    )
                , false 
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "PartialApplicationTest2"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new PartialApplicationTest2\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals("T4", $result[1]);
        $this->assertEquals(42, $result[2]);
        $this->assertEquals(23, $result[3]);
        $this->assertEquals(42, $result[4]);
        $this->assertEquals(5, $result[5]);
    }
}
