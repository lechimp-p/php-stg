<?php

namespace Lechimp\STG\Closures;

use Lechimp\STG\STG;
use Lechimp\STG\CodeLabel;

/**
 * Base class for closures.
 */
abstract class Closure {
    /**
     * @var CodeLabel
     */
    public $entry_code;

    public function __construct() {
        $this->entry_code = new CodeLabel($this, "entry_code");
    }

    /**
     * The entry code of the closure.
     *
     * @param   STG     $stg
     * @return  CodeLabel
     */
    abstract public function entry_code(STG $stg);

    /**
     * Get a garbage collected update of this closure.
     *
     * Either returns the updated closure with garbage collected or collects
     * garbage on the closures bound in free variables.
     *
     * The garbage collection inserts the id of the closure in visited to
     * avoid running in to infinite recursion when collecting cyclic references.
     *
     * @return &array   $visited
     * @return Standard
     */
    public function collect_garbage(array &$survivors) {
        $id = spl_object_hash($this);
        if (array_key_exists($id, $survivors)) {
            // Garbage collection on this has already been done.
            return $this;
        }
        $survivors[$id] = get_class($this);

        $this->collect_garbage_in_references($survivors);
        
        return $this;
    }

    /**
     * Collect garbage on all referenced closures.
     *
     * @return &array   $visited
     * @return null
     */
    abstract public function collect_garbage_in_references(array &$survivors);
}
