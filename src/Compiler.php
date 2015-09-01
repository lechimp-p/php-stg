<?php

namespace Lechimp\STG;

/**
 * Compiles expressions from the stg language to php-based STG
 * code.
 */
class Compiler {
    const STG_VAR_NAME = 'stg';

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

        // Constant used when rendering.
        $rc = array
            ( "ns" => $namespace // Namespace
            , 'stg' => self::STG_VAR_NAME  // variable name for stg
            , 'index' => 0 // An index to create unique names.
            );

        $globals = array();

        $classes = array(); 
        
        foreach($program->bindings() as $binding) {
            $var_name = $binding->variable()->name();
            $class_name = $this->className($rc, $var_name);

            // Closures for global lambdas.
            $classes[] = $this->compile_lambda($rc, $binding->lambda(), $class_name);

            // Instantiation of globals for STG class.
            $globals[$var_name] = "new $class_name(\$$var_name)";
        }
        
        // Class for the final stg machine
        $classes[] = g_class( $rc["ns"], $stg_class_name
            , array() // no props
            , array
                ( g_public_method( "__construct", array(), array_flatten

                    // Create arrays for the free variables of the global closures.
                    ( array_map(function(Lang\Binding $binding) {
                        $closure_name = $binding->variable()->name();
                        return array
                            ( array(g_stmt("\$$closure_name = array()"))
                            , array_map(function(Lang\Variable $free_var) use ($closure_name) {
                                $var_name = $free_var->name();
                                return g_stmt("\${$closure_name}[\"$var_name\"] = null");
                            }, $binding->lambda()->free_variables()));
                    }, $program->bindings())

                    // Create the array containing the globals.
                    , g_stmt(function($ind) use ($globals) { return
                        "{$ind}\$globals = ".g_multiline_dict($ind, $globals).";";})

                    // Fill the previously generated arrays with contents from globals.
                    , array_map(function(Lang\Binding $binding) {
                        $closure_name = $binding->variable()->name();
                        return array_map(function(Lang\Variable $free_var) use ($closure_name) {
                            $var_name = $free_var->name();
                            return g_stmt("\${$closure_name}[\"$var_name\"] = \$globals[\"$var_name\"]");
                        }, $binding->lambda()->free_variables());
                    }, $program->bindings())

                    // Use parents constructor.
                    , g_stmt(function($ind) use ($globals) { return
                        "{$ind}parent::__construct(\$globals);"; })
                    )
                ))
            , "\\Lechimp\\STG\\STG"
        );

        // Render all classes to a single file.
        return array("main.php" => implode("\n\n", array_map(function(Gen\GClass $cl) {
            return $cl->render(0);
        }, array_flatten($classes))));
    }

    protected function compile_lambda(array &$rc, Lang\Lambda $lambda, $class_name) {
        assert(is_string($class_name));

        $num_args = count($lambda->arguments());
        $var_names = array_map(function(Lang\Variable $var) {
            return '"'.$var->name().'"';
        }, $lambda->free_variables());

        list($compiled_expression, $additional_methods, $additional_classes)
             = $this->compile_expression($rc, $lambda->expression());

        return array_flatten
            ( g_class($rc["ns"], $class_name
                , array
                    (
                    )
                , array_merge
                    ( array
                        ( g_public_method("entry_code", g_stg_args(), array_flatten(array
                            ( g_stmt("assert(\${$rc['stg']}->count_args() >= $num_args)")
                            , g_stmt("\$local_env = array()")
                            
                            // Get the free variables into the local env. 
                            , array_map(function(Lang\Variable $free_var) {
                                $var_name = $free_var->name();
                                return g_stmt("\$local_env[\"$var_name\"] = \$this->free_variables[\"$var_name\"]");
                            }, $lambda->free_variables())

                            // Get the arguments into the local env.
                            , array_map(function(Lang\Variable $argument) use (&$rc) {
                                $arg_name = $argument->name();
                                return g_stg_pop_arg_to($rc["stg"], "local_env[\"$arg_name\"]");
                            }, $lambda->arguments())

                            , $compiled_expression
                            )))

                        // Required method for concrete STGClosures.
                        , g_public_method("free_variables_names", array(), array
                            ( g_stmt(function($ind) use ($var_names) { return
                                "{$ind}return ".g_multiline_array($ind, $var_names).";";
                            })))
                        )
                    , $additional_methods
                    )
                , "\\Lechimp\\STG\\STGClosure")
            , $additional_classes
            );
    }

    protected function compile_expression(array &$rc, Lang\Expression $expression) {
        if ($expression instanceof Lang\Application) {
            return $this->compile_application($rc, $expression);
        }
        if ($expression instanceof Lang\Constructor) {
            return $this->compile_constructor($rc, $expression);
        }
        if ($expression instanceof Lang\CaseExpr) {
            return $this->compile_case_expression($rc, $expression);
        }
        if ($expression instanceof Lang\LetBinding) {
            return $this->compile_let_binding($rc, $expression);
        }
        if ($expression instanceof Lang\LetRecBinding) {
            return $this->compile_letrec_binding($rc, $expression);
        }
        if ($expression instanceof Lang\Literal) {
            return $this->compile_literal($rc, $expression);
        }
        throw new \LogicException("Unknown expression '".get_class($expression)."'.");
    }

    protected function compile_application(array &$rc, Lang\Application $application) {
        $var_name = $application->variable()->name();
        return array
            ( array_merge
                ( array_map(function($atom) use (&$rc) {
                    return g_stg_push_arg($rc["stg"], $this->compile_atom($rc, $atom));
                }, $application->atoms())
                , array(g_stg_enter($rc["stg"], "\$local_env[\"$var_name\"]"))
                )
            , array()
            , array()
            );
    }

    protected function compile_constructor(array &$rc, Lang\Constructor $constructor) {
        $id = $constructor->id();

        $args_vector = array_map(function(Lang\Atom $atom) use (&$rc) {
            return $this->compile_atom($rc, $atom);
        }, $constructor->atoms());

        return array(array
            ( g_stg_pop_return_to($rc["stg"], "return_vector")
            , g_if_then_else
                ( "array_key_exists(\"$id\", \$return_vector)"
                , array
                    ( g_stmt(function($ind) use ($args_vector) { return
                        "{$ind}\$args_vector = ".g_multiline_array($ind, $args_vector).";"; })
                    , g_stg_push_return($rc["stg"], '$args_vector')
                    , g_stmt("return \$return_vector[\"$id\"]")
                    )
                , array( g_if_then_else
                    ( "array_key_exists(\"\", \$return_vector)"
                    , array
                        ( g_stg_push_return($rc["stg"], '$this')
                        , g_stmt("return \$return_vector[\"\"]")
                        )
                    , array
                        ( g_stmt("throw new \\LogicException(".
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

    protected function compile_literal(array &$rc, Lang\Literal $literal) {
        $value = $literal->value();

        return array(array
            ( g_stg_pop_return_to($rc["stg"], "return_vector")
            , g_if_then_else
                ( "array_key_exists($value, \$return_vector)"
                , array
                    ( g_stmt("return \$return_vector[$value]")
                    )
                , array ( g_if_then_else
                    ( "array_key_exists(\"\", \$return_vector)"
                    , array
                        ( g_stg_push_return($rc["stg"], "$value")
                        , g_stmt("return \$return_vector[\"\"]")
                        )
                    , array
                        ( g_stmt("throw new \\LogicException(".
                                    "\"No matching alternative for literal $value\"".
                                 ")")
                        )
                    ))
                )
            )
            , array()
            , array()
            );
    }

    protected function compile_case_expression(array &$rc, Lang\CaseExpr $case_expression) {
        $methods = array();
        $return_vector = array();

        // Return code that needs to be executed for every alternative. It restores
        // the environment for the alternative.
        $default_return_code = array
            ( g_stg_pop_env_to($rc["stg"], "local_env")
            );

        foreach($case_expression->alternatives() as $alternative) {
            // TODO: Most probably the generation of names for the methods needs
            //       to be changed, as this will name crash on nested case expressions.
            if ($alternative instanceof Lang\DefaultAlternative) {
                $method_name = $this->methodName($rc, "alternative_default");
                $return_vector[null] = g_code_label($method_name);
                $bind_to = $alternative->variable();
                if ($bind_to === null) {
                    $r_code = array_flatten
                        ( $default_return_code
                        // We won't need the value from the constructor.
                        , g_stg_pop_return_to($rc["stg"], "null")
                        );
                }
                else {
                    $var_name = $bind_to->name();
                    $r_code = array_flatten
                        ( $default_return_code
                        // Save value from constructor in local env.
                        , g_stg_pop_return_to($rc["stg"], "local_env[\"$var_name\"]")
                        );
                }
            }
            else if ($alternative instanceof Lang\PrimitiveAlternative) {
                $value = $alternative->literal()->value();
                assert(is_int($value));
                $method_name = $this->methodName($rc, "alternative_$value");
                $return_vector[$value] = g_code_label($method_name);
                $r_code = $default_return_code;
            }
            else if ($alternative instanceof Lang\AlgebraicAlternative) {
                $id = $alternative->id();
                $method_name = $this->methodName($rc, "alternative_$id");
                $return_vector[$id] = g_code_label($method_name);
                // Pop arguments to constructor and fill them into appropriate variables.
                $r_code = array_flatten
                    ( $default_return_code
                    , g_stg_pop_return_to($rc["stg"], "arg_vector")
                    , array_map(function(Lang\Variable $var) {
                        $name = $var->name();
                        return g_stmt("\$local_env[\"$name\"] = array_shift(\$arg_vector)");
                    }, $alternative->variables())
                    );
            }
            else {
                throw new \LogicException("Unknown alternative class ".get_class($alternative));
            }

            list($m_code, $sub_methods)
                = $this->compile_expression($rc, $alternative->expression());
            $methods[] = g_public_method($method_name, g_stg_args(), array_merge($r_code, $m_code));
            $methods = array_merge($methods, $sub_methods);
        }

        list($sub_statements, $sub_methods)
            = $this->compile_expression($rc, $case_expression->expression());

        $statements = array
            ( g_stmt(function($ind) use ($return_vector) { return 
                "$ind\$return_vector = ".g_multiline_dict($ind, $return_vector).";";})
            , g_stg_push_env($rc["stg"], '$local_env')
            , g_stg_push_return($rc["stg"], '$return_vector')
            );

        return array
            ( array_merge($statements, $sub_statements)
            , array_merge($methods, $sub_methods)
            , array()
            );
    }

    protected function compile_let_binding(array &$rc, Lang\LetBinding $let_binding) {
        list($expr_code, $expr_methods, $expr_classes)
            = $this->compile_expression($rc, $let_binding->expression());

        // Cashes fresh class names in the first iteration as they are needed
        // in the second iteration.
        $class_names = array();
    
        return array
            ( array_flatten
                ( array_map( function(Lang\Binding $binding) use (&$rc, &$class_names) {
                    // TODO: I need to introduce a correct naming scheme to avoid
                    //       name clashes.
                    $name = $binding->variable()->name();
                    $class_name = $this->className($rc, $name);
                    $class_names[] = $class_name;
                    return array_flatten
                        ( g_stmt("\$free_vars_$name = array()")
                        , array_map(function(Lang\Variable $free_var) use ($name) {
                            $fname = $free_var->name();
                            return g_stmt("\$free_vars_{$name}[\"$fname\"] = \$local_env[\"$fname\"]");
                        }, $binding->lambda()->free_variables())
                        , g_stmt("\$local_env[\"$name\"] = new $class_name(\$free_vars_$name)")
                        );
                }, $let_binding->bindings())
                , $expr_code
                )
            , $expr_methods
            , array_flatten
                ( array_map(function(Lang\Binding $binding) use (&$rc, &$class_names) {
                    // TODO: I need to introduce a correct naming scheme to avoid
                    //       name clashes.
                    $class_name = array_shift($class_names);
                    return $this->compile_lambda($rc, $binding->lambda(), $class_name);
                }, $let_binding->bindings()) 
                , $expr_classes
                )
            );
    }

    protected function compile_letrec_binding(array &$rc, Lang\LetRecBinding $letrec_binding) {
        list($expr_code, $expr_methods, $expr_classes)
            = $this->compile_expression($rc, $letrec_binding->expression());

        // Cashes fresh class names in the first iteration as they are needed
        // in the third iteration.
        $class_names = array();
    
        return array
            ( array_flatten

                // First create the closures with stubs for free variables
                ( array_map( function(Lang\Binding $binding) use (&$rc, &$class_names) {
                    // TODO: I need to introduce a correct naming scheme to avoid
                    //       name clashes.
                    $name = $binding->variable()->name();
                    $class_name = $this->className($rc, $name);
                    $class_names[] = $class_name;
                    return array_flatten
                        ( g_stmt("\$free_vars_$name = array()")
                        , array_map(function(Lang\Variable $free_var) use ($name) {
                            $fname = $free_var->name();
                            return g_stmt("\$free_vars_{$name}[\"$fname\"] = null");
                        }, $binding->lambda()->free_variables())
                        , g_stmt("\$local_env[\"$name\"] = new $class_name(\$free_vars_$name)")
                        );
                }, $letrec_binding->bindings())

                // Then bind the stubs to the new variables.
                , array_map( function(Lang\Binding $binding) {
                    $name = $binding->variable()->name();
                    return array_map(function(Lang\Variable $free_var) use ($name) {
                        $fname = $free_var->name();
                        return g_stmt("\$free_vars_{$name}[\"$fname\"] = \$local_env[\"$fname\"]");
                    }, $binding->lambda()->free_variables());
                }, $letrec_binding->bindings()) 
                , $expr_code
                )
            , $expr_methods
            , array_flatten
                ( array_map(function(Lang\Binding $binding) use (&$rc, &$class_names) {
                    // TODO: I need to introduce a correct naming scheme to avoid
                    //       name clashes.
                    $class_name = array_shift($class_names);
                    return $this->compile_lambda($rc, $binding->lambda(), $class_name);
                }, $letrec_binding->bindings()) 
                , $expr_classes
                )
            );
    }

    protected function compile_atom(array &$rc, Lang\Atom $atom) {
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

    protected function className(array &$rc, $name) {
        assert(is_string($name));
        $i = $rc["index"];
        $rc["index"]++;
        return ucfirst($name)."_{$i}_Closure";
    }

    protected function methodName(array &$rc, $name) {
        assert(is_string($name));
        $i = $rc["index"];
        $rc["index"]++;
        return $name."_$i";
    }
} 

function g_class($namespace, $name, $properties, $methods, $extends = null) {
    return new Gen\GClass($namespace, $name, $properties, $methods, $extends);
}

function g_public_method($name, $arguments, $statements) {
    return new Gen\GPublicMethod($name, $arguments, $statements);
}

function g_stg_args() {
    return array(new Gen\GArgument("\\Lechimp\\STG\\STG", Compiler::STG_VAR_NAME));
}

function g_stmt($code) {
    return new Gen\GStatement($code);
}

function g_if_then_else($if, $then, $else) {
    return new Gen\GIfThenElse($if, $then, $else);
}

function g_multiline_dict($ind, array $array) {
    return 
        "array\n$ind    ( ".
        implode("\n$ind    , " , array_map(function($v, $k = null) {
            if (is_null($k) || $k == "") {
                return "\"\" => $v";
            }
            if (is_string($k)) {
                return "\"$k\" => $v";
            }
            if (is_int($k)) {
                return "$k => $v";
            }
            throw new \LogicException("Can't render multiline dict with key"
                                     ." of type '".gettype($k));
        }, $array, array_keys($array))).
        "\n$ind    )";
        
}

function g_multiline_array($ind, array $array) {
    return 
        "array\n$ind    ( ".
        implode("\n$ind    , " , array_map(function($v) {
            return "$v";
        }, $array)).
        "\n$ind    )";
}

function g_var($varname) {
    return "\$$varname";
}

function g_stg_pop_arg_to($stg_name, $arg_name) {
    return new Gen\GStatement("\$$arg_name = \${$stg_name}->pop_arg()");
}

function g_stg_push_arg($stg_name, $what) {
    return new Gen\GStatement("\${$stg_name}->push_arg($what)");
}

function g_stg_enter($stg_name, $where) {
    return new Gen\GStatement("return \${$stg_name}->enter($where)");
}

function g_stg_global_var($stg_name, $var_name) {
    return "\${$stg_name}->global_var(\"$var_name\")";
}

function g_stg_pop_return_to($stg_name, $to) {
    return new Gen\GStatement("\${$to} = \${$stg_name}->pop_return()");
}

function g_stg_push_return($stg_name, $what) {
    return new Gen\GStatement("\${$stg_name}->push_return($what)");
}

function g_stg_pop_env_to($stg_name, $to) {
    return new Gen\GStatement("\${$to} = \${$stg_name}->pop_env()");
}

function g_stg_push_env($stg_name, $what) {
    return new Gen\GStatement("\${$stg_name}->push_env($what)");
}

function g_code_label($method_name) {
    return "new \\Lechimp\\STG\\CodeLabel(\$this, \"$method_name\")";
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
