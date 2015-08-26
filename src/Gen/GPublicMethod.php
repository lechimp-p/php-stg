<?php

namespace Lechimp\STG\Gen;

class GPublicMethod extends GMethod {
    public function __construct($name) {
        parent::__construct($name);
        $this->qualifier = "public";
    }
}
