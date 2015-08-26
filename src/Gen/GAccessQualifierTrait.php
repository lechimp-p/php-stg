<?php

namespace Lechimp\STG\Gen;

trait GAccessQualifierTrait {
    /**
     * @var string
     */
    protected $qualifier;

    protected function render_qualifier() {
        return $this->qualifier;
    }
}
