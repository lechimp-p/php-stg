<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

class LetWithFreeVariablesTest extends OneProgramTestBase
{
    public function program(Lang $l)
    {
        /**
         * Represents the following program
         * main = \{a, extract} \u \{} ->
         *      let result = \{a, extract} \u \{} -> extract a
         *      in result
         * a = \{} \n \{} -> Wrapped 42 23
         * extract = \{} \n \{w} ->
         *     case w of
         *         Wrapped a b -> Result a b
         */
        return $l->prg(array( "main" => $l->lam_f(
            array("a", "extract"),
            $l->lt(
                array( "result" => $l->lam_f(
                    array("a", "extract"),
                    $l->app("extract", "a")
                )
                    ),
                $l->app("result")
            )
        )
            , "a" => $l->lam_n(
                $l->con("Wrapped", $l->lit(42), $l->lit(23))
            )
            , "extract" => $l->lam_a(
                array("w"),
                $l->cse(
                    $l->app("w"),
                    array( "Wrapped a b" => $l->con("Result", "a", "b")
                        )
                ),
                false
            )
            ));
    }

    protected function assertions($result)
    {
        $this->assertEquals("Result", $result[1]);
        $this->assertEquals(42, $result[2]);
        $this->assertEquals(23, $result[3]);
    }
}
