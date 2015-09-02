<?php

use Lechimp\STG\Lang\Program;
use Lechimp\STG\Lang\Binding;
use Lechimp\STG\Lang\Variable;
use Lechimp\STG\Lang\Lambda;
use Lechimp\STG\Lang\PrimOp;
use Lechimp\STG\Lang\Literal;
use Lechimp\STG\Lang\LetBinding;
use Lechimp\STG\Lang\Constructor;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class IntegerPrimOpsTest extends ProgramTestBase {
    public function test_add() {
        /**
         * Represents the following program
         * main = 
         *     let v = \{} \u \{} -> 42# +# 23#
         *     in \{} \u \{} -> Result v
         */
        $program = new Program(array
            ( new Binding
                ( new Variable("main")
                , new Lambda
                    ( array()
                    , array()
                    , new LetBinding
                        ( array
                            ( new Binding
                                ( new Variable("v")
                                , new Lambda
                                    ( array()
                                    , array()
                                    , new PrimOp
                                        ( "IntAddOp"
                                        , array
                                            ( new Literal(42)
                                            , new Literal(23)
                                            )
                                        )
                                    , true
                                    )  
                                )
                            )
                        , new Constructor
                            ( "Result"
                            , array (new Variable("v"))
                            )
                        )
                    , true
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "IntegerPrimOpsTestAdd"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new IntegerPrimOpsTestAdd\TheMachine();
        $this->result = null;
        $machine->push_return(array
            ( "Result" => new CodeLabel($this, "returns_result")
            ));
        $machine->run();
        $this->assertEquals(array(42 + 23), $this->result);
    }

    public function test_sub() {
        /**
         * Represents the following program
         * main = 
         *     let v = \{} \u \{} -> 42# -# 23#
         *     in \{} \u \{} -> Result v
         */
        $program = new Program(array
            ( new Binding
                ( new Variable("main")
                , new Lambda
                    ( array()
                    , array()
                    , new LetBinding
                        ( array
                            ( new Binding
                                ( new Variable("v")
                                , new Lambda
                                    ( array()
                                    , array()
                                    , new PrimOp
                                        ( "IntSubOp"
                                        , array
                                            ( new Literal(42)
                                            , new Literal(23)
                                            )
                                        )
                                    , true
                                    )  
                                )
                            )
                        , new Constructor
                            ( "Result"
                            , array (new Variable("v"))
                            )
                        )
                    , true
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "IntegerPrimOpsTestSub"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new IntegerPrimOpsTestSub\TheMachine();
        $this->result = null;
        $machine->push_return(array
            ( "Result" => new CodeLabel($this, "returns_result")
            ));
        $machine->run();
        $this->assertEquals(array(42 - 23), $this->result);
    }

    public function test_mul() {
        /**
         * Represents the following program
         * main = 
         *     let v = \{} \u \{} -> 42# *# 23#
         *     in \{} \u \{} -> Result v
         */
        $program = new Program(array
            ( new Binding
                ( new Variable("main")
                , new Lambda
                    ( array()
                    , array()
                    , new LetBinding
                        ( array
                            ( new Binding
                                ( new Variable("v")
                                , new Lambda
                                    ( array()
                                    , array()
                                    , new PrimOp
                                        ( "IntMulOp"
                                        , array
                                            ( new Literal(42)
                                            , new Literal(23)
                                            )
                                        )
                                    , true
                                    )  
                                )
                            )
                        , new Constructor
                            ( "Result"
                            , array (new Variable("v"))
                            )
                        )
                    , true
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "IntegerPrimOpsTestMul"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new IntegerPrimOpsTestMul\TheMachine();
        $this->result = null;
        $machine->push_return(array
            ( "Result" => new CodeLabel($this, "returns_result")
            ));
        $machine->run();
        $this->assertEquals(array(42 * 23), $this->result);
    }


    public function returns_result($stg) {
        $this->result = $stg->pop_return();
    }
}
