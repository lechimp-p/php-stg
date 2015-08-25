<?php

namespace Lechimp\STG\Lang;

/**
 * An alternative in a case expression.
 */
abstract class Alternative {
    /**
     * @var Expression
     */
    protected $expression;

    public function expression() {
        return $this->expression;
    }
}
