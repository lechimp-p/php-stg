<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class Lambda extends Pattern {
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c) {
        if ($c instanceof Lang\Lambda) {
            return $c; 
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$lambda) {
        $var_names = array_map(function(Lang\Variable $var) {
            return '"'.$var->name().'"';
        }, $lambda->free_variables());

        $sub_results = $c->compile_expression($g, $lambda->expression());

        $results = $c->results();
        $results->add_methods( array_flatten
            ( $g->public_method("entry_code", $g->stg_args()
                 , array_merge
                    ( $this->compile_lambda_entry_code($g, $lambda)
                    , $sub_results->flush_statements() 
                    )
                 )

            // Required method for concrete STGClosures.
            , $g->public_method("free_variables_names", array(), array
                ( $g->stmt(function($ind) use ($g, $var_names) { return
                    "{$ind}return ".$g->multiline_array($ind, $var_names).";";
                })))

            // Put previously compiled methods after entry code for readability
            // of generated code.
            , $sub_results->flush_methods() 
            ));

        return $results
            ->add($sub_results);
    } 

    public function compile_lambda_entry_code(Gen $g, Lang\Lambda $lambda) {
        $num_args = count($lambda->arguments());
        return array_flatten
            ( $this->compile_arguments_check($g, $lambda)

            , $g->init_local_env()

            // Get the free variables into the local env.
            , array_map(function(Lang\Variable $free_var) use ($g) {
                return $g->free_var_to_local_env($free_var->name());
            }, $lambda->free_variables())

            // Get the arguments into the local env.
            , array_map(function(Lang\Variable $argument) use ($g) {
                return $g->stg_pop_arg_to_local_env($argument->name());
            }, $lambda->arguments())

            // Make the entry code of the closure point to the black hole.
            , $lambda->updatable()
                ? array
                    ( $g->stmt("\$this->entry_code = ".$g->code_label("black_hole"))
                    , $g->stg_push_update_frame()
                    )
                : array()
            );
    }

    public function compile_arguments_check(Gen $g, Lang\Lambda $lambda) {
        return $g->if_then_else
            ( $g->stg_args_smaller_than(count($lambda->arguments()))
            , array($g->stg_trigger_update_partial_application())
            , array()
            );
    }


}

