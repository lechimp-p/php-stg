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
        $rg = $c->compile_globals($g, $bindings);
        $rm = $c->compile_machine($g, $c->stg_class_name, $bindings, $rg->globals());
        return $rg->combine($rm);
    } 
}

