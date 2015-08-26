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
    public function compile(Lang\Program $program, $stg_class_name) {
        assert(is_string($stg_class_name));

        // Constant used when rendering.
        $rc = array
            ( "ns" => "" // Namespace
            , 'stg' => self::STG_VAR_NAME;  // variable name for stg
            );

        $globals = array();
        $code = "use \\Lechimp\\STG\\STG;\n".
                "use \\Lechimp\\STG\\CodeLabel;\n".
                "use \\Lechimp\\STG\\STGClosure;\n".
                "\n\n";

        $classes = array(); 
        
        foreach($program->bindings() as $binding) {
            $var_name = $binding->variable()->name();
            $class_name = ucfirst($var_name)."Closure";

            // Closures for global lambdas.
            $classes[] = $this->compile_lambda($rc, $binding->lambda(), $class_name);

            // Instantiation of globals for STG class.
            $globals[$var_name] = "new $class_name()";
        }
        
        // Class for the final stg machine
        $classes[] = g_class( $rc["ns"], $stg_class_name
            , array() // no props
            , array
                ( g_public_method( "__construct", array(), array
                    ( g_stmt(function($ind) { return
                        "parent::__construct(".g_multiline_dict($ind, $globals_code).");"; })
                    )
                );
        );

        // Render all classes to a single file.
        $code .= implode("\n\n", array_map(function(GClass $cl) {
            return $cl->render(0);
        }, $classes));

        return array("main.php" => $code);
    }

    protected function compile_lambda(array $rc, Lang\Lambda $lambda, $class_name) {
        assert(is_string($class_name));

        $num_args = count($lambda->arguments());

        list($compiled_expression, $additional_methods)
             = $this->compile_expression($rc, $lambda->expression());

        return g_class($rc["ns"], $class_name
            , array
                (
                )
            , array_merge(array
                ( g_public_method("entry_code", g_stg_args(), array_merge(array 
                    ( g_stmt("assert(\${$rc['stg']}->count_args() >= $num_args)")
                    , g_stmt("//TODO: get free variable NYI!")
                    )
                    , array_map(function($argument) {
                            return g_stg_pop_arg_to($rc["stg"], "_".$argument->name());
                        }, $lambda->arguments())
                    , $compiled_expression
                    )
                )
                ,
                $additional_methods
                )
            , "STGClosure"
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
            ( array_merge(array_map(function($atom) {
                    return g_stg_push_arg($rc["stg"], $this->compile_atom($rc, atom)); 
                }, $application->atoms())
                ,
                g_stg_enter($rc["stg"], g_stg_global_var($rc["stg"], $var_name));
                )
            , array()
            );
    }

    protected function compile_constructor(array $rc, Lang\Constructor $constructor) {
        $stg = self::STG_VAR_NAME;
        $rvn = '$return_vector';

        $id = $constructor->id();

        return array(array
            ( g_stg_pop_return_to($rc["stg"], "return_vector)")
            , g_stmt(function($i) { return
                 "{$i}if(array_key_exists(\"$id\", \$return_vector)) {\n"
                ."{$i}    return \$return_vector[\"$id\"];\n"
                ."{$i}}\n"
                ."{$i}else if (array_key_exists(null, \$return_vector)) {\n"
                ."{$i}  return \$return_vector[null];\n"
                ."{$i}}\n"
                ."{$i}else {\n"
                ."{$i}  throw new \\LogicException(\n"
                ."{$i}      \"No matching alternative for constructor '$id'.\"\n"
                ."{$i}  );\n"
                ."{$i}}\n"
                });
            )
            , array());
    }

    protected function compile_case_expression(Lang\CaseExpr $case_expression) {
        $stg = self::STG_VAR_NAME;
        $lthis = '$this';
        $rvn = '$return_vector';

        $return_vector = array();
        $methods = array();

        foreach($case_expression->alternatives() as $alternative) {
            if ($alternative instanceof Lang\DefaultAlternative) {
                $method_name = "alternative_default";
                $return_vector[] = "null => new CodeLabel($lthis, \"$method_name\")";
            }
            else if ($alternative instanceof Lang\PrimitiveAlternative) {
                $value = $alternative->literal()->value();
                assert(is_int($value));
                $method_name = "alternative_$value";
                $return_vector[] = "$value => new CodeLabel($lthis, \"$method_name\")";
            }
            else if ($alternative instanceof Lang\AlgebraicAlternative) {
                $id = $alternative->id();
                $method_name = "alternative_$id";
                $return_vector[] = "\"$id\" => new CodeLabel($lthis, \"$method_name\")";
            }
            else {
                throw new \LogicException("Unknown alternative class ".get_class($alternative));
            }

            list($sub_code, $sub_methods) 
                = $this->compile_expression($alternative->expression());
            $methods[] =     "public function $method_name(STG {$stg}) {\n"
                       ."         ".$sub_code
                       . "    }\n";
            $methods[] = $sub_methods;
        }

        list($sub_code, $sub_methods) 
            = $this->compile_expression($case_expression->expression()); 
        $code = "$rvn = array\n            ( "
                 .implode("\n            , ",$return_vector)."\n"
                 ."            );\n"
                 ."        {$stg}->push_return($rvn);\n"
                 ."        $sub_code";
        $methods = $sub_methods."\n    ".implode("\n    ", $methods);
        return array($code, $methods);
    }

    protected function compile_atom(Lang\Atom $atom) {
        $stg = self::STG_VAR_NAME;
        if ($atom instanceof Lang\Variable) {
            $var_name = $atom->name();
            return "{$stg}->global_var(\"$var_name\")"; 
        }
        if ($atom instanceof Lang\Literal) {
            return $literal->value();
        }
        throw new \LogicException("Unknown atom '$atom'.");
    }
} 

function g_class($namespace, $name, $properties, $methods) {
    return new Lechimp\Gen\GClass($namespace, $name, $properties, $methods);
}

function g_public_method($name, $arguments, $statements) {
    return new Lechimp\Gen\GPublicMethod($name, $arguments, $statements);
}

function g_stg_args() {
    return array(new Lechimp\Gen\GArgument("STG", "stg"));
}

function g_stmt($code) {
    return new \Lechimp\Gen\GStatement($code);
}

function g_multiline_dict($ind, array $array) {
    assert(is_int($ind));
    return implode("\n$ind," , array_map(function($v, $k) {
                return "\"$k\" => $v";
           }, $array, array_keys($array)));
}

function g_stg_pop_arg_to($stg_name, $arg_name) {
    return new Lechimp\Gen\GStatement("\$$arg_name = \${$stg_name}->pop_arg()");
}

function g_stg_push_arg($stg_name, $what) {
    return new Lechimp\Gen\GStatement("\$$arg_name = \${$stg_name}->push_arg($what)");
}

function g_stg_ebter($stg_name, $where) {
    return new Lechimp\Gen\GStatement("\${$stg_name}->enter($where)");
}

function g_stg_global_var($stg_name, $var_name) {
    return "\${$stg_name}->global_var(\"$var_name\")";
}

function g_stg_pop_return_to($stg_name, $to) {
    return new Lechimp\Gen\GStatement(\"\${$to} = \${$stg_name}->pop_return()");
}
