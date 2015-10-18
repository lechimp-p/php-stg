<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class Application extends Pattern {
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c) {
        if ($c instanceof Lang\Application) {
            return $c; 
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$application) {
        $var_name = $application->variable()->name();

        $results = $c->results();
        $results->add_statements( array_flatten
            ( array_map(function($atom) use ($c, $g) {
                return $g->stg_push_arg($g->atom($atom));
            }, array_reverse($application->atoms()))
            , $g->stg_enter_local_env($var_name)
            ));
        return $results;
    } 
}

