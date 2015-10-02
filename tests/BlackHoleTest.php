<?php

use Lechimp\STG\Lang\Lang;
use Lechimp\STG\Compiler;
use Lechimp\STG\CodeLabel;

require_once(__DIR__."/ProgramTestBase.php");

class BlackHoleTest extends ProgramTestBase {
    /**
     * @expectedException Lechimp\STG\Exceptions\BlackHole
     */
    public function test_program() {
        $l = new Lang();

        /**
         * Represents the following program
         * main = \{} \u \{} -> 
         *      letrec a = \{a} \u \{} -> 
         *                  case a of
         *                      b -> b
         *      in a 
         */
        $program = $l->program(array
            ( $l->binding
                ( $l->variable("main")
                , $l->lambda
                    ( array()
                    , array()
                    , $l->letrec
                        ( array
                            ( $l->binding
                                ( $l->variable("a")
                                , $l->lambda
                                    ( array( $l->variable("a") )
                                    , array()
                                    , $l->case_expr
                                        ( $l->application
                                            ( $l->variable("a")
                                            , array()
                                            )
                                        , array
                                            ( $l->default_alternative
                                                ( $l->variable("b")
                                                , $l->application
                                                    ( $l->variable("b")
                                                    , array()
                                                    )
                                                )
                                            )
                                        )
                                    , true
                                    )
                                )
                            )
                        , $l->application 
                            ( $l->variable("a")
                            , array()
                            )
                        )
                    , true
                    )
                )
            ));

        $compiler = new Compiler();
        $compiled = $compiler->compile($program, "TheMachine", "BlackHoleTest"); 
        //$this->echo_program($compiled["main.php"]);
        eval($compiled["main.php"]);
        $machine = new BlackHoleTest\TheMachine();
        $machine->push_return(array
            ( "Undefined" => new CodeLabel($this, "returns_Undefined")
            ));
        $machine->run();
    }

    public function returns_Undefined($_) {
        $this->assertFalse(true);
    } 
}
