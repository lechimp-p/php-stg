<?php

namespace Lechimp\STG;

/**
 * Stores results during compilation process.
 */
class CompilationResults {
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

    /**
     * @var array   $name => $initializer
     */
    protected $globals;

    public function __construct() {
        $this->classes = array();
        $this->methods = array();
        $this->statements = array();
        $this->globals = array();
    } 

    public function classes() {
        return $this->classes;
    }

    public function addClass(Gen\GClass $cl) {
        $this->classes[] = $cl;
        return $this;
    }

    public function flushClasses() {
        $cls = $this->classes;
        $this->classes = array();
        return $cls;
    }

    public function methods() {
        return $this->methods;
    }

    public function addMethod(Gen\GMethod $method) {
        $this->methods[] = $method;
        return $this;
    }

    public function flushMethods() {
        $methods = $this->methods;
        $this->methods = array();
        return $methods;
    }

    public function globals() {
        return $this->globals;
    }

    public function addGlobal($name, $initializer) {
        assert(is_string($name));
        assert(is_string($initializer));
        assert(!array_key_exists($name, $this->globals));
        $this->globals[$name] = $initializer;
        return $this;
    }

    public function flushGlobals() {
        $globals = $this->globals;
        $this->globals = array();
        return $globals;
    }

    public function statements() {
        return $this->statements;
    }

    public function addStatement(Gen\GStatement $stmt) {
        $this->statements[] = $stmt;        
        return $this;
    }

    public function addStatements(array $stmts) {
        $stmts = array_map(function(Gen\GStatement $stmt) {
            return $stmt;
        }, $stmts);
        $this->statements = array_merge($this->statements, $stmts);
    }

    public function flushStatements() {
        $stmts = $this->statements;
        $this->statements = array();
        return $stmts;
    }

    public function add(CompilationResults $res) {
        foreach($res->classes() as $cls) {
            $this->addClass($cls);
        }
        foreach($res->methods() as $method) {
            $this->addMethod($method);
        }
        foreach($res->globals() as $name => $cls) {
            $this->addGlobal($name, $stmt);
        }
        foreach($res->statements() as $stmt) {
            $this->addStatement($stmt);
        }
        return $this;
    }

    public function combine(CompilationResults $res) {
        $results = new CompilationResults();
        foreach($this->classes() as $cls) {
            $results->addClass($cls);
        }
        foreach($res->classes() as $cls) {
            $results->addClass($cls);
        }
        foreach($this->methods() as $method) {
            $results->addMethod($method);
        }
        foreach($res->methods() as $method) {
            $results->addMethod($method);
        }
        foreach($this->globals() as $name => $stmt) {
            $results->addGlobal($name, $stmt);
        }
        foreach($res->globals() as $name => $cls) {
            $results->addGlobal($name, $stmt);
        }
        foreach($this->statements() as $stmt) {
            $results->addStatement($stmt);
        }
        foreach($res->statements() as $stmt) {
            $results->addStatement($stmt);
        }
        return $results;
    }
}
