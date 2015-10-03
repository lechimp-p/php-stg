<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class DuplicateVarNameTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} -> 
         *           let b = \{a} \u \{} -> a
         *               a = \{swapAB} \u \{c} -> swapAB c
         *           in a b
         * a = \{} \n \{} -> A
         * swapAB = \{} \n \{a} -> 
         *     case a of
         *         A -> B
         *         B -> A   
         *         default -> a   
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array($l->variable("swapAB"), $l->variable("a"))
                    , array()
                    , $l->let
                        ( array
                            ( $l->binding
                                ( $l->variable("b")
                                , $l->lambda
                                    ( array($l->variable("a"))
                                    , array()
                                    , $l->application 
                                        ( $l->variable("a")
                                        , array ()
                                        )
                                    , true
                                    )
                                )
                            , $l->binding
                                ( $l->variable("a")
                                , $l->lambda
                                    ( array($l->variable("swapAB"))
                                    , array($l->variable("c"))
                                    , $l->application
                                        ( $l->variable("swapAB")
                                        , array
                                            ( $l->variable("c")
                                            )
                                        )
                                    , true
                                    )
                                )
                            )
                        , $l->application 
                            ( $l->variable("a")
                            , array
                                ( $l->variable("b") 
                                )
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
                            , $l->default_alternative 
                                ( null
                                , $l->application
                                    ( $l->variable("a")
                                    , array()
                                    )
                                )
                            )
                        )
                    , false
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "DuplicateVarNameTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new DuplicateVarNameTest\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals("B", $result[1]);
    }
}
