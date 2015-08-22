<?php

use Lechimp\STG\Lang\Program;
use Lechimp\STG\Lang\Binding;
use Lechimp\STG\Lang\Variable;
use Lechimp\STG\Lang\Lambda;
use Lechimp\STG\Lang\PrimOp;
use Lechimp\STG\Lang\Literal;
use Lechimp\STG\Compiler;

class Program1Test extends PHPUnit_Framework_TestCase {
    public function test_program() {
        $program = new Program(array
            ( new Binding
                ( new Variable("main")
                , new Lambda
                    ( array()
                    , array()
                    , new PrimOp
                        ( "+"
                        , array
                            ( new Literal(1)
                            , new Literal(2)
                            )
                        )
                    , true
                    )
                )
            ));
        $compiled = Compiler::compile($program); 
    }
}
