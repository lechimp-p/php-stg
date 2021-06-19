<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class CaseExpr extends Pattern
{
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c)
    {
        if ($c instanceof Lang\CaseExpr) {
            return $c;
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$case_expression)
    {
        $return_vector = array();

        $alternatives_results
            = $this->compile_alternatives($c, $g, $case_expression->alternatives(), $return_vector);
        assert(count($alternatives_results->statements()) == 0);

        $sub_results
            = $c->compile_syntax($g, $case_expression->expression());

        $method_name = $g->method_name("case_return");

        $results = $c->results();
        $results
        ->add_statements(array( $g->stg_push_local_env()
            , $g->stg_push_return($g->code_label($method_name))
            ))
        ->add_method($g->public_method(
            $method_name,
            $g->stg_args(),
            $this->compile_return($c, $g, $return_vector)
        ));
 
        $results->add($alternatives_results);
        $results->add($sub_results);
        return $results;
    }

    protected function compile_return(Compiler $c, Gen $g, array $return_vector)
    {
        $default = null;
        $stmts = array
            // First entry of data vector contains actual value or closure,
            // while the second one is examined to chose a code label to go
            // on with. See compile_constructor and compile_primitive_value_jump.
            ( $g->stg_get_register_to("data_vector")
            , $g->stmt('$value = $data_vector[1]')
            );

        // $value either is a real value or the name of a constructor here.
        foreach ($return_vector as $value => $return_label) {
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
        } else {
            $stmts[] = $g->stmt("throw new \\LogicException(" .
                                "\"No matching alternative for '\$value'\"" .
                                ")");
        }

        return $stmts;
    }

    protected function compile_alternatives(Compiler $c, Gen $g, array $alternatives, array &$return_vector)
    {
        $results = $c->results();
        foreach ($alternatives as $alternative) {
            $results->add($this->compile_alternative($c, $g, $alternative, $return_vector));
        }
        return $results;
    }
    
    protected function compile_alternative(Compiler $c, Gen $g, Lang\Alternative $alternative, array &$return_vector)
    {
        if ($alternative instanceof Lang\DefaultAlternative) {
            return $this->compile_alternative_default($c, $g, $alternative, $return_vector);
        } elseif ($alternative instanceof Lang\PrimitiveAlternative) {
            return $this->compile_alternative_primitive($c, $g, $alternative, $return_vector);
        } elseif ($alternative instanceof Lang\AlgebraicAlternative) {
            return $this->compile_alternative_algebraic($c, $g, $alternative, $return_vector);
        } else {
            throw new \LogicException("Unknown alternative class " . get_class($alternative));
        }
    }

    // Return code that needs to be executed for every alternative. It restores
    // the environment for the alternative.
    protected function compile_alternative_common_return_code(Compiler $c, Gen $g)
    {
        return array( $g->stg_pop_local_env()
            );
    }

    protected function compile_alternative_default(Compiler $c, Gen $g, Lang\DefaultAlternative $alternative, array &$return_vector)
    {
        $results = $c->results();

        $method_name = $g->method_name("alternative_default");
        $return_vector[""] = $g->code_label($method_name);

        if ($alternative->variable() === null) {
            $results->add_statements(array_flatten(
                $this->compile_alternative_common_return_code($c, $g)
                // We won't need the value from the constructor.
                ,
                $g->stg_pop_register()
            ));
        } else {
            $var_name = $alternative->variable()->name();
            $results->add_statements(array_flatten(
                $this->compile_alternative_common_return_code($c, $g)
                // Save value from constructor in local env.
                ,
                $g->stg_pop_register_to("return_vector"),
                $g->to_local_env($var_name, '$return_vector[0]')
            ));
        }

        $results->add($c->compile_syntax($g, $alternative->expression()));
        $results->add_method($g->public_method(
            $method_name,
            $g->stg_args(),
            $results->flush_statements()
        ));
        
        return $results;
    }


    protected function compile_alternative_primitive(Compiler $c, Gen $g, Lang\PrimitiveAlternative $alternative, array &$return_vector)
    {
        $results = $c->results();

        $value = $alternative->literal()->value();
        assert(is_int($value));
        $method_name = $g->method_name("alternative_$value");
        $return_vector[$value] = $g->code_label($method_name);

        return $results
            ->add_statements($this->compile_alternative_common_return_code($c, $g))
            ->add_statement($g->stg_pop_register())
            ->add($c->compile_syntax($g, $alternative->expression()))
            ->add_method($g->public_method(
                $method_name,
                $g->stg_args(),
                $results->flush_statements()
            ))
            ;
    }

    protected function compile_alternative_algebraic(Compiler $c, Gen $g, Lang\AlgebraicAlternative $alternative, array &$return_vector)
    {
        $results = $c->results();

        $id = $alternative->id();
        $method_name = $g->method_name("alternative_$id");
        $return_vector[$id] = $g->code_label($method_name);
        // Pop arguments to constructor and fill them into appropriate variables.
        $results->add_statements(array_flatten(
            $this->compile_alternative_common_return_code($c, $g),
            $g->stg_pop_register_to("data_vector"),
            $g->stmt('array_shift($data_vector)'),
            $g->stmt('array_shift($data_vector)'),
            array_map(function (Lang\Variable $var) use ($g) {
                    $name = $var->name();
                    return $g->to_local_env($name, "array_shift(\$data_vector)");
                }, $alternative->variables())
        ));

        $results->add($c->compile_syntax($g, $alternative->expression()));
        $results->add_method($g->public_method(
            $method_name,
            $g->stg_args(),
            $results->flush_statements()
        ));
        
        return $results;
    }
}
