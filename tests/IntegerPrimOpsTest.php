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
        $program = $l->prg(array
            ( "main" => $l->lam_n
                ( $l->lt( array
                    ( "v" => $l->lam_n
                        ( $l->prm( "IntAddOp", 42, 23)
                        )  
                    )
                    , $l->cse
                        ( $l->app("v")
                        , array
                            ( "default a" => $l->con("Result", "a")
                            )
                        )
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "IntegerPrimOpsTestAdd"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new IntegerPrimOpsTestAdd\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals(42 + 23, $result[2]);
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
        $program = $l->prg(array
            ( "main" => $l->lam_n
                ( $l->lt( array
                    ( "v" => $l->lam_n
                        ( $l->prm( "IntSubOp", 42, 23)
                        )  
                    )
                    , $l->cse
                        ( $l->app("v")
                        , array
                            ( "default a" => $l->con("Result", "a")
                            )
                        )
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "IntegerPrimOpsTestSub"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new IntegerPrimOpsTestSub\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals(42 - 23, $result[2]);
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
        $program = $l->prg(array
            ( "main" => $l->lam_n
                ( $l->lt( array
                    ( "v" => $l->lam_n
                        ( $l->prm( "IntMulOp", 42, 23)
                        )  
                    )
                    , $l->cse
                        ( $l->app("v")
                        , array
                            ( "default a" => $l->con("Result", "a")
                            )
                        )
                    )
                )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "IntegerPrimOpsTestMul"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new IntegerPrimOpsTestMul\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals(42 * 23, $result[2]);
    }
}
