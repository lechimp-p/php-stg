<?php

use Lechimp\STG\Lang\Lang;

require_once(__DIR__."/OneProgramTestBase.php");

class BasicTest extends OneProgramTestBase {
    protected function program(Lang $l) {
        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} -> swapAB a
         * a = \{} \n \{} -> A
         * swapAB = \{} \n \{a} -> 
         *     case a of
         *         A -> B
         *         B -> A   
         *         default -> a   
         */
        return $l->prg(array
            ( "main" => $l->lam_f
                ( array("swapAB", "a")
                , $l->app("swapAB", "a")
                )
            , "a" => $l->lam_n
                ( $l->con("A")
                )
            , "swapAB" => $l->lam_a
                ( array("a")
                , $l->cse
                    ( $l->app("a")
                    , array
                        ( "A"       => $l->con("B")
                        , "B"       => $l->con("A")
                        , "default" => $l->app("a")
                        )
                    )
                , false
                )
            ));
    }

    protected function assertions($result) {
        $this->assertEquals("B", $result[1]);
    }
}
