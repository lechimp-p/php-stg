<?php

namespace Lechimp\STG\Gen;

class GProtectedProperty extends GProperty
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->qualifier = "protected";
    }
}
