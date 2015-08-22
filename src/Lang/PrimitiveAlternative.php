<?php

namespace Lechimp\STG\Lang;

class PrimitiveAlternative extends Alternative {
    /**
     * @var Literal
     */
    private $literal;

    public function __construct(Literal $literal, Expression $expression) {
        $this->literal = $literal;
        $this->expression = $expression;
    }

    public function literal() {
        return $this->literal;
    }
}
