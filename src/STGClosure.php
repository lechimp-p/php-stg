<?php

namespace Lechimp\STG;

/**
 * Representation of a closure in PHP.
 */
abstract class STGClosure {
    /**
     * The entry code of the closure.
     *
     * @param   STG     $stg
     * @return  CodeLabel
     */
    abstract function entry_code(STG $stg);
}
