<?php

namespace Lechimp\STG\Lang;

/**
 * A literal.
 *
 * Only ints atm.
 */
class Literal extends Expression implements Atom
{
    /**
     * @var int
     */
    private $value;

    public function __construct($value)
    {
        assert(is_int($value));
        $this->value = $value;
    }

    public function value()
    {
        return $this->value;
    }
}
