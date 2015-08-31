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
     * @var GProperty[]
     */
    protected $properties;

    /**
     * @var GMethod[]
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
            $namespace_start = array("namespace {$this->namespace} {", "");
            $namespace_end = array("", "} // namespace {$this->namespace}");
        }
        else {
            $namespace_start = array();
            $namespace_end = array();
        }
        if ($this->extends) {
            $extends = "extends ".$this->extends." ";
        }
        else {
            $extends = "";
        }
        return implode("\n", array_merge
            ( $namespace_start
            , array( $this->indent($indentation, "class {$this->name} $extends{" ) )

            // Properties
            , array_map(function(GProperty $prop) use ($indentation) {
                return $prop->render($indentation + 1);
            }, $this->properties)

            // Methods
            , array_map(function(GMethod $method) use ($indentation) {
                return $method->render($indentation + 1);
            }, $this->methods)
            
            , array( $this->indent($indentation, "}") )
            , $namespace_end
            ));
    }
}
