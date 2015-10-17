<?php

namespace Lechimp\STG\Lang;

class DefaultAlternative extends Alternative implements Syntax {
    /**
     * @var Variable|null
     */
    protected $variable;

    public function __construct($variable, Expression $expression) {
        assert(is_null($variable) || $variable instanceof Variable);
        $this->variable = $variable;
        $this->expression = $expression;
    }

    public function variable() {
        return $this->variable;
    }
}
