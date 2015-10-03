<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class NestedCaseTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{extract, a} \u \{} -> extract a
         * a = \{} \n \{} -> 
         *      let w = \{} \u \{} -> Wrapped 42
         *      in Wrapped w
         * extract = \{} \n \{w} -> 
         *     case (case w of
         *              Wrapped a -> a
         *          )
         *     of
         *         Wrapped a -> Result a
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array($l->variable("extract"), $l->variable("a"))
                    , array()
                    , $l->application 
                        ( $l->variable("extract")
                        , array
                            ( $l->variable("a") 
                            )
                        )
                    , true
                    )
                )
            , $l->binding
                ( $l->variable("a")
                , $l->lambda
                    ( array()
                    , array()
                    , $l->let
                        ( array
                            ( $l->binding
                                ( $l->variable("w")
                                , $l->lambda
                                    ( array()
                                    , array()
                                    , $l->constructor
                                        ( "Wrapped"
                                        , array
                                            ( $l->literal(42)
                                            )
                                        )
                                    , true
                                    )
                                )
                            )
                        , $l->constructor
                            ( "Wrapped"
                            , array
                                ( $l->variable("w") 
                                )
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
                        ( $l->case_expr
                            ( $l->application
                                ( $l->variable("w")
                                , array()
                                )
                            , array
                                ( $l->algebraic_alternative
                                    ( "Wrapped"
                                    , array
                                        ( $l->variable("a") 
                                        )
                                    , $l->application
                                        ( $l->variable("a") 
                                        , array()
                                        )
                                    )
                                )
                            )
                        , array
                            ( $l->algebraic_alternative
                                ( "Wrapped"
                                , array
                                    ( $l->variable("a") 
                                    )
                                , $l->constructor
                                    ( "Result" 
                                    , array
                                        ( $l->variable("a") 
                                        )
                                    )
                                )
                            )
                        )
                    , false
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "NestedCaseTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new NestedCaseTest\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals(42, $result[2]);
    }
}
