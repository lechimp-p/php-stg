<?php

namespace Lechimp\STG\Lang;

/**
 * A binding of an expression to a name.
 */
class Binding {
    /**
     * @var Variable
     */
    private $variable; 

    /**
     * @var Lambda
     */
    private $lambda;

    public function __construct(Variable $variable, Lambda $lambda) {
        $this->variable = $variable;
        $this->lambda = $lambda;
    }

    public function variable() {
        return $this->variable;
    }

    public function lambda() {
        return $this->lambda;
    }
}
