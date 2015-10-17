<?php

namespace Lechimp\STG\Lang;

/**
 * A saturated constructor.
 */
class Constructor extends Expression implements Syntax {
    /**
     * @var string
     */
    private $id;

    /**
     * @var Atom[]
     */
    private $atoms;

    public function __construct($id, array $atoms) {
        assert(is_string($id));
        $this->id = $id;
        $this->atoms = array_map(function(Atom $atom) {
            return $atom;
        }, $atoms);
    }

    public function id() {
        return $this->id;
    }

    public function atoms() {
        return $this->atoms;
    }
}
