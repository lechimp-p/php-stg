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
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array()
                    , array()
                    , $l->letrec(array
                        ( $l->binding
                            ( $l->variable("tc")
                            , $l->lambda
                                ( array()
                                , array
                                    ( $l->variable("a")
                                    , $l->variable("b")
                                    )
                                , $l->constructor
                                    ( "T"
                                    , array
                                        ( $l->variable("a")
                                        , $l->variable("b")
                                        )
                                    )
                                , false 
                                )
                            )
                        , $l->binding
                            ( $l->variable("vt")
                            , $l->lambda
                                ( array($l->variable("tc"))
                                , array()
                                , $l->application
                                    ( $l->variable("tc")
                                    , array($l->literal(42))
                                    )
                                , true 
                                )
                            )
                        , $l->binding
                            ( $l->variable("v")
                            , $l->lambda
                                ( array($l->variable("vt"))
                                , array()
                                , $l->application
                                    ( $l->variable("vt")
                                    , array($l->literal(23))
                                    )
                                , false 
                                )
                            )
                        )
                        , $l->application
                            ( $l->variable("v")
                            , array()
                            )
                        )
                    , false 
                    )
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
