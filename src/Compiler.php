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
            );

        $globals = array();

        $classes = array(); 
        
        foreach($program->bindings() as $binding) {
            $var_name = $binding->variable()->name();
            $class_name = ucfirst($var_name)."Closure";

            // Closures for global lambdas.
            $classes[] = $this->compile_lambda($rc, $binding->lambda(), $class_name);

            // Instantiation of globals for STG class.
            $globals[$var_name] = "new $class_name(\$$var_name)";
        }
        
        // Class for the final stg machine
        $classes[] = g_class( $rc["ns"], $stg_class_name
            , array() // no props
            , array
                ( g_public_method( "__construct", array(), array_flatten(array

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
                        "{$ind}\$globals = ".g_multiline_dict("$ind    ", $globals).";";})

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
                    ))
                ))
            , "\\Lechimp\\STG\\STG"
        );

        // Render all classes to a single file.
        return array("main.php" => implode("\n\n", array_map(function(Gen\GClass $cl) {
            return $cl->render(0);
        }, $classes)));
    }

    protected function compile_lambda(array $rc, Lang\Lambda $lambda, $class_name) {
        assert(is_string($class_name));

        $num_args = count($lambda->arguments());
        $var_names = array_map(function(Lang\Variable $var) {
            return '"'.$var->name().'"';
        }, $lambda->free_variables());

        list($compiled_expression, $additional_methods)
             = $this->compile_expression($rc, $lambda->expression());

        return g_class($rc["ns"], $class_name
            , array
                (
                )
            , array_merge
                ( array
                    ( g_public_method("entry_code", g_stg_args(), array_flatten(array
                        ( g_stmt("assert(\${$rc['stg']}->count_args() >= $num_args)")
                        , array_map(function(Lang\Variable $free_var) {
                            $var_name = $free_var->name();
                            return g_stmt("\$_$var_name = \$this->free_variables[\"$var_name\"]");
                        }, $lambda->free_variables())
                        , array_map(function(Lang\Variable $argument) use ($rc) { return
                            g_stg_pop_arg_to($rc["stg"], "_".$argument->name());
                        }, $lambda->arguments())
                        , $compiled_expression
                        )))
                    , g_public_method("free_variables_names", array(), array
                        ( g_stmt(function($ind) use ($var_names) { return
                            "{$ind}return ".g_multiline_array("$ind    ", $var_names).";";
                        })))
                    )
                , $additional_methods
                )
            , "\\Lechimp\\STG\\STGClosure"
            );
    }

    protected function compile_expression(array $rc, Lang\Expression $expression) {
        if ($expression instanceof Lang\Application) {
            return $this->compile_application($rc, $expression);
        }
        if ($expression instanceof Lang\Constructor) {
            return $this->compile_constructor($rc, $expression);
        }
        if ($expression instanceof Lang\CaseExpr) {
            return $this->compile_case_expression($rc, $expression);
        }
        throw new \LogicException("Unknown expression '$expression'.");
    }

    protected function compile_application(array $rc, Lang\Application $application) {
        $var_name = $application->variable()->name();
        return array
            ( array_merge
                ( array_map(function($atom) use ($rc) {
                    return g_stg_push_arg($rc["stg"], $this->compile_atom($rc, $atom));
                }, $application->atoms())
                , array(g_stg_enter($rc["stg"], "\$_$var_name"))
                )
            , array()
            );
    }

    protected function compile_constructor(array $rc, Lang\Constructor $constructor) {
        $stg = self::STG_VAR_NAME;
        $rvn = '$return_vector';

        $id = $constructor->id();

        return array(array
            ( g_stg_pop_return_to($rc["stg"], "return_vector")
            , g_stmt(function($i) use ($id) { return
                 "{$i}if(array_key_exists(\"$id\", \$return_vector)) {\n"
            ."    {$i}    return \$return_vector[\"$id\"];\n"
            ."    {$i}}\n"
            ."    {$i}else if (array_key_exists(null, \$return_vector)) {\n"
            ."    {$i}    return \$return_vector[null];\n"
            ."    {$i}}\n"
            ."    {$i}else {\n"
            ."    {$i}    throw new \\LogicException(\n"
            ."    {$i}        \"No matching alternative for constructor '$id'.\"\n"
            ."    {$i}    );\n"
            ."    {$i}}\n";
                })
            )
            , array()
            );
    }

    protected function compile_case_expression(array $rc, Lang\CaseExpr $case_expression) {
        $methods = array();
        $return_vector = array();

        foreach($case_expression->alternatives() as $alternative) {
            // TODO: Most probably the generation of names for the methods needs
            //       to be changed, as this will name crash on nested case expressions.
            if ($alternative instanceof Lang\DefaultAlternative) {
                $method_name = "alternative_default";
                $return_vector[null] = g_code_label($method_name);
            }
            else if ($alternative instanceof Lang\PrimitiveAlternative) {
                $value = $alternative->literal()->value();
                assert(is_int($value));
                $method_name = "alternative_$value";
                $return_vector[$value] = g_code_label($method_name);
            }
            else if ($alternative instanceof Lang\AlgebraicAlternative) {
                $id = $alternative->id();
                $method_name = "alternative_$id";
                $return_vector[$id] = g_code_label($method_name);
            }
            else {
                throw new \LogicException("Unknown alternative class ".get_class($alternative));
            }

            list($m_code, $sub_methods)
                = $this->compile_expression($rc, $alternative->expression());
            $methods[] = g_public_method($method_name, g_stg_args(), $m_code);
            $methods = array_merge($methods, $sub_methods);
        }

        list($sub_statements, $sub_methods)
            = $this->compile_expression($rc, $case_expression->expression());

        $statements = array
            ( g_stmt(function($ind) use ($return_vector) { return 
                "$ind\$return_vector = ".g_multiline_dict("$ind    ", $return_vector).";";})
            , g_stg_push_return($rc["stg"], '$return_vector')
            );

        return array
            ( array_merge($statements, $sub_statements)
            , array_merge($methods, $sub_methods)
            );
    }

    protected function compile_atom(array $rc, Lang\Atom $atom) {
        $stg = self::STG_VAR_NAME;
        if ($atom instanceof Lang\Variable) {
            $var_name = $atom->name();
            return "\$_$var_name"; 
        }
        if ($atom instanceof Lang\Literal) {
            return $literal->value();
        }
        throw new \LogicException("Unknown atom '$atom'.");
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

function g_multiline_dict($ind, array $array) {
    return 
        "array\n$ind    ( ".
        implode("\n$ind    , " , array_map(function($v, $k) {
            if (is_string($k)) {
                return "\"$k\" => $v";
            }
            if (is_int($k)) {
                return "$k => $v";
            }
            assert($k === null);
            return "null => $v";
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

function g_code_label($method_name) {
    return "new \\Lechimp\\STG\\CodeLabel(\$this, \"$method_name\")";
}

function array_flatten(array $array) {
    $returns = array();
    foreach($array as $val) {
        if (is_array($val)) {
            $returns = array_merge($returns, array_flatten($val));
        }
        else {
            $returns[] = $val;
        }
    }
    return $returns;
}
