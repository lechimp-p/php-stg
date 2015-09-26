<?php

use Lechimp\STG\Lang\Program;
use Lechimp\STG\Lang\Binding;
use Lechimp\STG\Lang\LetBinding;
use Lechimp\STG\Lang\LetRecBinding;
use Lechimp\STG\Lang\Variable;
use Lechimp\STG\Lang\Lambda;
use Lechimp\STG\Lang\PrimOp;
use Lechimp\STG\Lang\Literal;
use Lechimp\STG\Lang\Application;
use Lechimp\STG\Lang\Constructor;
use Lechimp\STG\Lang\CaseExpr;
use Lechimp\STG\Lang\DefaultAlternative;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class BlackHoleTest extends ProgramTestBase {
    /**
     * @expectedException Lechimp\STG\Exceptions\BlackHole
     */
    public function test_program() {
        /**
         * Represents the following program
         * main = \{} \u \{} -> 
         *      letrec a = \{a} \u \{} -> 
         *                  case a of
         *                      b -> b
         *      in a 
         */
        $program = new Program(array
            ( new Binding
                ( new Variable("main")
                , new Lambda
                    ( array()
                    , array()
                    , new LetRecBinding
                        ( array
                            ( new Binding
                                ( new Variable("a")
                                , new Lambda
                                    ( array( new Variable("a") )
                                    , array()
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
                                    , true
                                    )
                                )
                            )
                        , new Application 
                            ( new Variable("a")
                            , array()
                            )
                        )
                    , true
                    )
                )
            ));

        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "BlackHoleTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new BlackHoleTest\TheMachine();
        $machine->push_return(array
            ( "Undefined" => new CodeLabel($this, "returns_Undefined")
            ));
        $machine->run();
    }

    public function returns_Undefined($_) {
        $this->assertFalse(true);
    } 
}
