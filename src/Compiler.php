<?php

namespace Lechimp\STG;

/**
 * Compiles expressions from the stg language to php-based STG
 * code.
 */
class Compiler {
    const STG_VAR_NAME = '$stg';

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

        $stg = self::STG_VAR_NAME; 
        $globals = array();
        $code = "use \\Lechimp\\STG\\STG;\n".
                "use \\Lechimp\\STG\\CodeLabel;\n".
                "use \\Lechimp\\STG\\STGClosure;\n".
                "\n\n";
        
        foreach($program->bindings() as $binding) {
            $var_name = $binding->variable()->name();
            $class_name = ucfirst($var_name)."Closure";
            $code .= $this->compile_lambda($binding->lambda(), $class_name)."\n\n";
            $globals[$var_name] = "new $class_name()";
        }
        
        $globals_code = implode( "\n            , "
                               , array_map(function($v, $k) {
                                    return "\"$k\" => $v";
                                 }, $globals, array_keys($globals))
                               );

        $code .= "\n";
        $code .= <<<PHP
class TheMachine extends STG {
    public function __construct() {
        parent::__construct(array
            ( $globals_code
            ));
    }
}
PHP;
        return array("main.php" => $code);
    }

    protected function compile_lambda(Lang\Lambda $lambda, $class_name) {
        assert(is_string($class_name));

        $stg = self::STG_VAR_NAME; 

        $num_args = count($lambda->arguments());
        $argument_stack_check = "assert({$stg}->count_args() >= $num_args);";

        $get_free_variables = "//TODO: get_free_variables NYI!";

        $arguments = array();
        foreach ($lambda->arguments() as $argument) {
            $argname = '$'.$argument->name();
            $arguments[] = "$argname = {$stg}->pop_arg();";
        }
        $get_arguments = implode("\n        ", $arguments);

        list($compiled_expression, $additional_methods)
             = $this->compile_expression($lambda->expression());

        return <<<PHP
class $class_name extends STGClosure {
    public function entry_code(STG $stg) {
        $argument_stack_check
        $get_free_variables
        $get_arguments
        $compiled_expression 
    }

    $additional_methods
}
PHP;
    }

    protected function compile_expression(Lang\Expression $expression) {
        if ($expression instanceof Lang\Application) {
            return $this->compile_application($expression);
        }
        if ($expression instanceof Lang\Constructor) {
            return $this->compile_constructor($expression);
        }
        if ($expression instanceof Lang\CaseExpr) {
            return $this->compile_case_expression($expression);
        }
        throw new \LogicException("Unknown expression '$expression'.");
    }

    protected function compile_application(Lang\Application $application) {
        $stg = self::STG_VAR_NAME;
        $code = array();
        foreach ($application->atoms() as $atom) {
            $atom = $this->compile_atom($atom);
            $code[] =  "{$stg}->push_arg($atom);";
        }
        $var_name = $application->variable()->name();
        $code[] = "return {$stg}->enter({$stg}->global_var(\"$var_name\"));";
        $code = implode("\n        ", $code);
        return array($code, "");
    }

    protected function compile_constructor(Lang\Constructor $constructor) {
        $stg = self::STG_VAR_NAME;
        $rvn = '$return_vector';

        $id = $constructor->id();

        $code = "$rvn = {$stg}->pop_return();\n"
              . "        if (array_key_exists(\"$id\", $rvn)) {\n"
              . "            return {$rvn}[\"$id\"];\n"
              . "        }\n"
              . "        else if (array_key_exists(null, $rvn)) {\n"
              . "            return {$rvn}[null];\n"
              . "        }\n"
              . "        else {\n"
              . "            throw new \\LogicException(\n"
              . "                \"No matching alternative for constructor '$id'.\"\n"
              . "            );\n"
              . "        }\n"
              ;
        return array($code, "");
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
