<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class AllSyntax extends Pattern {
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c) {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$_) {
        throw new \LogicException("Can't use pattern AllSyntax to compile something.");
    } 
}

