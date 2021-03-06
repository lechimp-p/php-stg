<?php

namespace Lechimp\STG\Lang;

/**
 * A builtin primitive operation.
 */
class PrimOp extends Expression implements Syntax
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Atom[]
     */
    private $atoms;

    private static $op_ids = array( "IntAddOp"
        , "IntSubOp"
        , "IntMulOp"
//        , "IntMulMayOfloOp"
//        , "IntQuotOp"
//        , "IntRemOp"
//        , "IntNegOp"
//        , "IntAddCOp"
//        , "IntSubCOp"
//        , "IntGtOp"
//        , "IntGeOp"
//        , "IntEqOp"
//        , "IntNeOp"
//        , "IntLtOp"
//        , "IntLeOp"
        );

    public function __construct($id, array $atoms)
    {
        assert(in_array($id, static::$op_ids));
        $this->atoms = array_map(function (Atom $atom) {
            return $atom;
        }, $atoms);
        $this->id = $id;
    }

    public function id()
    {
        return $this->id;
    }

    public function atoms()
    {
        return $this->atoms;
    }
}
