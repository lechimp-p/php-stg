<?php

namespace Lechimp\STG\Lang;

/**
 * A factory for lang objects.
 *
 * This way we do not have to type new in the tests so often and also gain
 * flexibility in the objects used for lang construction.
 *
 * There also is a set of three word methods that hides the underlying structure
 * of objects from the user and makes notation of STG-programs shorter.
 */
class Lang {
    /**
     * Provide a dictionary of bindings and get a program.
     *
     * The dictionary has to be in the form $global_name => $lambda.
     *
     * @param   array   $bindings
     * @return  Program
     */
    public function prg(array $bindings) {
        $bnds = array();
        foreach($bindings as $key => $value) {
            assert(is_string($key));
            $bnds[] = $this->binding($this->variable($key), $value);
        }
        return $this->program($bnds);
    }

    /**
     * Provide an array of free variables, an array of arguments and an expression
     * and get a lambda.
     *
     * One can optionaly provide an updatable-flag, which defaults to true.
     *
     * @param   string[]    $free_vars
     * @param   string[]    $args
     * @param   Expression  $expr
     * @param   bool|null   $updatable defaults to true
     * @return  Lambda
     */
    public function lam(array $free_vars, array $args, Expression $expr, $updatable = true) {
        assert(is_bool($updatable));
        return $this->lambda( $this->to_vars($free_vars)
                            , $this->to_vars($args)
                            , $expr
                            , $updatable
                            );
    }

    private function to_vars(array &$arr) {
        return array_map(function($n) {
            assert(is_string($n));
            return $this->variable($n);
        }, $arr);
    }

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
