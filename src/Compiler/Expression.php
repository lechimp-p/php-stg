<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen\Gen;

class Expression extends Pattern {
    /**
     * @inheritdoc
     */
    public function matches(Lang\Syntax $c) {
        if ($c instanceof Lang\Expression) {
            return true;
        } 
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $c, Gen $g, &$_) {
        throw new \LogicException("Can't use pattern AllExpressions to compile something.");
    } 
}

