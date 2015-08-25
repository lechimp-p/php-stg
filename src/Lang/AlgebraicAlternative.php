<?php

namespace Lechimp\STG\Lang;

class AlgebraicAlternative extends Alternative {
    /**
     * @var string
     */
    protected $id;

    public function __construct($id, Expression $expression) {
        assert(is_string($id));
        $this->id = $id;
        $this->expression = $expression;
    }

    public function id() {
        return $this->id;
    }
}
