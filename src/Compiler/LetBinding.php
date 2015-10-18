<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class LetBinding extends Pattern {
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c) {
        if ($c instanceof Lang\LetBinding) {
            return $c; 
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$let_binding) {
        // Cashes fresh class names in the first iteration as they are needed
        // in the second iteration.
        $class_names = array();

        return $c->results()
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
                ( array_map(function(Lang\Binding $binding) use ($c, $g, &$class_names) {
                    $class_name = array_shift($class_names);
                    return $c->compile_lambda_old($g, $binding->lambda(), $class_name);
                }, $let_binding->bindings())))
            ->add($c->compile_expression($g, $let_binding->expression()));
    } 
}

