<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class LetRecTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{} \u \{} -> 
         *      letrec result = \{a, extract} \u \{} -> extract a 
         *             a = \{} \n \{} -> Wrapped 42 23
         *             extract = \{} \n \{w} -> 
         *                  case w of
         *                      Wrapped a b -> Result a b
         *      in result 
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array()
                    , array()
                    , $l->letrec
                        ( array
                            ( $l->binding
                                ( $l->variable("result")
                                , $l->lambda
                                    ( array
                                        ( $l->variable("a")
                                        , $l->variable("extract")
                                        )
                                    , array()
                                    , $l->application
                                        ( $l->variable("extract")
                                        , array( $l->variable("a") )
                                        )
                                    , true
                                    )
                                )
                            , $l->binding
                                ( $l->variable("a")
                                , $l->lambda
                                    ( array()
                                    , array()
                                    , $l->constructor
                                        ( "Wrapped"
                                        , array
                                            ( $l->literal(42)
                                            , $l->literal(23)
                                            )
                                        )
                                    , true
                                    )
                                )
                            , $l->binding
                                ( $l->variable("extract")
                                , $l->lambda
                                    ( array()
                                    , array($l->variable("w"))
                                    , $l->case_expr
                                        ( $l->application
                                            ( $l->variable("w")
                                            , array()
                                            )
                                        , array
                                            ( $l->algebraic_alternative
                                                ( "Wrapped"
                                                , array
                                                    ( $l->variable("a") 
                                                    , $l->variable("b") 
                                                    )
                                                , $l->constructor
                                                    ( "Result" 
                                                    , array
                                                        ( $l->variable("a") 
                                                        , $l->variable("b") 
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    , false
                                    )
                                )
                            )
                        , $l->application 
                            ( $l->variable("result")
                            , array()
                            )
                        )
                    , true
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "LetRecTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new LetRecTest\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals(42, $result[2]);
        $this->assertEquals(23, $result[3]);
    }
}
