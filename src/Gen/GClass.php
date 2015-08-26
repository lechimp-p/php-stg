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

    /**
     * @var string|null
     */
    protected $extends;

    public function __construct($namespace, $name, array $properties, array $methods, $extends = null) {
        assert(is_string($namespace));
        assert(is_string($name));
        assert(is_string($extends) || $extends === null);
        $this->properties = array_map(function(GProperty $p) {
            return $p;
        }, $properties);
        $this->methods = array_map(function(GMethod $m) {
            return $m;
        }, $methods);
        $this->namespace = $namespace;
        $this->name = $name;
        $this->extends = $extends;
    }

    /**
     * @inheritdoc
     */
    public function render($indentation) {
        assert(is_int($indentation));
        if ($this->namespace) {
            $qualified_name = $this->namespace."\\".$this->name;
        }
        else {
            $qualified_name = $this->name;
        }
        if ($this->extends) {
            $extends = "extends ".$this->extends." ";
        }
        else {
            $extends = "";
        }
        return $this->cat_and_indent($indentation, array
            ( "class $qualified_name $extends{"
            , "}"
            ));
    }
}
