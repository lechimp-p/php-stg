<?php

namespace Lechimp\STG\Gen;

class GPrivateMethod extends GMethod {
    public function __construct($name, array $arguments, array $statements) {
        parent::__construct($name, $arguments, $statements);
        $this->qualifier = "private";
    }
}
