<?php

namespace Lechimp\STG;

/**
 * One STG machine.
 */
abstract class STG {
    /**
     * @var \SPLStack
     */
    protected $argument_stack;

    /**
     * @var \SPLStack
     */
    protected $return_stack;

    /**
     * @var \SPLStack
     */
    protected $env_stack;

    /**
     * @var \SPLStack
     */
    protected $update_stack;

    /**
     * @var array
     */
    protected $globals;

    /**
     * @var mixed 
     */
    protected $argument_register;

    public function __construct(array $globals) {
        foreach($globals as $key => $value) {
            assert(is_string($key));
            assert($value instanceof STGClosure);
        }
        if (!array_key_exists("main", $globals)) {
            throw new \LogicException("Missing global 'main'.");
        }
        if (!$globals["main"] instanceof STGClosure) {
            throw new \LogicException("Expected 'main' to be a closure.");
        }
        $this->globals = $globals;
        $this->argument_stack = new \SPLStack();
        $this->return_stack = new \SPLStack();
        $this->env_stack = new \SPLStack();
        $this->update_stack = new \SPLStack();
        $this->argument_register = null;
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
    public function enter(STGClosure $closure) {
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
     * @param   STGClosure|int  $arg
     * @return  none
     */
    public function push_arg($argument) {
        assert(is_int($argument) || $argument instanceof STGClosure);
        $this->argument_stack->push($argument); 
    }

    /**
     * Pop an argument from the stack.
     *
     * @return  STGClosure|int
     */
    public function pop_arg() {
        return $this->argument_stack->pop();
    }

    /**
     * Get the length of the argument stack.
     *
     * @return  int
     */
    public function count_args() {
        return $this->argument_stack->count();
    }

    /**
     * Push a continuation or argumens on the return stack.
     *
     * @param   mixed   $val
     * @return  none
     */
    public function push_return($val) {
        $this->return_stack->push($val);
    }

    /**
     * Pop a continuation from the return stack.
     *
     * @return CodeLabel
     */
    public function pop_return() {
        assert($this->return_stack->count() > 0);
        return $this->return_stack->pop();
    }

    /**
     * Push a environment on the stack.
     *
     * @param   array   $continuations
     * @return  none
     */
    public function push_env(array $continuations) {
        $this->env_stack->push($continuations);
    }

    /**
     * Pop a continuation from the return stack.
     *
     * @return CodeLabel
     */
    public function pop_env() {
        assert($this->env_stack->count() > 0);
        return $this->env_stack->pop();
    }

    /**
     * Store some arguments in the argument register.
     *
     * @param   mixed $args
     * @return  null
     */
    public function push_argument_register($args) {
        assert($args !== null);
        assert($this->argument_register === null);
        $this->argument_register = $args;
    }

    /**
     * Get the arguments stored in the argument register and clean it.
     *
     * @return  array
     */
    public function pop_argument_register() {
        assert($this->argument_register !== null);
        $args = $this->argument_register;
        $this->argument_register = null;
        return $args;
    }

    /**
     * Get the arguments stored in the argument register.
     *
     * @return  array
     */
    public function get_argument_register() {
        assert($this->argument_register !== null);
        return $this->argument_register;
    }
}
