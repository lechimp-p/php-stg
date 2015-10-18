<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class LetRecBinding extends Pattern {
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c) {
        if ($c instanceof Lang\LetRecBinding) {
            return $c; 
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$letrec_binding) {
        // Cashes fresh class names in the first iteration as they are needed
        // in the third iteration.
        $class_names = array();
    
        return $c->results()
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
                ( array_map(function(Lang\Binding $binding) use ($c, $g, &$class_names) {
                    $class_name = array_shift($class_names);
                    return $c->compile_lambda_old($g, $binding->lambda(), $class_name);
                }, $letrec_binding->bindings()) 
                ))
            ->add($c->compile_syntax($g, $letrec_binding->expression()));
    }
}

