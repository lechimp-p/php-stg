<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class Constructor extends Pattern
{
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c)
    {
        if ($c instanceof Lang\Constructor) {
            return $c;
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$constructor) : Results
    {
        $id = $constructor->id();

        $args_vector = array_map(
            fn (Lang\Atom $atom) => $g->atom($atom),
            $constructor->atoms()
        );
        
        // We return a standardized data vector for the 'value' of this constructor.
        // See compile_case_return and compile_primitive_value_jump.
        $standard_vector = ['$this', "\"$id\""];
        $data_vector = array_merge($standard_vector, $args_vector);

        $results = $c->results();
        $results->add_statements([
            $g->stg_pop_return_to("return"),
            $g->stmt(
                fn ($ind) =>
                    "{$ind}\$data_vector = " . $g->multiline_array($ind, $data_vector) . ";"
            ),
            $g->stg_push_register('$data_vector'),
            $g->stmt("return \$return")
        ]);
        return $results;
    }
}
