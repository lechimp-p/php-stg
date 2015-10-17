<?php

namespace Lechimp\STG\Closures;

use Lechimp\STG\GC;
use Lechimp\STG\STG;
use Lechimp\STG\Exceptions\BlackHole;
use Lechimp\STG\CodeLabel;

/**
 * A closure representing a constructor in weak head normal form.
 */
class WHNF extends Closure {
    use GC;

    /**
     * @var array
     */
    protected $data_vector;

    /**
     * Give the data vector as it is used by case expressions.
     *
     * @param   array   $data_vector
     */
    public function __construct(array $data_vector) {
        parent::__construct();

        // Overwrite first entry of data vector with self,
        // as this stands in for the former closure.
        if ($this->data_vector[0] instanceof Closure) {
            $data_vector[0] = $this;
        }
        $this->data_vector = $data_vector;
    }

    /**
     * The entry code of the closure.
     *
     * @param   STG     $stg
     * @return  CodeLabel
     */
    public function entry_code(STG $stg) {
        $return = $stg->pop_b_stack();
        $stg->push_register($this->data_vector);
        return $return;
    }

    /**
     * Get a list of the free variables of the closure.
     *
     * @return  string[]    
     */
    public function free_variables_names() {
        return array();
    }

    /**
     * @inheritdoc
     */
    public function collect_garbage_in_references(array &$survivors) {
        $this->collect_garbage_in_array($this->data_vector, $survivors);
    }
}
