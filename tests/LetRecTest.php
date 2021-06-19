<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Lang\Lang;

class LetRecTest extends OneProgramTestBase
{
    protected function program(Lang $l)
    {
        /**
         * Represents the following program
         * main = \{} \u \{} ->
         *      letrec result = \{a, extract} \u \{} -> extract a
         *             a = \{} \n \{} -> Wrapped 42 23
         *             extract = \{} \n \{w} ->
         *                  case w of
         *                      Wrapped a b -> Result a b
         *      in result
         */
        return $l->prg(array( "main" => $l->lam_n(
            $l->ltr(
                array( "result" => $l->lam_f(
                    array("a", "extract"),
                    $l->app("extract", "a")
                )
                    , "a" => $l->lam_n(
                        $l->con("Wrapped", $l->lit(42), $l->lit(23))
                    )
                    , "extract" => $l->lam_a(
                        array("w"),
                        $l->cse(
                            $l->app("w"),
                            array(  "Wrapped a b" => $l->con("Result", "a", "b")
                                )
                        ),
                        false
                    )
                    ),
                $l->app("result")
            )
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
