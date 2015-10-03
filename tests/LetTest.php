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
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array()
                    , array()
                    , $l->let
                        ( array
                            ( $l->binding
                                ( $l->variable("a")
                                , $l->lambda
                                    ( array()
                                    , array()
                                    , $l->constructor("A", array())
                                    , true
                                    )
                                )
                                , $l->binding
                                    ( $l->variable("swapAB")
                                    , $l->lambda
                                        ( array()
                                        , array($l->variable("a"))
                                        , $l->case_expr
                                            ( $l->application
                                                ( $l->variable("a")
                                                , array()
                                                )
                                            , array
                                                ( $l->algebraic_alternative
                                                    ( "A"
                                                    , array()
                                                    , $l->constructor("B", array())
                                                    )
                                                , $l->algebraic_alternative
                                                    ( "B"
                                                    , array()
                                                    , $l->constructor("A", array())
                                                    )
                                                )
                                            )
                                        , false
                                        )
                                    )
                                )
                        , $l->application 
                            ( $l->variable("swapAB")
                            , array
                                ( $l->variable("a") 
                                )
                            )
                        )
                    , true
                    )
                )
            , 

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
