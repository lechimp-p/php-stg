<?php

namespace Lechimp\STG;

/**
 * One STG machine.
 *
 * The stacks from the machine are implemented with SPLFixedArrays, as the i
 * implementation using SPLStack was messy.
 */
abstract class STG {
    use GC;

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
     * @var array
     */
    protected $globals;

    /**
     * @var mixed 
     */
    protected $register;

    /**
     * @var STGClosure
     */
    protected $node;

    /**
     * Counts created closures for gc.
     *
     * @var int
     */
    public $amount_closures;

    /**
     * Counts updated closures for gc.
     *
     * @var int
     */
    public $updated_closures;

    /**
     * Amount of cycles of main loop after which the check is called whether
     * garbage collection should be done.
     *
     * @var int
     */
    public $check_garbage_collection_cycles = 1;

    public function __construct() {
        $this->init_globals();
        foreach($this->globals as $key => $value) {
            assert(is_string($key));
            assert($value instanceof Closures\Standard);
        }
        if (!array_key_exists("main", $this->globals)) {
            throw new \LogicException("Missing global 'main'.");
        }

        if ($this->a_stack_size === null) {
            $this->a_stack_size = self::A_STACK_SIZE;
        }

        if ($this->b_stack_size === null) {
            $this->b_stack_size = self::B_STACK_SIZE;
        }

        assert(is_int($this->a_stack_size) && $this->a_stack_size > 0);
        assert(is_int($this->b_stack_size) && $this->b_stack_size > 0);

        $this->label_update = new CodeLabel($this, "update");

        $this->init(); 
    }

    /**
     * Initialize the globals.
     */
    abstract protected function init_globals();

    /**
     * Initialize the stacks and stuff for a run.
     */
    public function init() {
        $this->a_stack = new \SPLFixedArray($this->a_stack_size);
        $this->a_top = 0;
        $this->a_bottom = 0;
        $this->b_stack = new \SPLFixedArray($this->b_stack_size);
        $this->b_top = 0;
        $this->b_bottom = 0;
        $this->register = null;
        $this->updated_closures = 0;
        $this->amount_closures = 0;
        $this->init_globals();
    }

    /**
     * Run the machine to evaluate main.
     */
    public function run() {
        $label = new CodeLabel($this->globals["main"], "entry_code");
        $this->node = $this->globals["main"];
        $count = 0;
        while($label !== null) {
            $label = $label->jump($this); 
            if ($count == $this->check_garbage_collection_cycles) {
                $count = 0;
                $this->collect_garbage(); 
            }
            $count++;
        }
    }

    /**
     * Create a new closure based on a class name and
     * an array of free variables.
     *
     * @param   string  $class_name
     * @param   array   $free_vars
     * @return  Closures/Standard
     */
    public function new_closure($class_name, array &$free_vars) {
        $this->amount_closures++;
        return new $class_name($free_vars);
    }

    /**
     * Enter the given closure.
     *
     * @param   Closures\Closure    $closure
     * @return  CodeLabel
     */
    public function enter(Closures\Closure $closure) {
        // That may be superfluous as we just return the label.
        // See Gen::stg_enter.
        // This offers the flexibility to use another STG (for debugging...)
        // with similar generated code though.
        $this->node = $closure;
        return $closure->entry_code;
    }

    /**
     * Push an argument on the a stack.
     *
     * @param   mixed $arg
     * @return  null 
     */
    public function push_a_stack($argument) {
        assert(is_int($argument) || $argument instanceof Closures\Closure);
        $this->a_stack[$this->a_top++] = $argument;
    }

    /**
     * Add the arguments found in the array to the a stack.
     *
     * @param   \SPLFixedArray $args
     * @return  null
     */
    public function push_array_a_stack(\SPLFixedArray $args) {
        foreach($args as $a) {
            $this->push_a_stack($a);
        }
    }

    /**
     * Pop an argument from the a stack.
     *
     * @return  mixed
     */
    public function pop_a_stack() {
        assert($this->a_stack->count() > 0);
        return $this->a_stack[--$this->a_top];
    }

    /**
     * Get the length of the a stack.
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
     * Add the values found in the array to the b stack.
     *
     * @param   \SPLFixedArray $args
     * @return  null
     */
    public function push_array_b_stack(\SPLFixedArray $bs) {
        foreach($bs as $b) {
            $this->push_b_stack($b);
        }
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
        assert($this->register === null);
        $frame = array
            ( $this->node
            , $this->a_bottom
            , $this->b_bottom
            );
        $this->a_bottom = $this->a_top;
        $this->b_bottom = $this->b_top;
	    $this->push_b_stack($frame);
        $this->push_b_stack($this->label_update);
    }

    /**
     * Perform an update for a partial application.
     *
     * @return CodeLabel
     */
    public function update_partial_application() {
        // ToDo: This is an artificial return we pushed
        // for the constructors. We could remove this by
        // somehow merging the update frame information
        // and the code label.
        $this->pop_b_stack();
        list($node, $a_bottom, $b_bottom)
            = $this->pop_b_stack();
    
        $a_copy = new \SPLFixedArray($this->a_top-$this->a_bottom);
        $b_copy = new \SPLFixedArray($this->b_top-$this->b_bottom);

        STG::copy_array($this->a_stack, $this->a_bottom, $this->a_top, $a_copy);
        STG::copy_array($this->b_stack, $this->b_bottom, $this->b_top, $b_copy);

        $node->update(new Closures\PartialApplication
                            ( $this->node
                            , $a_copy
                            , $b_copy
                            ));
        $this->a_bottom = $a_bottom;
        $this->b_bottom = $b_bottom;

        $this->amount_closures++;
        $this->updated_closures++;

        return $this->enter($this->node);
    }

    /**
     * Perform an update for a WHNF.
     *
     * @return CodeLabel
     */
    public function update($_) {
        list($node, $a_bottom, $b_bottom)
            = $this->pop_b_stack();
        $this->a_bottom = $a_bottom;
        $this->b_bottom = $b_bottom;
        $node->update(new Closures\WHNF($this->get_register()));

        $this->amount_closures++;
        $this->updated_closures++;

        return $this->pop_b_stack();
    }

    /**
     * Trigger garbage collection
     */
    public function collect_garbage() {
        // Collects the closure that persist after garbage collection. Will
        // be used to abort gc on closures that were already visited.
        $survivors = array();
        $this->collect_garbage_in_array($this->globals, $survivors);
        $this->collect_garbage_in_stack($this->a_stack, $survivors);
        $this->collect_garbage_in_stack($this->b_stack, $survivors);

        $this->amount_closures = count($survivors);
        $this->updated_closures = 0;
    }

    

    // HELPERS

    static private function copy_array(\SPLFixedArray $src, $src_start, $src_end, \SPLFixedArray $tgt, $tgt_start = 0) {
        for ($i = $src_start; $i < $src_end; $i++) {
            $tgt[$tgt_start++] = $src[$i];
        }
    }
}
