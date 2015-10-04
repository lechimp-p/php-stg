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
            $bnds[] = $this->binding($this->to_var($key), $value);
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
     * The alternatives dictionary is interpreted as such: The keys are arrays, where
     * the first entry stands for the pattern for the alternative and the other entries
     * are strings that are used to bind variables in the pattern. If the pattern is
     * empty it is an default alternative. If pattern is a string, it is an algebraic
     * alternative. If the pattern is a literal, it is an primitive alternative. The key
     * could also be a string and is then interpreted as an array with one entry.
     *
     * @param   Expression  $expr
     * @param   array       $alternatives
     * @return  CaseExpr
     */
    public function cse(Expression $expr, array $alternatives) {
        $alts = array();
        foreach ($alternatives as $key => $value) {
            if (is_string($key)) {
                $key = array($key);
            }
            assert(is_array($key));
            assert(count($key) > 0);
            $pattern = array_shift($key);

            if ($pattern === "") {
                if (count($key) == 1) {
                    $var = $this->variable(array_shift($key));
                }
                else {
                    assert(count($key) == 0);
                    $var = null;
                }
                $alts[] = $this->default_alternative( $var, $value);
            }
            else if (is_string($pattern)) {
                $alts[] = $this->algebraic_alternative( $pattern
                                                      , $this->to_vars($key)
                                                      , $value
                                                      );
            }
            else {
                assert($pattern instanceof Atom);
                assert(count($key) == 0); 
                $alts[] = $this->primitive_alternative( $pattern, array(), $value);
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
