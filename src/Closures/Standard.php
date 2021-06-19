<?php

namespace Lechimp\STG\Closures;

use Lechimp\STG\STG;
use Lechimp\STG\GC;
use Lechimp\STG\Exceptions\BlackHole;
use Lechimp\STG\CodeLabel;

/**
 * Representation of a closure in PHP.
 */
abstract class Standard extends Closure
{
    use GC;

    /**
     * @var array|null $name => $content
     */
    protected $free_variables;

    // For updating and garbage collection.
    /**
     * @var Closure|null
     */
    public $updated;

    /**
     * ATTENTION: The dictionary of free variables is passed by reference.
     *            to make recursive definitions possible.
     */
    public function __construct(array &$free_variables)
    {
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
    public function black_hole(STG $stg)
    {
        throw new BlackHole();
    }

    /**
     * Get a free variable.
     *
     * @param   string          $name
     * @return  Closures\Closure|int
     */
    public function free_variable($name)
    {
        assert(is_string($name));
        assert($this->updated === null);
        if (array_key_exists($name, $this->free_variables)) {
            throw new \LogicException("Unknown free variable '$name' in closure '"
                                     . get_class($this) . "'");
        }
        return $this->free_variables[$name];
    }

    /**
     * Update this closure with another closure.
     *
     * Cleans free variables, sets pointer to updated version of this closure
     * and overwrites entry code of this closure to the updated version.
     *
     * @param   Closure $updated
     * @return  null
     */
    public function update(Closure $updated)
    {
        assert($this->updated === null);
        $this->updated = $updated;
        $this->free_variables = null;
        $this->entry_code = $updated->entry_code;
    }

    /**
     * @inheritdoc
     */
    public function collect_garbage(array &$survivors)
    {
        if ($this->updated !== null) {
            // The closure was updated. Drop it.
            return $this->updated->collect_garbage($survivors);
        }

        return parent::collect_garbage($survivors);
    }

    /**
     * @inheritdoc
     */
    public function collect_garbage_in_references(array &$survivors)
    {
        assert($this->free_variables !== null);
        $this->collect_garbage_in_array($this->free_variables, $survivors);
    }
}
