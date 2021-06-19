<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler\Compiler;

class GCTest extends ProgramTestBase
{
    public function test_gc()
    {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{} \u \{} -> sum a
         * sum = \{sum} \n \{v} ->
         *      case v of
         *          Cons h t ->
         *              let s = \{sum,t} \u \{} -> sum t
         *              in case s of
         *                  default u -> h + u
         *          End -> 0
         * cons = \{} \n \{h,t} ->
         *      let b = \{h,t} \u \{} -> Cons h t
         *      in b
         * end = \{} \u \{} -> End
         * a = \{cons, end} \u -> \{} ->
         *  letrec  l1 = \{l2, cons} \u \{} -> cons 1 l2
         *          l2 = \{l3, cons} \u \{} -> cons 2 l3
         *          l3 = \{l4, cons} \u \{} -> cons 3 l4
         *          l4 = \{l5, cons} \u \{} -> cons 4 l5
         *          l5 = \{l6, cons} \u \{} -> cons 5 l6
         *          l6 = \{l7, cons} \u \{} -> cons 6 l7
         *          l7 = \{l8, cons} \u \{} -> cons 7 l8
         *          l8 = \{l9, cons} \u \{} -> cons 8 l9
         *          l9 = \{l10, cons} \u \{} -> cons 9 l10
         *          l10 = \{end, cons} \u \{} -> cons 10 end
         *  in l1
         */
        $program = $l->prg(array( "main" => $l->lam_f(
            array("sum", "a"),
            $l->app("sum", "a")
        )
            , "sum" => $l->lam(
                array("sum"),
                array("v"),
                $l->cse(
                    $l->app("v"),
                    array( "Cons h t" => $l->lt(
                        array( "s" => $l->lam_f(
                            array("sum", "t"),
                            $l->app("sum", "t")
                        )
                                ),
                        $l->cse(
                            $l->app("s"),
                            array("default u" => $l->prm("IntAddOp", "h", "u"))
                        )
                    )
                        , "End" => $l->lit(0)
                        )
                ),
                false
            )
            , "cons" => $l->lam_a(
                array("h", "t"),
                $l->lt(
                    array( "b" => $l->lam_f(
                        array("h", "t"),
                        $l->con("Cons", "h", "t")
                    )
                    ),
                    $l->app("b")
                ),
                false
            )
            , "end" => $l->lam_n($l->con("End"))
            , "a" => $l->lam_f(
                array("cons", "end"),
                $l->ltr(
                    array( "l1" => $l->lam_f(array("l2", "cons"), $l->app("cons", $l->lit(1), "l2"))
                    , "l2" => $l->lam_f(array("l3", "cons"), $l->app("cons", $l->lit(2), "l3"))
                    , "l3" => $l->lam_f(array("l4", "cons"), $l->app("cons", $l->lit(3), "l4"))
                    , "l4" => $l->lam_f(array("l5", "cons"), $l->app("cons", $l->lit(4), "l5"))
                    , "l5" => $l->lam_f(array("l6", "cons"), $l->app("cons", $l->lit(5), "l6"))
                    , "l6" => $l->lam_f(array("l7", "cons"), $l->app("cons", $l->lit(6), "l7"))
                    , "l7" => $l->lam_f(array("l8", "cons"), $l->app("cons", $l->lit(7), "l8"))
                    , "l8" => $l->lam_f(array("l9", "cons"), $l->app("cons", $l->lit(8), "l9"))
                    , "l9" => $l->lam_f(array("l10", "cons"), $l->app("cons", $l->lit(9), "l10"))
                    , "l10" => $l->lam_f(array("end", "cons"), $l->app("cons", $l->lit(10), "end"))
                    ),
                    $l->app("l1")
                )
            )
            ));

        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "GCTest");
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new \GCTest\TheMachine();
        $machine->check_garbage_collection_cycles = 100000;
        $result = $this->machine_result($machine);

        $this->assertEquals(1 + 2 + 3 + 4 + 5 + 6 + 7 + 8 + 9 + 10, $result[1]);

        // TODO: This test surely is not enough, as it only proves that
        // the STG somehoe messes around with some numbers. We do not
        // acquire any knowledge removed closures. We also do not know
        // if me miss some updated closures.
        $amount_closures_before_gc = $machine->amount_closures;
        $updated_closures_before_gc = $machine->updated_closures;
        $this->assertGreaterThan(0, $amount_closures_before_gc);
        $this->assertGreaterThan(0, $updated_closures_before_gc);

        $machine->collect_garbage();

        $amount_closures_after_gc = $machine->amount_closures;
        $updated_closures_after_gc = $machine->updated_closures;
        $this->assertGreaterThan(0, $amount_closures_after_gc);
        $this->assertGreaterThan($amount_closures_after_gc, $amount_closures_before_gc);
        $this->assertEquals(0, $updated_closures_after_gc);
    }
}
