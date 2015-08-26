<?php

namespace Lechimp\STG\Gen;

class GClass extends Gen {
    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Properties[]
     */
    protected $properties;

    /**
     * @var Method[]
     */
    protected $methods;

    public function __construct($namespace, $name, array $properties, array $methods) {
        assert(is_string($namespace));
        assert(is_string($name));
        $this->properties = array_map(function(GProperty $p) {
            return $p;
        }, $properties);
        $this->methods = array_map(function(GMethod $m) {
            return $m;
        }, $methods);
    }

    /**
     * @inheritdoc
     */
    public function render($indentation) {
        assert(is_int($indentation));
    }
}
