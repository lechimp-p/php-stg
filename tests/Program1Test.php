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

class Program1Test extends PHPUnit_Framework_TestCase {
    protected function echo_program($program) {
        echo "\n\n-------- PROGRAM --------\n\n";
        $prg = split("\n", $program);
        foreach($prg as $no => $line) {
            echo sprintf("%3d", $no).": $line\n"; 
        } 
    }

    public function test_program() {
        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} -> swapAB a
         * a = \{} \n \{} -> A
         * swapAB = \{} \n \{a} -> 
         *     case a of
         *         A -> B
         *         B -> A   
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
                            ( new AlgebraicAlternative
                                ( "A"
                                , new Constructor("B", array())
                                )
                            , new AlgebraicAlternative
                                ( "B"
                                , new Constructor("A", array())
                                )
                            )
                        )
                    , false
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new TheMachine();
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
