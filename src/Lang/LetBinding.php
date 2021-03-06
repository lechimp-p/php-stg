<?php

namespace Lechimp\STG\Lang;

/**
 * A let binding expression.
 */
class LetBinding extends Expression implements Syntax
{
    /**
     * @var Binding[]
     */
    private $bindings;

    /**
     * @var Expression
     */
    private $expression;

    public function __construct(array $bindings, Expression $expression)
    {
        $this->bindings = array_map(function (Binding $binding) {
            return $binding;
        }, $bindings);
        $this->expression = $expression;
    }

    public function bindings()
    {
        return $this->bindings;
    }

    public function expression()
    {
        return $this->expression;
    }
}
