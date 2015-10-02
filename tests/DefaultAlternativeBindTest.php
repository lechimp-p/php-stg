<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class DefaultAlternativeBindTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} -> swapAB a
         * a = \{} \n \{} -> A
         * swapAB = \{} \n \{a} -> 
         *     case a of
         *         b -> b
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array($l->variable("swapAB"), $l->variable("a"))
                    , array()
                    , $l->application 
                        ( $l->variable("swapAB")
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
                    , $l->constructor("A", array())
                    , false 
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
                            ( $l->default_alternative 
                                ( $l->variable("b")
                                , $l->application
                                    ( $l->variable("b")
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
        $compiled = $compiler->compile($program, "TheMachine", "DefaultAlternativeBindTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new DefaultAlternativeBindTest\TheMachine();
        $this->result = null;
        $machine->push_return(array
            ( "A" => new CodeLabel($this, "returns_A")
            , "B" => new CodeLabel($this, "returns_B")
            , "C" => new CodeLabel($this, "returns_C")
            ));
        $machine->run();
        $this->assertEquals("A", $this->result);
    }

    public function returns_A($_) {
        $this->result = "A";
    }

    public function returns_B($_) {
        $this->result = "B";
    }

    public function returns_C($_) {
        $this->result = "C";
    }
}
