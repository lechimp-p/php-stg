<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/OneProgramTestBase.php");

class NestedCaseTest extends OneProgramTestBase {
    public function program(Lang $l) {
        /**
         * Represents the following program
         * main = \{extract, a} \u \{} -> extract a
         * a = \{} \n \{} -> 
         *      let w = \{} \u \{} -> Wrapped 42
         *      in Wrapped w
         * extract = \{} \n \{w} -> 
         *     case (case w of
         *              Wrapped a -> a
         *          )
         *     of
         *         Wrapped a -> Result a
         */
        return $l->prg(array
            ( "main" => $l->lam_f
                ( array("extract", "a")
                , $l->app("extract", "a")
                )
            , "a" => $l->lam_n
                ( $l->lt( array
                    ( "w" => $l->lam_n
                        ( $l->con("Wrapped", $l->lit(42))
                        )
                    )
                    , $l->con("Wrapped", "w")
                    )
                )
            , "extract" => $l->lam_a
                ( array("w")
                , $l->cse
                    ( $l->cse
                        ( $l->app("w")
                        , array
                            ( "Wrapped a" => $l->app("a")
                            )
                        )
                    , array
                        ( "Wrapped a" => $l->con("Result", "a")
                        )
                    )
                , false
                )
            ));
    }

    protected function assertions($result) {
        $this->assertEquals(42, $result[2]);
    }
}
