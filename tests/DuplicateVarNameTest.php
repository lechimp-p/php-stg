<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Lang\Lang;

class DuplicateVarNameTest extends OneProgramTestBase
{
    protected function program(Lang $l)
    {
        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} ->
         *           let b = \{a} \u \{} -> a
         *               a = \{swapAB} \n \{c} -> swapAB c
         *           in a b
         * a = \{} \n \{} -> A
         * swapAB = \{} \n \{a} ->
         *     case a of
         *         A -> B
         *         B -> A
         *         default -> a
         */
        return $l->prg(array( "main" => $l->lam_f(
            array("swapAB", "a"),
            $l->lt(
                        array( "b" => $l->lam_f(
                        array("a"),
                        $l->app("a")
                    )
                    , "a" => $l->lam(
                        array("swapAB"),
                        array("c"),
                        $l->app("swapAB", "c"),
                        false
                    )
                    ),
                        $l->app("a", "b")
                    )
        )
            , "a" => $l->lam_n(
                $l->con("A")
            )
            , "swapAB" => $l->lam_a(
                array("a"),
                $l->cse(
                        $l->app("a"),
                        array( "A" => $l->con("B")
                        , "B" => $l->con("A")
                        , "default" => $l->app("a")
                        )
                    ),
                false
            )
            ));
    }

    protected function assertions($result)
    {
        $this->assertEquals("B", $result[1]);
    }
}
