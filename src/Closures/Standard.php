<?php

namespace Lechimp\STG\Closures;

use Lechimp\STG\STG;
use Lechimp\STG\Exceptions\BlackHole;
use Lechimp\STG\CodeLabel;

/**
 * Representation of a closure in PHP.
 */
abstract class Standard {
    /**
     * @var array|null $name => $content
     */
    protected $free_variables;

    /**
     * @var CodeLabel
     */
    public $entry_code;

    // For updating and garbage collection.
    /**
     * @var Standard|null
     */
    public $updated;

    /**
     * ATTENTION: The dictionary of free variables is passed by reference.
     *            to make recursive definitions possible.
     */
    public function __construct(array &$free_variables) {
        $free_variables_names = $this->free_variables_names();
        assert(count($free_variables_names) == count($free_variables));
        assert(sort($free_variables_names) == sort(array_keys($free_variables)));
        $this->free_variables = &$free_variables;
        $this->entry_code = new CodeLabel($this, "entry_code");
        $this->updated = null;

        // For partial update.
        $this->function_closure = null;
        $this->argument_stack = null;
        $this->return_stack = null;
        $this->env_stack = null;
    }

    /**
     * The entry code of the closure.
     *
     * @param   STG     $stg
     * @return  CodeLabel
     */
    abstract public function entry_code(STG $stg);

    /**
     * Get a list of the free variables of the closure.
     *
     * @return  string[]    
     */
    abstract public function free_variables_names();

    /**
     * Black hole entry code.
     *
     * This is used entry code when this closure is just evaluated.
     *
     * @param   STG $stg
     */
    public function black_hole(STG $stg) {
        throw new BlackHole();
    }

    /**
     * Get a free variable.
     *
     * @param   string          $name
     * @return  Closures\Standard|int
     */
    public function free_variable($name) {
        assert(is_string($name));
        assert($this->updated === null);
        if (array_key_exists($name, $this->free_variables)) {
            throw new \LogicException("Unknown free variable '$name' in closure '"
                                     .get_class($this)."'");
        }
        return $this->free_variables[$name];
    }

    /**
     * Update this closure with another closure.
     *
     * Cleans free variables, sets pointer to updated version of this closure
     * and overwrites entry code of this closure to the updated version.
     *
     * @param   Standard    $updated
     * @return  null
     */
    public function update(Standard $updated) {
        assert($this->updated === null);
        $this->updated = $updated;
        $this->free_variables = null;
    } 

    // In place update with partial application.
    //
    // The paper assumes, that we manage the pointers to closures ourselves, thus
    // we could update a closure in place. This is not the case in this PHP scenario,
    // as we want to take advantage of the garbage collection of PHP. Therefore we
    // perform an in place update with this closure by effectively having a two in 
    // one object. We could also use an indirection in entry_code, but we use this
    // mechanism as it closer resembles the paper. First make it run, care about
    // style and performance later.

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

    public function partial_application_entry_code(STG $stg) {
        assert($this->argument_stack instanceof SPLStack);
        $this->stg->push_args($this->argument_stack);

        assert($this->return_stack instanceof SPLStack);
        $this->stg->push_returns($this->return_stack);

        assert($this->env_stack instanceof SPLStack);
        $this->stg->push_envs($this->env_stack);
    }

    public function in_place_update( STGClosure $function_closure
                                   , \SPLStack $argument_stack
                                   , \SPLStack $return_stack
                                   , \SPLStack $env_stack
                                   ) {
        assert($this->function_closure === null);
        assert($this->argument_stack === null);
        assert($this->return_stack === null);
        assert($this->env_stack === null);
        $this->argument_stack = $argument_stack;
        $this->return_stack = $return_stack;
        $this->env_stack = $env_stack;
        $this->entry_code = new CodeLabel($this, "partial_application_entry_code");
    }
}
