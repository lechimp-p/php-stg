<?php

namespace Lechimp\STG\Gen;

abstract class GMethod extends Gen {
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Argument[]
     */
    protected $arguments;

    /**
     * @var Statement[]
     */
    protected $statements;

    public function __construct($name, array $arguments, array $statements) {
        assert(is_string($name));
        $this->arguments = array_map(function(Argument $arg) {
            return $arg;
        }, $arguments);
        $this->statements = array_map(function(Statement $st) {
            return $st;
        }, $statements);
    } 

    public function render($indentation) {
        assert(is_int($indentation));
    } 
}
