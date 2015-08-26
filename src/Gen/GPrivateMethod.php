<?php

namespace Lechimp\STG\Gen;

class GPrivateMethod extends GMethod {
    public function __construct($name) {
        parent::__construct($name);
        $this->qualifier = "private";
    }
}
