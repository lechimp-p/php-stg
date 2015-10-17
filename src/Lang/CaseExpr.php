<?php

namespace Lechimp\STG\Lang;

/**
 * A case expression.
 */
class CaseExpr extends Expression implements Syntax {
    /**
     * @var Expression
     */
    private $expression;

    /**
     * @var Alternative[]
     */
    private $alternatives;

    public function __construct(Expression $expression, array $alternatives) {
        $this->expression = $expression;
        $is_primitive_alternative = null;
        $this->alternatives = array_map(function (Alternative $alternative) 
                                        use (&$is_primitive_alternative) {
            if ($is_primitive_alternative === null) {
                $is_primitive_alternative = $alternative instanceof PrimitiveAlternative;
            }
            if ($is_primitive_alternative) {
                assert( $alternative instanceof PrimitiveAlternative 
                     || $alternative instanceof DefaultAlternative);
            }
            else {
                assert( $alternative instanceof AlgebraicAlternative
                     || $alternative instanceof DefaultAlternative);
            }
            return $alternative;
        }, $alternatives);
    }

    public function expression() {
        return $this->expression;
    }

    public function alternatives() {
        return $this->alternatives;
    }
}
