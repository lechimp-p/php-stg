<?php

namespace Lechimp\STG\Lang;

class DefaultAlternative extends Alternative {
    /**
     * @var Variable|null
     */
    private $variable;

    public function __construct(Variable $variable, Expression $expression) {
        $this->variable = $variable;
        $this->expression = $expression;
    }

    public function variable() {
        return $this->variable;
    }
}
