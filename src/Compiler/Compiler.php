<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen;

/**
 * Compiles expressions from the stg language to php-based STG code.
 *
 * Only deals with high level question, as 'put var in local environment'
 * while Gen-class deals with the creation of actual php code.
 */
class Compiler {
    const STG_VAR_NAME = 'stg';

    /**
     * Create a code generator.
     *
     * @return Gen
     */
    public function code_generator($namespace, $stg_name, $standard_name) {
        return new Gen\Gen($namespace, $stg_name, $standard_name);
    }

    /**
     * Create a compilation results object 
     *
     * @return Results 
     */
    public function results() {
        return new Results();
    }

    /**
     * The patterns that are applied to the syntax.
     *
     * @var Pattern[]
     */
    public $patterns;

    /**
     * @var int
     */
    public $amount_of_patterns;

    public function __construct() {
        $this->patterns = array
            // This needs to contain the patterns from specific to general.
            ( new Lambda()
            , new Application()
            , new Constructor()
            , new Program()
            );
        $this->amount_of_patterns = count($this->patterns);
    }

    /**
     * Compile a program to a bunch of PHP files using the STG to execute the
     * defined program.
     *
     * @param   Lang\Program    $program
     * @param   string          $stg_class_name
     * @param   string          $namespace      Where to put the classes we create.
     * @param   strubg          $standard_name  Class name for standard closure.
     * @return  array           $filename => $content
     */
    public function compile( Lang\Program $program, $stg_class_name
                           , $namespace = ""
                           , $standard_name = "\\Lechimp\\STG\\Closures\\Standard") {
        assert(is_string($stg_class_name));

        $g = $this->code_generator($namespace, self::STG_VAR_NAME, $standard_name); 
        // TODO: This should be going to the generator like namespace.
        $this->stg_class_name = $stg_class_name;

        $results = $this->compile_syntax($g, $program);

        assert(count($results->methods()) == 0);
        assert(count($results->statements()) == 0);

        // Render all classes to a single file.
        return array("main.php" => implode("\n\n", array_map(function(Gen\GClass $cl) {
            return $cl->render(0);
        }, $results->classes())));
    }

    public function compile_syntax(Gen\Gen $g, Lang\Syntax $s) {
        // TODO: This is very inefficient and will make the compiler
        // slow. I could somehow construct a search tree from the patterns
        // to make it faster again.
        for ($i = 0; $i < $this->amount_of_patterns; $i++) {
            $res = $this->patterns[$i]->matches($s);
            if ($res !== null) {
                return $this->patterns[$i]->compile($this, $g, $res);
            }
        }

        throw new LogicException("Don't know how to compile ".get_class($s));
    }

    //---------------------
    // LAMBDAS
    //---------------------

    // TODO: remove this temporary method. It is just needed to ease
    // refactoring.
    public function compile_lambda_old(Gen\Gen $g, Lang\Lambda $lambda, $class_name) {
        $results = $this->compile_syntax($g, $lambda);
        return $results->add_class
            ($g->closure_class($class_name, $results->flush_methods()));
    }

    //---------------------
    // EXPRESSIONS
    //---------------------

    public function compile_expression(Gen\Gen $g, Lang\Expression $expression) {
        if ($expression instanceof Lang\Application
        ||  $expression instanceof Lang\Constructor) {
            return $this->compile_syntax($g, $expression);
        }
        if ($expression instanceof Lang\CaseExpr) {
            return $this->compile_case_expression($g, $expression);
        }
        if ($expression instanceof Lang\LetBinding) {
            return $this->compile_let_binding($g, $expression);
        }
        if ($expression instanceof Lang\LetRecBinding) {
            return $this->compile_letrec_binding($g, $expression);
        }
        if ($expression instanceof Lang\Literal) {
            return $this->compile_literal($g, $expression);
        }
        if ($expression instanceof Lang\PrimOp) {
            return $this->compile_prim_op($g, $expression);
        }
        throw new \LogicException("Unknown expression '".get_class($expression)."'.");
    }

    //---------------------
    // LITERALS
    //---------------------

    public function compile_literal(Gen\Gen $g, Lang\Literal $literal) {
        $value = $literal->value();

        $results = $this->results();
        $results->add_statements( array_flatten
            ( $g->stmt("\$primitive_value = $value")
            , $g->stg_primitive_value_jump()
            ));
        return $results;
    }

    //---------------------
    // CASE EXPRESSIONS
    //---------------------

    public function compile_case_expression(Gen\Gen $g, Lang\CaseExpr $case_expression) {
        $return_vector = array();

        $alternatives_results 
            = $this->compile_alternatives($g, $case_expression->alternatives(), $return_vector);
        assert(count($alternatives_results->statements()) == 0);

        $sub_results
            = $this->compile_expression($g, $case_expression->expression());

        $method_name = $g->method_name("case_return");

        $results = $this->results();
        $results
        ->add_statements( array
            ( $g->stg_push_local_env()
            , $g->stg_push_return($g->code_label($method_name))
            ))
        ->add_method( $g->public_method
            ( $method_name
            , $g->stg_args()
            , $this->compile_case_return($g, $return_vector) 
            ));
 
        $results->add($alternatives_results);
        $results->add($sub_results);
        return $results;
    }

    public function compile_case_return(Gen\Gen $g, array $return_vector) {
        $default = null;
        $stmts = array
            // First entry of data vector contains actual value or closure,
            // while the second one is examined to chose a code label to go
            // on with. See compile_constructor and compile_primitive_value_jump.
            ( $g->stg_get_register_to("data_vector")
            , $g->stmt('$value = $data_vector[1]')
            );

        // $value either is a real value or the name of a constructor here.
        foreach($return_vector as $value => $return_label) {
            if ($value === "") {
                $default = $return_label;
                continue;
            }
            
            if (is_string($value)) {
                $value = "\"$value\"";
            } 
            // TODO: I use strict comparison for now, but maybe this
            // will lead to problems?
            $stmts[] = $g->stmt("if (\$value === $value) return $return_label");
        }

        if ($default !== null) {
            $stmts[] = $g->stmt("return $default");
        }
        else {
            $stmts[] = $g->stmt("throw new \\LogicException(".
                                "\"No matching alternative for '\$value'\"".
                                ")");
        }

        return $stmts;
    }

    public function compile_alternatives(Gen\Gen $g, array $alternatives, array &$return_vector) {
        $results = $this->results();
        foreach($alternatives as $alternative) {
            $results->add($this->compile_alternative($g, $alternative, $return_vector));
        }
        return $results;
    } 
    
    public function compile_alternative(Gen\Gen $g, Lang\Alternative $alternative, array &$return_vector) {
        if ($alternative instanceof Lang\DefaultAlternative) {
            return $this->compile_alternative_default($g, $alternative, $return_vector);
        }
        else if ($alternative instanceof Lang\PrimitiveAlternative) {
            return $this->compile_alternative_primitive($g, $alternative, $return_vector);
        }
        else if ($alternative instanceof Lang\AlgebraicAlternative) {
            return $this->compile_alternative_algebraic($g, $alternative, $return_vector);
        }
        else {
            throw new \LogicException("Unknown alternative class ".get_class($alternative));
        }
    }

    // Return code that needs to be executed for every alternative. It restores
    // the environment for the alternative.
    public function compile_alternative_common_return_code(Gen\Gen $g) {
        return array
            ( $g->stg_pop_local_env()
            );
    }

    public function compile_alternative_default(Gen\Gen $g, Lang\DefaultAlternative $alternative, array &$return_vector) {
        $results = $this->results();

        $method_name = $g->method_name("alternative_default");
        $return_vector[""] = $g->code_label($method_name);

        if ($alternative->variable() === null) {
            $results->add_statements(array_flatten
                ( $this->compile_alternative_common_return_code($g)
                // We won't need the value from the constructor.
                , $g->stg_pop_register()
                ));
        }
        else {
            $var_name = $alternative->variable()->name();
            $results->add_statements(array_flatten
                ( $this->compile_alternative_common_return_code($g)
                // Save value from constructor in local env.
                , $g->stg_pop_register_to("return_vector")
                , $g->to_local_env($var_name, '$return_vector[0]')
                ));
        }

        $results->add($this->compile_expression($g, $alternative->expression()));
        $results->add_method($g->public_method
            ( $method_name
            , $g->stg_args()
            , $results->flush_statements()
            ));
        
        return $results; 
    }


    public function compile_alternative_primitive(Gen\Gen $g, Lang\PrimitiveAlternative $alternative, array &$return_vector) {
        $results = $this->results();

        $value = $alternative->literal()->value();
        assert(is_int($value));
        $method_name = $g->method_name("alternative_$value");
        $return_vector[$value] = $g->code_label($method_name);

        return $results
            ->add_statements($this->compile_alternative_common_return_code($g))
            ->add_statement($g->stg_pop_register())
            ->add($this->compile_expression($g, $alternative->expression()))
            ->add_method($g->public_method
                ( $method_name
                , $g->stg_args()
                , $results->flush_statements()
                ))
            ;
    }

    public function compile_alternative_algebraic(Gen\Gen $g, Lang\AlgebraicAlternative $alternative, array &$return_vector) {
        $results = $this->results();

        $id = $alternative->id();
        $method_name = $g->method_name("alternative_$id");
        $return_vector[$id] = $g->code_label($method_name);
        // Pop arguments to constructor and fill them into appropriate variables.
        $results->add_statements(array_flatten
            ( $this->compile_alternative_common_return_code($g)
            , $g->stg_pop_register_to("data_vector")
            , $g->stmt('array_shift($data_vector)')
            , $g->stmt('array_shift($data_vector)')
            , array_map(function(Lang\Variable $var) use ($g) {
                $name = $var->name();
                return $g->to_local_env($name, "array_shift(\$data_vector)");
            }, $alternative->variables())
            ));

        $results->add($this->compile_expression($g, $alternative->expression()));
        $results->add_method($g->public_method
            ( $method_name
            , $g->stg_args()
            , $results->flush_statements()
            ));
        
        return $results;
    }

    //---------------------
    // LET BINDINGS
    //---------------------

    public function compile_let_binding(Gen\Gen $g, Lang\LetBinding $let_binding) {
        // Cashes fresh class names in the first iteration as they are needed
        // in the second iteration.
        $class_names = array();

        return $this->results()
            ->add_statements(array_flatten
                ( array_map( function(Lang\Binding $binding) use ($g, &$class_names) {
                    $name = $binding->variable()->name();
                    $class_name = $g->class_name($name);
                    $class_names[] = $class_name;
                    return array_flatten
                        ( $g->stmt("\$free_vars_$name = array()")
                        , array_map(function(Lang\Variable $free_var) use ($g, $name) {
                            $fname = $free_var->name();
                            return $g->stmt("\$free_vars_{$name}[\"$fname\"] = \$local_env[\"$fname\"]");
                        }, $binding->lambda()->free_variables())
                        , $g->to_local_env($name, $g->stg_new_closure($class_name, $name))
                        );
                }, $let_binding->bindings())))
            ->adds(array_flatten
                ( array_map(function(Lang\Binding $binding) use ($g, &$class_names) {
                    $class_name = array_shift($class_names);
                    return $this->compile_lambda_old($g, $binding->lambda(), $class_name);
                }, $let_binding->bindings())))
            ->add($this->compile_expression($g, $let_binding->expression()));
    }

    //---------------------
    // LET REC BINDINGS
    //---------------------

    public function compile_letrec_binding(Gen\Gen $g, Lang\LetRecBinding $letrec_binding) {
        // Cashes fresh class names in the first iteration as they are needed
        // in the third iteration.
        $class_names = array();
    
        return $this->results()
            ->add_statements( array_flatten
                // First create the closures with stubs for free variables
                ( array_map( function(Lang\Binding $binding) use ($g, &$class_names) {
                    $name = $binding->variable()->name();
                    $class_name = $g->class_name($name);
                    $class_names[] = $class_name;
                    return array_flatten
                        ( $g->stmt("\$free_vars_$name = array()")
                        , array_map(function(Lang\Variable $free_var) use ($g, $name) {
                            $fname = $free_var->name();
                            return $g->stmt("\$free_vars_{$name}[\"$fname\"] = null");
                        }, $binding->lambda()->free_variables())
                        , $g->to_local_env($name, $g->stg_new_closure($class_name, $name))
                        );
                }, $letrec_binding->bindings())

                // Then bind the stubs to the new variables.
                , array_map( function(Lang\Binding $binding) use ($g) {
                    $name = $binding->variable()->name();
                    return array_map(function(Lang\Variable $free_var) use ($g, $name) {
                        $fname = $free_var->name();
                        return $g->stmt("\$free_vars_{$name}[\"$fname\"] = ".$g->local_env($fname));
                    }, $binding->lambda()->free_variables());
                }, $letrec_binding->bindings())))
            ->adds( array_flatten
                ( array_map(function(Lang\Binding $binding) use ($g, &$class_names) {
                    $class_name = array_shift($class_names);
                    return $this->compile_lambda_old($g, $binding->lambda(), $class_name);
                }, $letrec_binding->bindings()) 
                ))
            ->add($this->compile_expression($g, $letrec_binding->expression()));
    }

    //---------------------
    // ATOMS
    //---------------------

    public function compile_atom(Gen\Gen $g, Lang\Atom $atom) {
        if ($atom instanceof Lang\Variable) {
            $var_name = $atom->name();
            return $g->local_env($var_name); 
        }
        if ($atom instanceof Lang\Literal) {
            return $atom->value();
        }
        throw new \LogicException("Unknown atom '$atom'.");
    }

    //---------------------
    // PRIM OPS
    //---------------------

    // TODO: STG Paper mentions short circuit possibility.

    public function compile_prim_op(Gen\Gen $g, Lang\PrimOp $prim_op) {
        $id = $prim_op->id();
        $method_name = "compile_prim_op_$id";
        return $this->$method_name($g, $prim_op->atoms());
    }

    public function compile_prim_op_IntAddOp(Gen\Gen $g, array $atoms) {
        assert(count($atoms) == 2);
        list($l, $r) = $atoms;
        $left = $this->compile_atom($g, $l);
        $right = $this->compile_atom($g, $r);
        return $this->results()
            ->add_statements( array_flatten
                ( $g->stmt("\$primitive_value = $left + $right")
                , $g->stg_primitive_value_jump($g)
                ));
    }

    public function compile_prim_op_IntSubOp(Gen\Gen $g, array $atoms) {
         assert(count($atoms) == 2);
        list($l, $r) = $atoms;
        $left = $this->compile_atom($g, $l);
        $right = $this->compile_atom($g, $r);
        return $this->results()
            ->add_statements( array_flatten
                ( $g->stmt("\$primitive_value = $left - $right")
                , $g->stg_primitive_value_jump($g)
                ));       
    }

    public function compile_prim_op_IntMulOp(Gen\Gen $g, array $atoms) {
        assert(count($atoms) == 2);
        list($l, $r) = $atoms;
        $left = $this->compile_atom($g, $l);
        $right = $this->compile_atom($g, $r);
        return $this->results()
            ->add_statements( array_flatten
                ( $g->stmt("\$primitive_value = $left * $right")
                , $g->stg_primitive_value_jump($g)
                ));
    }

/* for copy:
    public function compile_prim_op_IntMulOp(Gen\Gen $g, array $atoms) {
        
    }
*/
} 


function array_flatten() {
    $args = func_get_args();
    if (count($args) == 0) {
        return array();
    }
    if (count($args) == 1) {
        if (is_array($args[0])) {
            $returns = array();
            foreach($args[0] as $val) {
                if (is_array($val)) {
                    $returns = array_merge($returns, array_flatten($val));
                }
                else {
                    $returns[] = $val;
                }
            }
            return $returns;
        }
        else {
            return $args[0];
        }
    }
    return array_flatten($args);
}
