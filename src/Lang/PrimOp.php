<?php

namespace Lechimp\STG\Lang;

/**
 * A builtin primitive operation.
 */
class PrimOp extends Expression {
    /**
     * @var string
     */
    private $id;

    /**
     * @var Atom[]
     */
    private $atoms;

    static private $op_ids = array
        ( "+"
        , "-"
        , "*"
        , "/"
        ); 

    public function __construct($id, array $atoms) {
        assert(in_array($id, static::$op_ids));
        $this->atoms = array_map(function (Atom $atom) {
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
