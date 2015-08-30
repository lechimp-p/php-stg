<?php

namespace Lechimp\STG\Lang;

class AlgebraicAlternative extends Alternative {
    /**
     * @var string
     */
    protected $id;

    /**
     * @var Variable[]
     */
    protected $variables;

    public function __construct($id, array $variables, Expression $expression) {
        assert(is_string($id));
        $this->id = $id;
        $this->expression = $expression;
        $this->variables = array_map(function(Variable $var) {
            return $var;
        }, $variables);
    }

    public function id() {
        return $this->id;
    }

    public function variables() {
        return $this->variables;
    }
}
