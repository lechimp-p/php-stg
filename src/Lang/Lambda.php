<?php

namespace Lechimp\STG\Lang;

/**
 * A lambda form.
 */
class Lambda implements Syntax {
    /**
     * @var Variable[]
     */
    private $free_variables;

    /**
     * @var Variable[]
     */
    private $arguments;

    /**
     * @var Expression
     */
    private $expression;

    /**
     * @var bool
     */
    private $updatable;

    public function __construct( array $free_variables
                               , array $arguments
                               , Expression $expression
                               , $updatable) {
        $this->free_variables = array_map(function(Variable $variable) {
            return $variable; 
        }, $free_variables);
        $this->arguments = array_map(function(Variable $variable) {
            return $variable; 
        }, $arguments);
        $this->expression = $expression;
        assert(is_bool($updatable));
        $this->updatable = $updatable;
        if ($this->updatable) {
            assert(count($arguments) == 0);
        }
    }

    public function free_variables() {
        return $this->free_variables;
    }

    public function arguments() {
        return $this->arguments;
    }

    public function expression() {
        return $this->expression;
    }

    public function updatable() {
        return $this->updatable;
    }
}
