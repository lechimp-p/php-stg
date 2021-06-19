<?php

namespace Lechimp\STG\Test;

use Lechimp\STG\Lang\Lang;

class ApplicationArgumentOrderTest extends OneProgramTestBase
{
    protected function program(Lang $l)
    {
        /**
         * Represents the following program
         * main = \{tuple2} \u \{} -> tuple2 1 2
         * tuple2 = \{} \n \{l,r} ->
         *      let t = \{l,r} \u \{} -> T2 l r
         *      in t
         */
        return $l->prg(array( "main" => $l->lam_f(
            array("tuple2"),
            $l->app("tuple2", $l->lit(1), $l->lit(2))
        )
            , "tuple2" => $l->lam_a(
                array("l", "r"),
                $l->lt(
                    array( "t" => $l->lam_f(
                            array("l", "r"),
                            $l->con("T2", "l", "r")
                        )
                    ),
                    $l->app("t")
                ),
                false
            )
            ));
    }

    protected function assertions($result)
    {
        $this->assertEquals("T2", $result[1]);
        $this->assertEquals(1, $result[2]);
        $this->assertEquals(2, $result[3]);
    }
}
