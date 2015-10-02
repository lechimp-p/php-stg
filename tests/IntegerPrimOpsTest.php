<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class IntegerPrimOpsTest extends ProgramTestBase {
    public function test_add() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = 
         *     let v = \{} \u \{} -> 42# +# 23#
         *     in  case v of
         *             a -> Result a
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array()
                    , array()
                    , $l->let
                        ( array
                            ( $l->binding
                                ( $l->variable("v")
                                , $l->lambda
                                    ( array()
                                    , array()
                                    , $l->prim_op
                                        ( "IntAddOp"
                                        , array
                                            ( $l->literal(42)
                                            , $l->literal(23)
                                            )
                                        )
                                    , true
                                    )  
                                )
                            )
                        , $l->case_expr
                            ( $l->application
                                ( $l->variable("v")
                                , array()
                                )
                            , array
                                ( $l->default_alternative
                                    ( $l->variable("a")
                                    , $l->constructor
                                        ( "Result"
                                        , array ($l->variable("a"))
                                        )
                                    )
                                )
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
        $l = new Lang();

        /**
         * Represents the following program
         * main = 
         *     let v = \{} \u \{} -> 42# -# 23#
         *     in  case v of
         *             a -> Result a
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array()
                    , array()
                    , $l->let
                        ( array
                            ( $l->binding
                                ( $l->variable("v")
                                , $l->lambda
                                    ( array()
                                    , array()
                                    , $l->prim_op
                                        ( "IntSubOp"
                                        , array
                                            ( $l->literal(42)
                                            , $l->literal(23)
                                            )
                                        )
                                    , true
                                    )  
                                )
                            )
                        , $l->case_expr
                            ( $l->application
                                ( $l->variable("v")
                                , array()
                                )
                            , array
                                ( $l->default_alternative
                                    ( $l->variable("a")
                                    , $l->constructor
                                        ( "Result"
                                        , array ($l->variable("a"))
                                        )
                                    )
                                )
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
        $l = new Lang();

        /**
         * Represents the following program
         * main = 
         *     let v = \{} \u \{} -> 42# *# 23#
         *     in  case v of
         *             a -> Result a
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array()
                    , array()
                    , $l->let
                        ( array
                            ( $l->binding
                                ( $l->variable("v")
                                , $l->lambda
                                    ( array()
                                    , array()
                                    , $l->prim_op
                                        ( "IntMulOp"
                                        , array
                                            ( $l->literal(42)
                                            , $l->literal(23)
                                            )
                                        )
                                    , true
                                    )  
                                )
                            )
                        , $l->case_expr
                            ( $l->application
                                ( $l->variable("v")
                                , array()
                                )
                            , array
                                ( $l->default_alternative
                                    ( $l->variable("a")
                                    , $l->constructor
                                        ( "Result"
                                        , array ($l->variable("a"))
                                        )
                                    )
                                )
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
        $this->result = $stg->pop_argument_register();
    }
}
