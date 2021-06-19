<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class Literal extends Pattern
{
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c)
    {
        if ($c instanceof Lang\Literal) {
            return $c->value();
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$value)
    {
        $results = $c->results();
        $results->add_statements(array_flatten(
                $g->stmt("\$primitive_value = $value"),
                $g->stg_primitive_value_jump()
            ));
        return $results;
    }
}
