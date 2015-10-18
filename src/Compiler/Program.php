<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class Program extends Pattern {
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c) {
        if ($c instanceof Lang\Program) {
            return $c->bindings(); 
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$bindings) {
        list($globals, $result) = $this->compile_globals($c, $g, $bindings);

        return $result->combine(
            $this->compile_machine($c, $g, $bindings, $globals)
        );
    } 

    public function compile_globals(Compiler $c, Gen $g, array $bindings) {
        $results = $c->results();
        $globals = array();

        foreach ($bindings as $binding) {
            assert($binding instanceof Lang\Binding);

            $var_name = $binding->variable()->name();
            $class_name = $g->class_name($var_name);

            $sub_result = $c->compile_syntax($g, $binding->lambda());
            $sub_result->add_class
                ( $g->closure_class($class_name, $sub_result->flush_methods()));
            assert(count($sub_result->methods()) == 0);
            assert(count($sub_result->statements()) == 0);

            $results->add($sub_result);
            // This line (the $var_name) depends on code generated in compile_init_globals.
            $globals[$var_name] = $g->stg_new_closure($class_name, $var_name);
        }

        return array($globals, $results);
    }

    protected function compile_machine(Compiler $c, Gen $g, array $bindings, array $globals) {
        $results = $c->results();
        $results->add_class($g->_class
            ( $c->stg_class_name
            , array() // no props
            , array
                ( $g->protected_method( "init_globals", array()
                    , $this->compile_init_globals($c, $g, $bindings, $globals)
                    )
                )
            , "\\Lechimp\\STG\\STG"
            ));
        return $results;
    }

    protected function compile_init_globals(Compiler $c, Gen $g, array $bindings, array $globals) {
        return array_flatten
            ( $g->stmt('$stg = $this')

            // Create arrays for the free variables of the global closures.
            , array_map(function(Lang\Binding $binding) use ($g) {
                $closure_name = $binding->variable()->name();
                return array
                    ( array($g->stmt("\$free_vars_$closure_name = array()"))
                    , array_map(function(Lang\Variable $free_var) use ($g, $closure_name) {
                        $var_name = $free_var->name();
                        return $g->stmt("\$free_vars_{$closure_name}[\"$var_name\"] = null");
                    }, $binding->lambda()->free_variables()));
            }, $bindings)

            // Create the array containing the globals.
            , $g->stmt(function($ind) use ($g, $globals) { return
                "{$ind}\$this->globals = ".$g->multiline_dict($ind, $globals).";";})

            // Fill the previously generated arrays with contents from globals.
            , array_map(function(Lang\Binding $binding) use ($g) {
                $closure_name = $binding->variable()->name();
                return array_map(function(Lang\Variable $free_var) use ($g, $closure_name) {
                    $var_name = $free_var->name();
                    return $g->stmt("\$free_vars_{$closure_name}[\"$var_name\"] = \$this->globals[\"$var_name\"]");
                }, $binding->lambda()->free_variables());
            }, $bindings)
            );
    }
}

