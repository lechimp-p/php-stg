<?php

namespace Lechimp\STG\Gen;

class GStatement extends Gen {
    /**
     * @var string|Closure
     */
    protected $statement;

    public function __construct($statement) {
        assert(is_string($statement) || $statement instanceof \Closure);
        $this->statement = $statement;
    }

    /**
     * @inheritdoc
     */
    public function render($indentation) {
        assert(is_int($indentation));
    }
}

?>
