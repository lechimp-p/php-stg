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
use Lechimp\STG\Lang\LetBinding;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class DuplicateVarNameTest extends ProgramTestBase {
    public function test_program() {
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
        $program = new Program(array
            ( new Binding
                ( new Variable("main")
                , new Lambda
                    ( array(new Variable("swapAB"), new Variable("a"))
                    , array()
                    , new LetBinding
                        ( array
                            ( new Binding
                                ( new Variable("b")
                                , new Lambda
                                    ( array(new Variable("a"))
                                    , array()
                                    , new Application 
                                        ( new Variable("a")
                                        , array ()
                                        )
                                    , true
                                    )
                                )
                            , new Binding
                                ( new Variable("a")
                                , new Lambda
                                    ( array(new Variable("swapAB"))
                                    , array(new Variable("c"))
                                    , new Application
                                        ( new Variable("swapAB")
                                        , array
                                            ( new Variable("c")
                                            )
                                        )
                                    , true
                                    )
                                )
                            )
                        , new Application 
                            ( new Variable("a")
                            , array
                                ( new Variable("b") 
                                )
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
                            ( new AlgebraicAlternative
                                ( "A"
                                , array()
                                , new Constructor("B", array())
                                )
                            , new AlgebraicAlternative
                                ( "B"
                                , array()
                                , new Constructor("A", array())
                                )
                            , new DefaultAlternative 
                                ( null
                                , new Application
                                    ( new Variable("a")
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
        $this->result = null;
        $machine->push_return(array
            ( "A" => new CodeLabel($this, "returns_A")
            , "B" => new CodeLabel($this, "returns_B")
            ));
        $machine->run();
        $this->assertEquals("B", $this->result);
    }

    public function returns_A($_) {
        $this->result = "A";
    }

    public function returns_B($_) {
        $this->result = "B";
    }
}