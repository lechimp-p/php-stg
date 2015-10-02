<?php

namespace Lechimp\STG\Lang;

/**
 * A factory for lang objects.
 *
 * This way we do not have to type new in the tests so often and also gain
 * flexibility in the objects used for lang construction.
 */
class Lang {
    public function algebraic_alternative($id, array $variables, Expression $expression) {
        return new AlgebraicAlternative($id, $variables, $expression);
    }

    public function application(Variable $variable, array $atoms) {
        return new Application($variable, $atoms);
    }

    public function binding(Variable $variable, Lambda $lambda) {
        return new Binding($variable, $lambda);
    }

    public function case_expr(Expression $expression, array $alternatives) {
        return new CaseExpr($expression, $alternatives);
    }

    public function constructor($id, array $atoms) {
        return new Constructor($id, $atoms);
    }

    public function default_alternative($variable, Expression $expression) {
        return new DefaultAlternative($variable, $expression);
    }

    public function lambda(array $free_variables, array $arguments, Expression $expression, $updatable) {
        return new Lambda($free_variables, $arguments, $expression, $updatable);
    }

    public function let(array $bindings, Expression $expression) {
        return new LetBinding($bindings, $expression);
    }

    public function letrec(array $bindings, Expression $expression) {
        return new LetRecBinding($bindings, $expression);
    }

    public function literal($value) {
        return new Literal($value);
    }

    public function prim_op($id, array $atoms) {
        return new PrimOp($id, $atoms);
    }

    public function primitive_alternative(Literal $literal, Expression $expression) {
        return new PrimitiveAlternative($literal, $expression);
    }

    public function program(array $bindings) {
        return new Program($bindings);
    }

    public function variable($name) {
        return new Variable($name);
    }
}
