<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class LetWithFreeVariablesTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{a, extract} \u \{} -> 
         *      let result = \{a, extract} \u \{} -> extract a 
         *      in result 
         * a = \{} \n \{} -> Wrapped 42 23
         * extract = \{} \n \{w} -> 
         *     case w of
         *         Wrapped a b -> Result a b
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array
                        ( $l->variable("a")
                        , $l->variable("extract")
                        )
                    , array()
                    , $l->let
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
                            )
                        , $l->application 
                            ( $l->variable("result")
                            , array()
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
 
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "LetWithFreeVariablesTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new LetWithFreeVariablesTest\TheMachine();
        $this->result = null;
        $machine->push_return(array
            ( "Result"  => new CodeLabel($this, "returns_result")
            ));
        $machine->run();
        $this->assertEquals(array(42, 23), $this->result);
    }

    public function returns_result($stg) {
        $this->result = $stg->pop_argument_register();
    }
}
