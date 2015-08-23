<?php

namespace Lechimp\STG\Lang;

/**
 * Application of a function to some arguments.
 */
class Application extends Expression {
    /**
     * @var Variable
     */
    private $variable;

    /**
     * @var Atom[]
     */
    private $atoms;

    public function __construct(Variable $variable, array $atoms) {
        $this->variable = $variable;
        $this->atoms = array_map(function(Atom $atom) {
            return $atom;
        }, $atoms);
    }

    public function variable() {
        return $this->variable;
    }

    public function atoms() {
        return $this->atoms;
    }
} 
