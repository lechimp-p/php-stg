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
            $c->compile_machine($g, $c->stg_class_name, $bindings, $globals)
        );
    } 

    public function compile_globals(Compiler $c, Gen $g, array $bindings) {
        $results = $c->results();
        $globals = array();

        foreach ($bindings as $binding) {
            assert($binding instanceof Lang\Binding);

            $var_name = $binding->variable()->name();
            $class_name = $g->class_name($var_name);

            $sub_result = $c->compile_lambda($g, $binding->lambda(), $class_name);
            assert(count($sub_result->methods()) == 0);
            assert(count($sub_result->statements()) == 0);

            $results->add($sub_result);
            // This line (the $var_name) depends on code generated in machine_construct.
            $globals[$var_name] = $g->stg_new_closure($class_name, $var_name);
        }

        return array($globals, $results);
    }

}

