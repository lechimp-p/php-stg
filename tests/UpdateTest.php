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
        $program = $l->prg([
            "main" => $l->lam_n(
                $l->ltr(
                    [
                        "v" => $l->lam_a(
                            ["c"],
                            $l->con(
                                "V",
                                "c"
                            ),
                            false
                        ),
                        "a" => $l->lam_f(
                            ["v"],
                            $l->app(
                                "v",
                                $l->lit(42)
                            )
                        ),
                        "t" => $l->lam_f(
                            ["a"],
                            $l->con(
                                "T",
                                "a",
                                "a"
                            )
                        )
                    ],
                    $l->cse(
                        $l->app("t"),
                        [
                            "T a b" => $l->cse(
                                $l->app("a"),
                                [
                                    "V v1" => $l->cse(
                                        $l->app("b"),
                                        [
                                            "V v2" => $l->con(
                                                "Result",
                                                "v1",
                                                "v2"
                                            )
                                        ]
                                    )
                                ]
                            )
                        ]
                    )
                )
            )
        ]);

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
