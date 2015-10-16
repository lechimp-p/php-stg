<?php

use Lechimp\STG\Lang\Lang;

require_once(__DIR__."/OneProgramTestBase.php");

class GCTest extends OneProgramTestBase {
    protected function program(Lang $l) {
        /**
         * Represents the following program
         * main = \{sum, a} \u \{} -> sum a
         * sum = \{sum} \n \{v} ->
         *      case v of
         *          Cons h t -> \{sum, h, t} \u \{} ->
         *              let s = \{sum,t} \u \{} -> sum t
         *              in h + s
         *          End -> 0
         * cons = \{} -> \{h,t} -> Cons h t
         * end = \{} -> \{} -> End
         * a = \{cons, end} -> 
         *  letrec  l1 = cons 1 l2
         *          l2 = cons 2 l3
         *          l3 = cons 3 l4
         *          l4 = cons 4 l5
         *          l5 = cons 5 l6
         *          l6 = cons 6 l7
         *          l7 = cons 7 l8
         *          l8 = cons 8 l9
         *          l9 = cons 9 l10
         *          l10 = cons 10 end
         *  in l1 
         */
        return $l->prg(array
            ( "main" => $l->lam_f
                ( array("sum", "a")
                , $l->app("sum", "a")
                )
            , "sum" => $l->lam
                ( array("sum")
                , array("v")
                , $l->cse
                    ( "v"
                    , array
                        ( "Cons h t" => $l->lam_f
                            ( array("sum", "h", "t")
                            , $l->lt(array
                                ( "s" => $l->lam_f
                                    ( array("sum", "t")
                                    , $l->app("sum", "t")
                                    )
                                )
                                , $l->prm("IntAddOp", "h", "s")
                                )
                            )
                        , "End" => $l->lam_n($l->lit(0))
                        )
                    )
                )
            , "cons" => $l->lam_a
                ( array("h", "t")
                , $l->con("Cons", "h", "t")
                )
            , "end" => $l->lam_n($l->con("End"))
            , "a" => $l->lam_f
                ( array("cons", "end")
                , $l->ltr(array
                    ( "l1" => $l->app("cons", $l->lit(1), $l2) 
                    , "l2" => $l->app("cons", $l->lit(2), $l3) 
                    , "l3" => $l->app("cons", $l->lit(3), $l4) 
                    , "l4" => $l->app("cons", $l->lit(4), $l5) 
                    , "l5" => $l->app("cons", $l->lit(5), $l6) 
                    , "l6" => $l->app("cons", $l->lit(6), $l7) 
                    , "l7" => $l->app("cons", $l->lit(7), $l8) 
                    , "l8" => $l->app("cons", $l->lit(8), $l9) 
                    , "l9" => $l->app("cons", $l->lit(9), $l10) 
                    , "l10" => $l->app("cons", $l->lit(10), "end") 
                    )
                    , $l->app("l10")
                    )
                )
            ));
    }

    protected function assertions($result) {
        $this->assertEquals(1+2+3+4+5+6+7+8+9+10, $result[1]);
    }
}
