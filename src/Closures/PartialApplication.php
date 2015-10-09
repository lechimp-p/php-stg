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
     * @var \SPLStack|null
     */
    protected $a_stack;

    /**
     * @var \SPLStack|null
     */
    protected $b_stack;

    /**
     * @var \SPLStack|null
     */
    protected $env_stack;

    /**
     * ATTENTION: The dictionary of free variables is passed by reference.
     *            to make recursive definitions possible.
     */
    public function __construct( Standard  $function_closure
                               , \SPLStack $a_stack
                               , \SPLStack $b_stack
                               ) {
        $a = array();
        parent::__construct($a);

        // For partial update.
        $this->function_closure = $function_closure;
        $this->a_stack = $a_stack;
        $this->b_stack = $b_stack;
    }

    public function entry_code(STG $stg) {
        $stg->push_args($this->a_stack);
        $stg->push_returns($this->b_stack);

        return $stg->enter($this->function_closure);
    }

    public function free_variables_names() {
        return array();
    }
}


