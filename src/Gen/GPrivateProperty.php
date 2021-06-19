<?php

namespace Lechimp\STG\Gen;

class GPrivateProperty extends GProperty
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->qualifier = "private";
    }
}
