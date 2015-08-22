<?php

namespace Lechimp\STG\Lang;

/**
 * Base class for an expression.
 */
abstract class Expression {
    /**
     * @var Expression
     */
    private $expression;

    public function expression() {
        return $this->expression;
    }
}
