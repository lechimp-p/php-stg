<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler\Compiler;
use Lechimp\STG\CodeLabel;

class UpdateTest extends ProgramTestBase
{
    public function test_program()
    {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{} \u \{} ->
         *  letrec v = \{} \n \{c} -> V c
         *         a = \{v} \u \{} -> v 42
         *         t = \{a} \u \{} -> T a a
         *  in case t of
         *      T a b -> case a of
         *          V v1 -> case b of
         *              V v2 -> Result v1 v2
         */
        $program = $l->program(array( $l->binding(
            $l->variable("main"),
            $l->lambda(
                array(),
                array(),
                $l->letrec(
                            array( $l->binding(
                                $l->variable("v"),
                                $l->lambda(
                                array(),
                                array($l->variable("c")),
                                $l->constructor(
                                        "V",
                                        array( $l->variable("c")
                                        )
                                    ),
                                false
                            )
                            )
                        , $l->binding(
                            $l->variable("a"),
                            $l->lambda(
                                array($l->variable("v")),
                                array(),
                                $l->application(
                                        $l->variable("v"),
                                        array($l->literal(42))
                                    ),
                                true
                            )
                        )
                        , $l->binding(
                            $l->variable("t"),
                            $l->lambda(
                                array($l->variable("a")),
                                array(),
                                $l->constructor(
                                        "T",
                                        array( $l->variable("a")
                                        , $l->variable("a")
                                        )
                                    ),
                                true
                            )
                        )
                        )
            /*  This part is:
             *  in case t of
             *      T a b -> case a of
             *          V v1 -> case b of
             *              V v2 -> Result v1 v2
             */
                        ,
                            $l->case_expr(
                                $l->application(
                                $l->variable("t"),
                                array()
                            ),
                                array( $l->algebraic_alternative(
                                "T",
                                array( $l->variable("a")
                                        , $l->variable("b")
                                        ),
                                $l->case_expr(
                                        $l->application(
                                                $l->variable("a"),
                                                array()
                                            ),
                                        array( $l->algebraic_alternative(
                                                "V",
                                                array($l->variable("v1")),
                                                $l->case_expr(
                                                    $l->application(
                                                            $l->variable("b"),
                                                            array()
                                                        ),
                                                    array( $l->algebraic_alternative(
                                                            "V",
                                                            array($l->variable("v2")),
                                                            $l->constructor(
                                                                "Result",
                                                                array( $l->variable("v1")
                                                                    , $l->variable("v2")
                                                                    )
                                                            )
                                                        )
                                                        )
                                                )
                                            )
                                            )
                                    )
                            )
                                )
                            )
                        ),
                false
            )
        )
            ));
        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "UpdateTest");
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new \UpdateTest\TheMachine();
        $result = $this->machine_result($machine);
        $this->assertEquals("Result", $result[1]);
        $this->assertEquals(42, $result[2]);
        $this->assertEquals(42, $result[3]);
    }
}
