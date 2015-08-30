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

class Program2Test extends ProgramTestBase {
    public function test_program() {
        /**
         * Represents the following program
         * main = \{extract, a} \u \{} -> extract a
         * a = \{} \n \{} -> Wrapped 42
         * extract = \{} \n \{w} -> 
         *     case w of
         *         Wrapped v -> v
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
                        , array( new Literal(42))
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
                                , new Application
                                    ( new Variable("w")
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
        $compiled = $compiler->compile($program, "TheMachine", "Program2"); 
        $this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new Program2\TheMachine();
        $this->result = null;
        $machine->push_return(array
            ( 42   => new CodeLabel($this, "returns_42")
            , null => new CodeLabel($this, "returns_other")
            ));
        $machine->run();
        $this->assertEquals(42, $this->result);
    }

    public function returns_42($_) {
        $this->result = 42;
    }

    public function returns_other($_) {
        $this->result = null;
    }
}
