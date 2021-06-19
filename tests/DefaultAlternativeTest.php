<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Lang\Lang;

class DefaultAlternativeTest extends OneProgramTestBase
{
    protected function program(Lang $l)
    {
        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} -> swapAB a
         * a = \{} \n \{} -> C
         * swapAB = \{} \n \{a} ->
         *     case a of
         *         A -> B
         *         B -> A
         *         default -> a
         */
        return $l->prg(array( "main" => $l->lam_f(
            array("swapAB", "a"),
            $l->app("swapAB", "a")
        )
            , "a" => $l->lam_n(
                $l->con("C")
            )
            , "swapAB" => $l->lam_a(
                array($l->variable("a")),
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
        $this->assertEquals("C", $result[1]);
    }
}
