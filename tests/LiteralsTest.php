<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class LiteralsTest extends ProgramTestBase {
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{swap12, a} \u \{} -> swap12 a
         * a = \{} \n \{} -> 1
         * swap12 = \{} \n \{a} -> 
         *     case a of
         *         1 -> 2
         *         2 -> 1   
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array($l->variable("swap12"), $l->variable("a"))
                    , array()
                    , $l->application 
                        ( $l->variable("swap12")
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
                    , $l->literal(1)
                    , true
                    )
                )
            , $l->binding
                ( $l->variable("swap12")
                , $l->lambda
                    ( array()
                    , array($l->variable("a"))
                    , $l->case_expr
                        ( $l->application
                            ( $l->variable("a")
                            , array()
                            )
                        , array
                            ( $l->primitive_alternative
                                ( $l->literal(1)
                                , $l->literal(2)
                                )
                            , $l->primitive_alternative
                                ( $l->literal(2)
                                , $l->literal(1)
                                )
                            )
                        )
                    , false
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "LiteralsTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new LiteralsTest\TheMachine();
        $this->result = null;
        $machine->push_return(array
            ( 1 => new CodeLabel($this, "returns_1")
            , 2 => new CodeLabel($this, "returns_2")
            ));
        $machine->run();
        $this->assertEquals(2, $this->result);
    }

    public function returns_1($_) {
        $this->result = 1;
    }

    public function returns_2($_) {
        $this->result = 2;
    }
}
