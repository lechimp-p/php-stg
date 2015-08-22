<?php

namespace Lechimp\STG\Lang;

/**
 * A variable is just a name.
 */
class Variable implements Atom {
    /**
     * @var string
     */
    private $name;

    public function __construct($name) {
        assert(is_string($name));
        $this->name = $name;
    }

    public function name() {
        return $this->name;
    }
}
