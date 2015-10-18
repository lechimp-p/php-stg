<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Gen;

/**
 * Stores results during compilation process.
 */
class Results {
    /**
     * @var Gen\GClass[]
     */
    protected $classes;

    /**
     * @var Gen\GMethod[]
     */
    protected $methods;

    /**
     * @var Gen\GStatement[]
     */
    protected $statements;

    public function __construct() {
        $this->classes = array();
        $this->methods = array();
        $this->statements = array();
    } 

    public function classes() {
        return $this->classes;
    }

    public function add_class(Gen\GClass $cl) {
        $this->classes[] = $cl;
        return $this;
    }

    public function add_classes(array $cls) {
        $cls= array_map(function(Gen\GClass $cl) {
            return $cl;
        }, $cls);
        $this->classes = array_merge($this->classes, $cls);
        return $this;
    }


    public function flush_classes() {
        $cls = $this->classes;
        $this->classes = array();
        return $cls;
    }

    public function methods() {
        return $this->methods;
    }

    public function add_method(Gen\GMethod $method) {
        $this->methods[] = $method;
        return $this;
    }

    public function add_methods(array $methods) {
        $methods = array_map(function(Gen\GMethod $method) {
            return $method;
        }, $methods);
        $this->methods = array_merge($this->methods, $methods);
        return $this;
    }

    public function flush_methods() {
        $methods = $this->methods;
        $this->methods = array();
        return $methods;
    }

    public function statements() {
        return $this->statements;
    }

    public function add_statement(Gen\GStatement $stmt) {
        $this->statements[] = $stmt;        
        return $this;
    }

    public function add_statements(array $stmts) {
        $stmts = array_map(function(Gen\GStatement $stmt) {
            return $stmt;
        }, $stmts);
        $this->statements = array_merge($this->statements, $stmts);
        return $this;
    }

    public function flush_statements() {
        $stmts = $this->statements;
        $this->statements = array();
        return $stmts;
    }

    public function add(Results $res) {
        foreach($res->classes() as $cls) {
            $this->add_class($cls);
        }
        foreach($res->methods() as $method) {
            $this->add_method($method);
        }
        foreach($res->statements() as $stmt) {
            $this->add_statement($stmt);
        }
        return $this;
    }

    public function adds(array $results) {
        foreach($results as $res) {
            $this->add($res);
        }
        return $this;
    }

    public function combine(Results $res) {
        $results = new Results();
        foreach($this->classes() as $cls) {
            $results->add_class($cls);
        }
        foreach($res->classes() as $cls) {
            $results->add_class($cls);
        }
        foreach($this->methods() as $method) {
            $results->add_method($method);
        }
        foreach($res->methods() as $method) {
            $results->add_method($method);
        }
        foreach($this->statements() as $stmt) {
            $results->add_statement($stmt);
        }
        foreach($res->statements() as $stmt) {
            $results->add_statement($stmt);
        }
        return $results;
    }
}
