<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang\Syntax;
use Lechimp\STG\Gen\Gen;

/**
 * A pattern on the STG-syntax tree and how to compile it.
 */
abstract class Pattern {
    /**
     * Does this pattern match the language construct?
     *
     * Return null if pattern does not match. Returns something that will be
     * passed to compile if pattern matches.
     *
     * @param   Syntax     $c
     * @return  mixed|null
     */
    abstract public function matches(Syntax $c);

    /**
     * Compile the pattern based on the stuff returned from matches.
     *
     * @param   Compile $c
     * @param   Gen     $g
     * @param   mixed   &$p
     * @return  Results
     */
    abstract public function compile(Compiler $c, Gen $g, &$p);
}
