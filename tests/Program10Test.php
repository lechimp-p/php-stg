<?php

use Lechimp\STG\Lang\Program;
use Lechimp\STG\Lang\Binding;
use Lechimp\STG\Lang\Variable;
use Lechimp\STG\Lang\Lambda;
use Lechimp\STG\Lang\PrimOp;
use Lechimp\STG\Lang\Literal;
use Lechimp\STG\Lang\Application;
use Lechimp\STG\Lang\Constructor;
use Lechimp\STG\Lang\CaseExpr;
use Lechimp\STG\Lang\AlgebraicAlternative;
use Lechimp\STG\Lang\DefaultAlternative;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class Program10Test extends ProgramTestBase {
    public function test_program() {
        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} -> swapAB a
         * a = \{} \n \{} -> A
         * swapAB = \{} \n \{a} -> 
         *     case a of
         *         b -> b
         */
        $program = new Program(array
            ( new Binding
                ( new Variable("main")
                , new Lambda
                    ( array(new Variable("swapAB"), new Variable("a"))
                    , array()
                    , new Application 
                        ( new Variable("swapAB")
                        , array
                            ( new Variable("a") 
                            )
                        )
                    , true
                    )
                )
            , new Binding
                ( new Variable("a")
                , new Lambda
                    ( array()
                    , array()
                    , new Constructor("A", array())
                    , true
                    )
                )
            , new Binding
                ( new Variable("swapAB")
                , new Lambda
                    ( array()
                    , array(new Variable("a"))
                    , new CaseExpr
                        ( new Application
                            ( new Variable("a")
                            , array()
                            )
                        , array
                            ( new DefaultAlternative 
                                ( new Variable("b")
                                , new Application
                                    ( new Variable("b")
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
        $compiled = $compiler->compile($program, "TheMachine", "Program10"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new Program10\TheMachine();
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
