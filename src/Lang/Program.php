<?php

namespace Lechimp\STG\Lang;

/**
 * A program from the STG language.
 */
class Program implements Syntax {
    /**
     * @var Binding[]
     */
    private $bindings;

    public function __construct(array $bindings) {
        $this->bindings = array_map(function(Binding $binding) {
            return $binding;
        }, $bindings); 
    }

    public function bindings() {
        return $this->bindings;
    }
}
