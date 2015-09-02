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
use Lechimp\STG\Lang\PrimitiveAlternative;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class LiteralsTest extends ProgramTestBase {
    public function test_program() {
        /**
         * Represents the following program
         * main = \{swap12, a} \u \{} -> swap12 a
         * a = \{} \n \{} -> 1
         * swap12 = \{} \n \{a} -> 
         *     case a of
         *         1 -> 2
         *         2 -> 1   
         */
        $program = new Program(array
            ( new Binding
                ( new Variable("main")
                , new Lambda
                    ( array(new Variable("swap12"), new Variable("a"))
                    , array()
                    , new Application 
                        ( new Variable("swap12")
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
                    , new Literal(1)
                    , true
                    )
                )
            , new Binding
                ( new Variable("swap12")
                , new Lambda
                    ( array()
                    , array(new Variable("a"))
                    , new CaseExpr
                        ( new Application
                            ( new Variable("a")
                            , array()
                            )
                        , array
                            ( new PrimitiveAlternative
                                ( new Literal(1)
                                , new Literal(2)
                                )
                            , new PrimitiveAlternative
                                ( new Literal(2)
                                , new Literal(1)
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
