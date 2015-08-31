<?php

namespace Lechimp\STG\Lang;

class DefaultAlternative extends Alternative {
    public function __construct(Expression $expression) {
        $this->expression = $expression;
    }
}
