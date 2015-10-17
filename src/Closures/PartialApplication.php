<?php

namespace Lechimp\STG\Closures;

use Lechimp\STG\STG;
use Lechimp\STG\Exceptions\BlackHole;
use Lechimp\STG\CodeLabel;

class PartialApplication extends Standard {
    /**
     * @var STGClosure|null
     */
    protected $function_closure;

    /**
     * @var \SPLFixedArray
     */
    protected $a_stack;

    /**
     * @var \SPLFixedArray
     */
    protected $b_stack;

    /**
     * ATTENTION: The dictionary of free variables is passed by reference.
     *            to make recursive definitions possible.
     */
    public function __construct( Standard  $function_closure
                               , \SPLFixedArray $a_stack
                               , \SPLFixedArray $b_stack
                               ) {
        $a = array();
        parent::__construct($a);

        // For partial update.
        $this->function_closure = $function_closure;
        $this->a_stack = $a_stack;
        $this->b_stack = $b_stack;
    }

    public function entry_code(STG $stg) {
        $stg->push_array_a_stack($this->a_stack);
        $stg->push_array_b_stack($this->b_stack);
 
        return $stg->enter($this->function_closure);
    }

    public function free_variables_names() {
        return array();
    }

    /**
     * Get a garbage collected update of this closure.
     *
     * Collects garbage in function closure and on the stacks.
     *
     * @return &array   $visited
     * @return Standard
     */
    public function collect_garbage(array &$visited) {
        $id = spl_object_hash($this);

        if (array_key_exists($id, $visited)) {
            // Garbage collection on function closure and stacks has already been done.
            return $this;
        }
        $visited[$id] = true;

        $this->function_closure = $this->function_closure->collect_garbage($visited);
    } 
}


