<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Lang\Lang;

class LetTest extends OneProgramTestBase
{
    protected function program(Lang $l)
    {
        /**
         * Represents the following program
         * main = \{} \u \{} ->
         *      let a = \{} \n \{} -> A
         *          swapAB = \{} \n \{a} ->
         *              case a of
         *                  A -> B
         *                  B -> A
         *      in swapAB a
         */
        return $l->prg(array( "main" => $l->lam_n(
            $l->lt(
                array( "a" => $l->lam_n(
                    $l->con("A")
                )
                    , "swapAB" => $l->lam_a(
                        array("a"),
                        $l->cse(
                            $l->app("a"),
                            array( "A" => $l->con("B")
                                    , "B" => $l->con("A")
                                    )
                        ),
                        false
                    )
                    ),
                $l->app("swapAB", "a")
            )
        )
            ));
    }

    protected function assertions($result)
    {
        $this->assertEquals("B", $result[1]);
    }
}
