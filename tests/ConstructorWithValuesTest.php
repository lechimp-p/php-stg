<?php

use Lechimp\STG\Lang\Lang;

require_once(__DIR__ . "/OneProgramTestBase.php");

class ConstructorWithValuesTest extends OneProgramTestBase
{
    protected function program(Lang $l)
    {
        /**
         * Represents the following program
         * main = \{extract, a} \u \{} -> extract a
         * a = \{} \n \{} -> Wrapped 42 23
         * extract = \{} \n \{w} ->
         *     case w of
         *         Wrapped a b -> Result a b
         */
        return $l->prg(array( "main" => $l->lam_f(
                        array("extract", "a"),
                        $l->app("extract", "a")
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
        $this->assertEquals(42, $result[2]);
        $this->assertEquals(23, $result[3]);
    }
}
