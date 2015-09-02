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
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class ConstructorWithValuesTest extends ProgramTestBase {
    public function test_program() {
        /**
         * Represents the following program
         * main = \{extract, a} \u \{} -> extract a
         * a = \{} \n \{} -> Wrapped 42 23
         * extract = \{} \n \{w} -> 
         *     case w of
         *         Wrapped a b -> Result a b
         */
        $program = new Program(array
            ( new Binding
                ( new Variable("main")
                , new Lambda
                    ( array(new Variable("extract"), new Variable("a"))
                    , array()
                    , new Application 
                        ( new Variable("extract")
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
                    , new Constructor
                        ( "Wrapped"
                        , array
                            ( new Literal(42)
                            , new Literal(23)
                            )
                        )
                    , true
                    )
                )
            , new Binding
                ( new Variable("extract")
                , new Lambda
                    ( array()
                    , array(new Variable("w"))
                    , new CaseExpr
                        ( new Application
                            ( new Variable("w")
                            , array()
                            )
                        , array
                            ( new AlgebraicAlternative
                                ( "Wrapped"
                                , array
                                    ( new Variable("a") 
                                    , new Variable("b") 
                                    )
                                , new Constructor
                                    ( "Result" 
                                    , array
                                        ( new Variable("a") 
                                        , new Variable("b") 
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
        $compiled = $compiler->compile($program, "TheMachine", "ConstructorWithValuesTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new ConstructorWithValuesTest\TheMachine();
        $this->result = null;
        $machine->push_return(array
            ( "Result"  => new CodeLabel($this, "returns_result")
            ));
        $machine->run();
        $this->assertEquals(array(42, 23), $this->result);
    }

    public function returns_result($stg) {
        $this->result = $stg->pop_return();
    }
}