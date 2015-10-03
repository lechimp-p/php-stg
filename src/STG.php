<?php

namespace Lechimp\STG;

/**
 * One STG machine.
 */
abstract class STG {
    /**
     * Stack for arguments.
     * 
     * @var \SPLFixedArray
     */
    protected $a_stack;

    /**
     * @var integer
     */
    protected $a_top;
    
    /**
     * @var integer
     */
    protected $a_bottom;

    /**
     * Stack for return labels, environments and update frames.
     *
     * @var \SPLFixedArray
     */
    protected $b_stack;

    /**
     * @var integer
     */
    protected $b_top;
    
    /**
     * @var integer
     */
    protected $b_bottom;

    /**
     * @var array
     */
    protected $globals;

    /**
     * @var mixed 
     */
    protected $register;

    const A_STACK_SIZE = 100;
    const B_STACK_SIZE = 100;

    /**
     * @var int
     */
    protected $a_stack_size = null;

    /**
     * @var int
     */
    protected $b_stack_size = null;

    /**
     * @var STGClosure
     */
    protected $node;

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

        if ($this->a_stack_size === null) {
            $this->a_stack_size = self::A_STACK_SIZE;
        }

        if ($this->b_stack_size === null) {
            $this->b_stack_size = self::B_STACK_SIZE;
        }

        assert(is_int($this->a_stack_size) && $this->a_stack_size > 0);
        assert(is_int($this->b_stack_size) && $this->b_stack_size > 0);

        $this->globals = $globals;
        $this->a_stack = new \SPLFixedArray($this->a_stack_size);
        $this->a_top = 0;
        $this->a_bottom = 0;
        $this->b_stack = new \SPLFixedArray($this->b_stack_size);
        $this->b_top = 0;
        $this->b_bottom = 0;
        $this->register = null;
        $this->label_update = new CodeLabel($this, "update");
    }

    /**
     * Run the machine to evaluate main.
     */
    public function run() {
        $label = new CodeLabel($this->globals["main"], "entry_code");
        $this->node = $this->globals["main"];
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
        $this->node = $closure;
        return $closure->entry_code;
    }

    /**
     * Push an argument on the stack.
     *
     * @param   Closures\Standard|int  $arg
     * @return  null 
     */
    public function push_a_stack($argument) {
        assert(is_int($argument) || $argument instanceof Closures\Standard);
        $this->a_stack[$this->a_top++] = $argument;
    }

    /**
     * Add the values found in the argument to the argument stack.
     *
     * @param   \SPLStack $args
     * @return  null
     */
    public function push_args(\SPLStack $args) {
        while($args->count() > 0) {
            $stg->push_arg($args->pop());
        }
    }

    /**
     * Pop an argument from the stack.
     *
     * @return  Closures\Standard|int
     */
    public function pop_a_stack() {
        assert($this->a_stack->count() > 0);
        return $this->a_stack[--$this->a_top];
    }

    /**
     * Get the length of the argument stack.
     *
     * @return  int
     */
    public function count_a_stack() {
        return $this->a_top - $this->a_bottom;
    }

    /**
     * Push a continuation, environment or update frame on the b stack.
     *
     * @param   mixed   $val
     * @return  none
     */
    public function push_b_stack($val) {
        $this->b_stack[$this->b_top++] = $val;
    }

    /**
     * Pop a continuation, environment or update frame from the b stack.
     *
     * @return mixed 
     */
    public function pop_b_stack() {
        assert($this->b_stack->count() > 0);
        return $this->b_stack[--$this->b_top];
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

    /*
     * Push an update frame.
     *
     * @return null
     */
    public function push_update_frame() {
        assert($this->argument_register === null);
        $this->update_stack->push(array
            ( $this->node
            , $this->argument_stack
            , $this->return_stack
            , $this->env_stack
            ));
        $this->argument_stack = new \SPLStack();
        $this->return_stack = new \SPLStack();
        $this->env_stack = new \SPLStack();
        $this->push_return($this->label_update);
    }

    /**
     * Pop an update frame.
     */
    protected function pop_update_frame() {
        assert($this->update_stack->count() !== 0);
        return $this->update_stack->pop();
    }

    /**
     * Perform an update for a partial application.
     *
     * @return CodeLabel
     */
    public function update_partial_application() {
        list($node, $argument_stack, $return_stack, $env_stack)
            = $this->update_stack->pop();
        $node->in_place_update($this->node, $argument_stack
                              , $return_stack, $env_stack);
    }

    /**
     * Perform an update.
     *
     * @return CodeLabel
     */
    public function update($_) {
        // Just restore the update frame, we will create a real implementation
        // later on.
        throw new \Exception("UPDATE"); 
    }
}
