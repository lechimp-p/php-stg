<?php

namespace Lechimp\STG\Gen;

class GPublicProperty extends GProperty
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->qualifier = "public";
    }
}
