<?php

namespace Lechimp\STG;

/**
 * Representation of a closure in PHP.
 */
abstract class STGClosure {
    /**
     * @var array   $name => $content
     */
    protected $free_variables;

    /**
     * @var CodeLabel
     */
    public $entry_code;

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
    }

    /**
     * The entry code of the closure.
     *
     * @param   STG     $stg
     * @return  CodeLabel
     */
    abstract function entry_code(STG $stg);

    /**
     * Get a list of the free variables of the closure.
     *
     * @return  string[]    
     */
    abstract function free_variables_names();

    /**
     * Get a free variable.
     *
     * @param   string          $name
     * @return  STGClosure|int
     */
    public function free_variable($name) {
        assert(is_string($name));
        if (array_key_exists($name, $this->free_variables)) {
            throw new \LogicException("Unknown free variable '$name' in closure '"
                                     .get_class($this)."'");
        }
        return $this->free_variables[$name];
    }
}
