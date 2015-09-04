<?php

namespace Lechimp\STG;

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
    protected function code_generator($namespace, $stg_name) {
        return new Gen($namespace, $stg_name);
    }

    /**
     * Create a compilation results object 
     *
     * @return CompilationResults 
     */
    protected function results() {
        return new CompilationResults();
    }

    /**
     * Compile a program to a bunch of PHP files using the STG to execute the
     * defined program.
     *
     * @param   Lang\Program    $program
     * @param   string          $stg_class_name
     * @return  array           $filename => $content
     */
    public function compile(Lang\Program $program, $stg_class_name, $namespace = "") {
        assert(is_string($stg_class_name));

        $g = $this->code_generator($namespace, self::STG_VAR_NAME); 

        $results_globals = $this->compile_globals($g, $program->bindings());
        
        // Class for the final stg machine
        $results_machine = $this->compile_machine($g, $stg_class_name, $program->bindings(), $results_globals->globals());

        $results = $results_globals->combine($results_machine);
        assert(count($results->methods()) == 0);
        assert(count($results->statements()) == 0);

        // Render all classes to a single file.
        return array("main.php" => implode("\n\n", array_map(function(Gen\GClass $cl) {
            return $cl->render(0);
        }, $results->classes())));
    }

    //---------------------
    // THE MACHINE
    //---------------------

    protected function compile_globals(Gen $g, array $bindings) {
        $results = $this->results();
        array_map(function(Lang\Binding $binding) use ($g, $results) {
            $var_name = $binding->variable()->name();
            $class_name = $g->class_name($var_name);

            $sub_result = $this->compile_lambda($g, $binding->lambda(), $class_name);
            assert(count($sub_result->methods()) == 0);
            assert(count($sub_result->globals()) == 0);
            assert(count($sub_result->statements()) == 0);

            $results->add($sub_result);
            // This line (the $var_name) depends on code generated in machine_construct.
            $results->addGlobal($var_name, "new $class_name(\$$var_name)");

        }, $bindings);
        return $results;
    }

    protected function compile_machine(Gen $g, $stg_class_name, array $bindings, array $globals) {
        $results = $this->results();
        $results->addClass($g->_class
            ( $stg_class_name
            , array() // no props
            , array
                ( $g->public_method( "__construct", array()
                    , $this->compile_machine_construct($g, $bindings, $globals)
                    )
                )
            , "\\Lechimp\\STG\\STG"
            ));
        return $results;
    }

    protected function compile_machine_construct(Gen $g, array $bindings, array $globals) {
        return array_flatten

            // Create arrays for the free variables of the global closures.
            ( array_map(function(Lang\Binding $binding) use ($g) {
                $closure_name = $binding->variable()->name();
                return array
                    ( array($g->stmt("\$$closure_name = array()"))
                    , array_map(function(Lang\Variable $free_var) use ($g, $closure_name) {
                        $var_name = $free_var->name();
                        return $g->stmt("\${$closure_name}[\"$var_name\"] = null");
                    }, $binding->lambda()->free_variables()));
            }, $bindings)

            // Create the array containing the globals.
            , $g->stmt(function($ind) use ($g, $globals) { return
                "{$ind}\$globals = ".$g->multiline_dict($ind, $globals).";";})

            // Fill the previously generated arrays with contents from globals.
            , array_map(function(Lang\Binding $binding) use ($g) {
                $closure_name = $binding->variable()->name();
                return array_map(function(Lang\Variable $free_var) use ($g, $closure_name) {
                    $var_name = $free_var->name();
                    return $g->stmt("\${$closure_name}[\"$var_name\"] = \$globals[\"$var_name\"]");
                }, $binding->lambda()->free_variables());
            }, $bindings)

            // Use parents constructor.
            , $g->stmt(function($ind) use ($globals) { return
                "{$ind}parent::__construct(\$globals);"; })
            );
    }

    //---------------------
    // LAMBDAS
    //---------------------

    protected function compile_lambda(Gen $g, Lang\Lambda $lambda, $class_name) {
        assert(is_string($class_name));

        $var_names = array_map(function(Lang\Variable $var) {
            return '"'.$var->name().'"';
        }, $lambda->free_variables());

        list($compiled_expression, $additional_methods, $additional_classes)
             = $this->compile_expression($g, $lambda->expression());

        $results = $this->results();
        $results->addClass( $g->_class
            ( $class_name
            , array
                (
                )
            , array_flatten
                ( $g->public_method("entry_code", $g->stg_args()
                     , array_merge
                        ( $this->compile_lambda_entry_code($g, $lambda)
                        , $compiled_expression
                        )
                     )

                // Required method for concrete STGClosures.
                , $g->public_method("free_variables_names", array(), array
                    ( $g->stmt(function($ind) use ($g, $var_names) { return
                        "{$ind}return ".$g->multiline_array($ind, $var_names).";";
                    })))
                , $additional_methods
                )
            , "\\Lechimp\\STG\\STGClosure"
            ));

        foreach($additional_classes as $cls) {
            $results->add($cls);
        }
        return $results;
    }

    protected function compile_lambda_entry_code(Gen $g, Lang\Lambda $lambda) {
        $num_args = count($lambda->arguments());
        return array_flatten
            ( $g->init_local_env()

            // Get the free variables into the local env.
            , array_map(function(Lang\Variable $free_var) use ($g) {
                return $g->free_var_to_local_env($free_var->name());
            }, $lambda->free_variables())

            // Get the arguments into the local env.
            , array_map(function(Lang\Variable $argument) use ($g) {
                return $g->stg_pop_arg_to_local_env($argument->name());
            }, $lambda->arguments())
            );
    }

    //---------------------
    // EXPRESSIONS
    //---------------------

    protected function compile_expression(Gen $g, Lang\Expression $expression) {
        if ($expression instanceof Lang\Application) {
            return $this->compile_application($g, $expression);
        }
        if ($expression instanceof Lang\Constructor) {
            return $this->compile_constructor($g, $expression);
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
    // APPLICATIONS
    //---------------------

    protected function compile_application(Gen $g, Lang\Application $application) {
        $var_name = $application->variable()->name();
        return array
            ( array_flatten
                ( array_map(function($atom) use ($g) {
                    return $g->stg_push_arg($this->compile_atom($g, $atom));
                }, $application->atoms())
                , $g->stg_enter_local_env($var_name)
                )
            , array()
            , array()
            );
    }

    //---------------------
    // CONSTRUCTORS
    //---------------------

    protected function compile_constructor(Gen $g, Lang\Constructor $constructor) {
        $id = $constructor->id();

        $args_vector = array_map(function(Lang\Atom $atom) use ($g) {
            return $this->compile_atom($g, $atom);
        }, $constructor->atoms());

        return array(array
            ( $g->stg_pop_return_to("return_vector")
            , $g->if_then_else
                ( "array_key_exists(\"$id\", \$return_vector)"
                , array
                    ( $g->stmt(function($ind) use ($g, $args_vector) { return
                        "{$ind}\$args_vector = ".$g->multiline_array($ind, $args_vector).";"; })
                    , $g->stg_push_return('$args_vector')
                    , $g->stmt("return \$return_vector[\"$id\"]")
                    )
                , array( $g->if_then_else
                    ( "array_key_exists(\"\", \$return_vector)"
                    , array
                        ( $g->stg_push_return('$this')
                        , $g->stmt("return \$return_vector[\"\"]")
                        )
                    , array
                        ( $g->stmt("throw new \\LogicException(".
                                    "\"No matching alternative for constructor '$id'\"".
                                 ")")
                        )
                    ))
                ) 
            )
            , array()
            , array()
            );
    }

    //---------------------
    // LITERALS
    //---------------------

    protected function compile_literal(Gen $g, Lang\Literal $literal) {
        $value = $literal->value();

        return array    
            ( array_flatten
                ( $g->stmt("\$primitive_value = $value")
                , $this->compile_primitive_value_jump($g)
                )
            , array()
            , array()
            );
    }

    protected function compile_primitive_value_jump(Gen $g) {
        return array
            ( $g->stg_pop_return_to("return_vector")
            , $g->if_then_else
                ( "array_key_exists(\$primitive_value, \$return_vector)"
                , array
                    ( $g->stmt("return \$return_vector[\$primitive_value]")
                    )
                , array ( $g->if_then_else
                    ( "array_key_exists(\"\", \$return_vector)"
                    , array
                        ( $g->stg_push_return("\$primitive_value")
                        , $g->stmt("return \$return_vector[\"\"]")
                        )
                    , array
                        ( $g->stmt("throw new \\LogicException(".
                                    "\"No matching alternative for '\$primitive_value'\"".
                                 ")")
                        )
                    ))
                )
            );
    }

    //---------------------
    // CASE EXPRESSIONS
    //---------------------

    protected function compile_case_expression(Gen $g, Lang\CaseExpr $case_expression) {
        $methods = array();
        $return_vector = array();


        $this->compile_alternatives($g, $case_expression->alternatives(), $return_vector, $methods);

        list($sub_statements, $sub_methods)
            = $this->compile_expression($g, $case_expression->expression());

        $statements = array
            ( $g->stmt(function($ind) use ($g, $return_vector) { return 
                "$ind\$return_vector = ".$g->multiline_dict($ind, $return_vector).";";})
            , $g->stg_push_local_env()
            , $g->stg_push_return('$return_vector')
            );

        return array
            ( array_merge($statements, $sub_statements)
            , array_merge($methods, $sub_methods)
            , array()
            );
    }

    protected function compile_alternatives(Gen $g, array $alternatives, array &$return_vector, array &$methods) {
        foreach($alternatives as $alternative) {
            $this->compile_alternative($g, $alternative, $return_vector, $methods);
        }
    } 
    
    protected function compile_alternative(Gen $g, Lang\Alternative $alternative, array &$return_vector, array &$methods) {
        if ($alternative instanceof Lang\DefaultAlternative) {
            list($r_code, $method_name)
                 = $this->compile_alternative_default($g, $alternative, $return_vector, $methods);
        }
        else if ($alternative instanceof Lang\PrimitiveAlternative) {
            list($r_code, $method_name)
                = $this->compile_alternative_primitive($g, $alternative, $return_vector, $methods);
        }
        else if ($alternative instanceof Lang\AlgebraicAlternative) {
            list($r_code, $method_name)
                = $this->compile_alternative_algebraic($g, $alternative, $return_vector, $methods);
        }
        else {
            throw new \LogicException("Unknown alternative class ".get_class($alternative));
        }

        list($m_code, $sub_methods)
            = $this->compile_expression($g, $alternative->expression());
        $methods[] = $g->public_method($method_name, $g->stg_args(), array_merge($r_code, $m_code));
        $methods = array_merge($methods, $sub_methods);
    }

    // Return code that needs to be executed for every alternative. It restores
    // the environment for the alternative.
    protected function compile_alternative_common_return_code(Gen $g) {
        return array
            ( $g->stg_pop_local_env()
            );
    }

    protected function compile_alternative_default(Gen $g, Lang\DefaultAlternative $alternative, array &$return_vector, array &$methods) {
        $method_name = $g->method_name("alternative_default");
        $return_vector[null] = $g->code_label($method_name);
        $bind_to = $alternative->variable();
        if ($bind_to === null) {
            $r_code = array_flatten
                ( $this->compile_alternative_common_return_code($g)
                // We won't need the value from the constructor.
                , $g->stg_pop_return()
                );
        }
        else {
            $var_name = $bind_to->name();
            $r_code = array_flatten
                ( $this->compile_alternative_common_return_code($g)
                // Save value from constructor in local env.
                , $g->stg_pop_return_to_local_env($var_name)
                );
        }
        return array($r_code, $method_name);
    }


    protected function compile_alternative_primitive(Gen $g, Lang\PrimitiveAlternative $alternative, array &$return_vector, array &$methods) {
        $value = $alternative->literal()->value();
        assert(is_int($value));
        $method_name = $g->method_name("alternative_$value");
        $return_vector[$value] = $g->code_label($method_name);
        $r_code = $this->compile_alternative_common_return_code($g);
        return array($r_code, $method_name);
    }

    protected function compile_alternative_algebraic(Gen $g, Lang\AlgebraicAlternative $alternative, array &$return_vector, array &$methods) {
        $id = $alternative->id();
        $method_name = $g->method_name("alternative_$id");
        $return_vector[$id] = $g->code_label($method_name);
        // Pop arguments to constructor and fill them into appropriate variables.
        $r_code = array_flatten
            ( $this->compile_alternative_common_return_code($g)
            , $g->stg_pop_return_to("arg_vector")
            , array_map(function(Lang\Variable $var) use ($g) {
                $name = $var->name();
                return $g->stmt("\$local_env[\"$name\"] = array_shift(\$arg_vector)");
            }, $alternative->variables())
            );
        return array($r_code, $method_name);
    }

    //---------------------
    // LET BINDINGS
    //---------------------

    protected function compile_let_binding(Gen $g, Lang\LetBinding $let_binding) {
        list($expr_code, $expr_methods, $expr_classes)
            = $this->compile_expression($g, $let_binding->expression());

        // Cashes fresh class names in the first iteration as they are needed
        // in the second iteration.
        $class_names = array();
    
        return array
            ( array_flatten
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
                        , $g->stmt("\$local_env[\"$name\"] = new $class_name(\$free_vars_$name)")
                        );
                }, $let_binding->bindings())
                , $expr_code
                )
            , $expr_methods
            , array_flatten
                ( array_map(function(Lang\Binding $binding) use ($g, &$class_names) {
                    $class_name = array_shift($class_names);
                    return $this->compile_lambda($g, $binding->lambda(), $class_name);
                }, $let_binding->bindings()) 
                , $expr_classes
                )
            );
    }

    //---------------------
    // LET REC BINDINGS
    //---------------------

    protected function compile_letrec_binding(Gen $g, Lang\LetRecBinding $letrec_binding) {
        list($expr_code, $expr_methods, $expr_classes)
            = $this->compile_expression($g, $letrec_binding->expression());

        // Cashes fresh class names in the first iteration as they are needed
        // in the third iteration.
        $class_names = array();
    
        return array
            ( array_flatten

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
                        , $g->stmt("\$local_env[\"$name\"] = new $class_name(\$free_vars_$name)")
                        );
                }, $letrec_binding->bindings())

                // Then bind the stubs to the new variables.
                , array_map( function(Lang\Binding $binding) use ($g) {
                    $name = $binding->variable()->name();
                    return array_map(function(Lang\Variable $free_var) use ($g, $name) {
                        $fname = $free_var->name();
                        return $g->stmt("\$free_vars_{$name}[\"$fname\"] = \$local_env[\"$fname\"]");
                    }, $binding->lambda()->free_variables());
                }, $letrec_binding->bindings()) 
                , $expr_code
                )
            , $expr_methods
            , array_flatten
                ( array_map(function(Lang\Binding $binding) use ($g, &$class_names) {
                    $class_name = array_shift($class_names);
                    return $this->compile_lambda($g, $binding->lambda(), $class_name);
                }, $letrec_binding->bindings()) 
                , $expr_classes
                )
            );
    }

    //---------------------
    // ATOMS
    //---------------------

    protected function compile_atom(Gen $g, Lang\Atom $atom) {
        $stg = self::STG_VAR_NAME;
        if ($atom instanceof Lang\Variable) {
            $var_name = $atom->name();
            return "\$local_env[\"$var_name\"]"; 
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

    protected function compile_prim_op(Gen $g, Lang\PrimOp $prim_op) {
        $id = $prim_op->id();
        $method_name = "compile_prim_op_$id";
        return $this->$method_name($g, $prim_op->atoms());
    }

    protected function compile_prim_op_IntAddOp(Gen $g, array $atoms) {
        assert(count($atoms) == 2);
        list($l, $r) = $atoms;
        $left = $this->compile_atom($g, $l);
        $right = $this->compile_atom($g, $r);
        return array
            ( array_flatten
                ( $g->stmt("\$primitive_value = $left + $right")
                , $this->compile_primitive_value_jump($g)
                )
            , array()
            , array()
            );
    }

    protected function compile_prim_op_IntSubOp(Gen $g, array $atoms) {
         assert(count($atoms) == 2);
        list($l, $r) = $atoms;
        $left = $this->compile_atom($g, $l);
        $right = $this->compile_atom($g, $r);
        return array
            ( array_flatten
                ( $g->stmt("\$primitive_value = $left - $right")
                , $this->compile_primitive_value_jump($g)
                )
            , array()
            , array()
            );       
    }

    protected function compile_prim_op_IntMulOp(Gen $g, array $atoms) {
        assert(count($atoms) == 2);
        list($l, $r) = $atoms;
        $left = $this->compile_atom($g, $l);
        $right = $this->compile_atom($g, $r);
        return array
            ( array_flatten
                ( $g->stmt("\$primitive_value = $left * $right")
                , $this->compile_primitive_value_jump($g)
                )
            , array()
            , array()
            );      
    }

/* for copy:
    protected function compile_prim_op_IntMulOp(Gen $g, array $atoms) {
        
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
