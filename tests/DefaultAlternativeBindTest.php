<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Lang\Lang;

require_once(__DIR__ . "/OneProgramTestBase.php");

class DefaultAlternativeBindTest extends OneProgramTestBase
{
    protected function program(Lang $l)
    {
        /**
         * Represents the following program
         * main = \{swapAB, a} \u \{} -> swapAB a
         * a = \{} \n \{} -> A
         * swapAB = \{} \n \{a} ->
         *     case a of
         *         b -> b
         */
        return $l->prg(array( "main" => $l->lam_f(
            array("swapAB", "a"),
            $l->app("swapAB", "a")
        )
            , "a" => $l->lam_n(
                $l->con("A"),
                false
            )
            , "swapAB" => $l->lam_a(
                array("a"),
                $l->cse(
                    $l->app("a"),
                    array( "default b" => $l->app("b")
                        )
                ),
                false
            )
            ));
    }

    protected function assertions($result)
    {
        $this->assertEquals("A", $result[1]);
    }
}
