<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class PrimOp extends Pattern
{
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c)
    {
        if ($c instanceof Lang\PrimOp) {
            return $c;
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$prim_op) : Results
    {
        $id = $prim_op->id();
        $atoms = $prim_op->atoms();
        assert(count($atoms));
        list($l, $r) = $atoms;
        $left = $g->atom($l);
        $right = $g->atom($r);
        $method_name = "prim_op_$id";
        return $c->results()
            ->add_statements(
                array_flatten(
                    $g->$method_name($left, $right),
                    $g->stg_primitive_value_jump($g)
                )
            );
    }
}
