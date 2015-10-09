<?php

namespace Lechimp\STG;

/**
 * One STG machine.
 */
abstract class STG {
    /**
     * Stack for arguments.
     * 
     * @var \SPLStack
     */
    protected $a_stack;

    /**
     * Stack for return labels, environments and update frames.
     *
     * @var \SPLStack
     */
    protected $b_stack;

    /**
     * @var array
     */
    protected $globals;

    /**
     * @var mixed 
     */
    protected $register;

    public function __construct(array $globals) {
        foreach($globals as $key => $value) {
            assert(is_string($key));
            assert($value instanceof Closures\Standard);
        }
        if (!array_key_exists("main", $globals)) {
            throw new \LogicException("Missing global 'main'.");
        }
        if (!$globals["main"] instanceof Closures\Standard) {
            throw new \LogicException("Expected 'main' to be a closure.");
        }
        $this->globals = $globals;
        $this->a_stack = new \SPLStack();
        $this->b_stack = new \SPLStack();
        $this->register = null;
    }

    /**
     * Run the machine to evaluate main.
     */
    public function run() {
        $label = new CodeLabel($this->globals["main"], "entry_code");
        while($label !== null) {
            $label = $label->jump($this); 
        }
    }

    /**
     * Enter the given closure.
     *
     * @return CodeLabel
     */
    public function enter(Closures\Standard $closure) {
        //echo "enter: ".get_class($closure)."\n";
        // That may be superfluous as we just return the label.
        // See Gen::stg_enter.
        // This offers the flexibility to use another STG (for debugging...)
        // with similar generated code though.
        return $closure->entry_code;
    }

    /**
     * Push an argument on the stack.
     *
     * @param   Closures\Standard|int  $arg
     * @return  none
     */
    public function push_a_stack($argument) {
        assert(is_int($argument) || $argument instanceof Closures\Standard);
        $this->a_stack->push($argument); 
    }

    /**
     * Pop an argument from the stack.
     *
     * @return  Closures\Standard|int
     */
    public function pop_a_stack() {
        return $this->a_stack->pop();
    }

    /**
     * Get the length of the argument stack.
     *
     * @return  int
     */
    public function count_a_stack() {
        return $this->a_stack->count();
    }

    /**
     * Push a continuation, environment or update frame on the b stack.
     *
     * @param   mixed   $val
     * @return  none
     */
    public function push_b_stack($val) {
        $this->b_stack->push($val);
    }

    /**
     * Pop a continuation, environment or update frame from the b stack.
     *
     * @return CodeLabel
     */
    public function pop_b_stack() {
        assert($this->b_stack->count() > 0);
        return $this->b_stack->pop();
    }

    /**
     * Store some arguments in the argument register.
     *
     * @param   mixed $args
     * @return  null
     */
    public function push_register($args) {
        assert($args !== null);
        assert($this->register === null);
        $this->register = $args;
    }

    /**
     * Get the arguments stored in the argument register and clean it.
     *
     * @return  array
     */
    public function pop_register() {
        assert($this->register !== null);
        $args = $this->register;
        $this->register = null;
        return $args;
    }

    /**
     * Get the arguments stored in the argument register.
     *
     * @return  array
     */
    public function get_register() {
        assert($this->register !== null);
        return $this->register;
    }
}
