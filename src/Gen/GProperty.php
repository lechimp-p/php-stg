<?php

namespace Lechimp\STG\Gen;

abstract class GProperty extends Gen {
    use GAccessQualifierTrait;

    /**
     * @var string
     */
    protected $name;

    public function __construct($name) {
        assert(is_string($name));
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function render($indentation) {
        return $this->indent($indentation, $this->render_qualifier().' $'.$this->name.';');
    }
}
