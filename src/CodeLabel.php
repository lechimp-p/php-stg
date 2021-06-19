<?php

namespace Lechimp\STG;

/**
 * The paper uses the term *code label* with the following characteristics:
 *
 * * names arbitrary blocks of code
 * * can be pushed onto a stack, stored or be put in a table
 * * can be used as a destination of a jump
 *
 * In C this is implemented via a function pointer. The code labels are used
 * to perform jumps. This is necessary as we would need an infinite stack in
 * PHP to model recursion and PHP does not perform tail call optimisation.
 * We therefore return code labels to jump somewhere and call them in a main
 * loop.
 */
class CodeLabel
{
    /**
     * @var mixed
     */
    private $object;

    /**
     * @var string
     */
    private $method;

    public function __construct($object, $method)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException("Expected an object as first parameter.");
        }
        if (!method_exists($object, $method)) {
            throw new \InvalidArgumentException("Expected a name of a method of the" .
                                         " object as second parameter.");
        }
        $this->object = $object;
        $this->method = $method;
    }

    public function jump($stg)
    {
        return $this->object->{$this->method}($stg);
    }
}
