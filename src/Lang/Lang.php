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
class Lang implements Syntax {
    /**
     * Provide a dictionary of bindings and get a program.
     *
     * The dictionary has to be in the form $global_name => $lambda.
     *
     * @param   array   $bindings
     * @return  Program
     */
    public function prg(array $bindings) {
        $bnds = $this->to_bindings($bindings);
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

    /**
     * Like lam, but with free variables only.
     *
     * @param   string[]    $free_vars
     * @param   Expression  $expr
     * @param   bool|null   $updatable defaults to true
     * @return  Lambda
     */
    public function lam_f(array $free_vars, Expression $expr, $updatable = true) {
        return $this->lam($free_vars, array(), $expr, $updatable);
    }

    /**
     * Like lam, but with arguments only.
     *
     * @param   string[]    $args
     * @param   Expression  $expr
     * @param   bool|null   $updatable defaults to true
     * @return  Lambda
     */
    public function lam_a(array $args, Expression $expr, $updatable = true) {
        return $this->lam(array(), $args, $expr, $updatable);
    }

    /**
     * Like lam, but without free variables and arguments.
     *
     * @param   string[]    $args
     * @param   Expression  $expr
     * @param   bool|null   $updatable defaults to true
     * @return  Lambda
     */
    public function lam_n(Expression $expr, $updatable = true) {
        return $this->lam(array(), array(), $expr, $updatable);
    }

    /**
     * Provide a variable name and some arguments and get an application.
     *
     * @param   string      $variable
     * @param   ...         $arguments  These could either be atoms or string, where string
     *                                  are converted to variables.
     * @return  Expression
     */ 
    public function app($variable) {
        $args = func_get_args();
        array_shift($args);
        return $this->application($this->to_var($variable), $this->to_vars($args));
    }

    /**
     * Provide an expression and a dictionary with alternative and get a CaseExpr.
     *
     * The alternatives dictionary is interpreted as such: The keys are strings
     * delimited with spaces, which are split to an array. The first entry stands
     * for the pattern for the alternative and the other entries are names that
     * are used to bind variables in the pattern. If the pattern is 'default' it
     * is an default alternative. If pattern is an undelimited string, it is an
     * algebraic alternative. Otherwise it is a primitive alternative.
     *
     * @param   Expression  $expr
     * @param   array       $alternatives
     * @return  CaseExpr
     */
    public function cse(Expression $expr, array $alternatives) {
        $alts = array();
        foreach ($alternatives as $key => $value) {
            assert(is_string($key));
            $key = split(" ", $key);
            assert(count($key) > 0);
            $pattern = array_shift($key);

            if ($pattern === "default") {
                if (count($key) == 1) {
                    $var = $this->variable(array_shift($key));
                }
                else {
                    assert(count($key) == 0);
                    $var = null;
                }
                $alts[] = $this->default_alternative( $var, $value);
            }
            else if (is_string($pattern) && substr($pattern,0,1) !== '"') {
                $alts[] = $this->algebraic_alternative( $pattern
                                                      , $this->to_vars($key)
                                                      , $value
                                                      );
            }
            else {
                assert($pattern instanceof Atom);
                assert(count($key) == 0); 
                $alts[] = $this->primitive_alternative( (int)$pattern, array(), $value);
            }
        }
        return $this->case_expr($expr, $alts);
    }

    /**
     * Give a name and and some arguments and get a Constructor.
     *
     * The arguments are left as is when they are Atoms and must be strings
     * that get interpreted as variables otherwise.  
     *
     * @param   string  $name
     * @param   ...     $arguments
     * @return  Cosntructor
     */
    public function con($name) {
        $args = func_get_args();
        array_shift($args);
        return $this->constructor($name, $this->to_vars($args));
    }

    /**
     * Give an dictionary with bindings and an expression and get a LetRecBinding.
     *
     * @param   array       $bindings
     * @param   Expression  $expr
     * @return  LetRecBinding
     */
    public function ltr(array $bindings, Expression $expr) {
        $bnds = $this->to_bindings($bindings);
        return $this->letrec($bnds, $expr);
    }

    /**
     * Give an dictionary with bindings and an expression and get a LetBinding.
     *
     * @param   array       $bindings
     * @param   Expression  $expr
     * @return  LetRecBinding
     */
    public function lt(array $bindings, Expression $expr) {
        $bnds = $this->to_bindings($bindings);
        return $this->let($bnds, $expr);
    }

    /**
     * Get a literal.
     *
     * @param   mixed   $value
     * @return  Literal
     */
    public function lit($value) {
        return $this->literal($value);
    }

    /**
     * Provide a prim op name and two arguments and get an PrimOp.
     *
     * @param   string      $op
     * @param   mixed       $l
     * @param   mixed       $r
     * @return  PrimOp
     */ 
    public function prm($op, $l, $r) {
        return $this->prim_op($op, array($this->to_var($l), $this->to_var($r)));
    }

    // TRIVIAL FACTORIES 

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

    // HELPERS

    private function to_var($name) {
        if ($name instanceof Atom) {
            return $name;    
        }
        assert(is_string($name));
        return $this->variable($name);
    }

    private function to_vars(array &$arr) {
        return array_map(function($n) {
            return $this->to_var($n);
        }, $arr);
    }

    private function to_bindings(array &$bindings) {
        $bnds = array();
        foreach($bindings as $key => $value) {
            assert(is_string($key));
            $bnds[] = $this->binding($this->to_var($key), $value);
        }
        return $bnds;
    }
}
