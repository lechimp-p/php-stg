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
    protected $argument_stack;

    /**
     * @var \SPLStack|null
     */
    protected $return_stack;

    /**
     * @var \SPLStack|null
     */
    protected $env_stack;

    /**
     * ATTENTION: The dictionary of free variables is passed by reference.
     *            to make recursive definitions possible.
     */
    public function __construct( Standard  $function_closure
                               , \SPLStack $argument_stack
                               , \SPLStack $return_stack
                               , \SPLStack $env_stack
                               ) {
        parent::__construct(array());

        // For partial update.
        $this->function_closure = $function_closure;
        $this->argument_stack = $argument_stack;
        $this->return_stack = $return_stack;
        $this->env_stack = $env_stack;
    }

    public function entry_code(STG $stg) {
        assert($this->argument_stack instanceof SPLStack);
        $this->stg->push_args($this->argument_stack);

        assert($this->return_stack instanceof SPLStack);
        $this->stg->push_returns($this->return_stack);

        assert($this->env_stack instanceof SPLStack);
        $this->stg->push_envs($this->env_stack);

        return $this->function_closure->entry_code;
    }
}


