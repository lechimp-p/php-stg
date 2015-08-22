<?php

namespace Lechimp\STG;

/**
 * Compiles expressions from the stg language to php-based STG
 * code.
 */
class Compiler {
    /**
     * Compile a program to a bunch of PHP files using the STG to execute the
     * defined program.
     *
     * @param   Lang\Program    $program
     * @return  array           $filename => $content
     */
    public function compile(Lang\Program $program) {
        $code = <<<PHP
class TheMachine extends \Lechimp\STG\STG {
    public function __construct() {
        parent::__construct(array
            ( "main" => new \Lechimp\STG\STGClosure()
            ));
    }
}
PHP;
        return array("main.php" => $code);
    }
} 
