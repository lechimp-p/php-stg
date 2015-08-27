<?php

namespace Lechimp\STG\Gen;

abstract class GMethod extends Gen {
    use GAccessQualifierTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var GArgument[]
     */
    protected $arguments;

    /**
     * @var GStatement[]
     */
    protected $statements;

    public function __construct($name, array $arguments, array $statements) {
        assert(is_string($name));
        $this->arguments = array_map(function(GArgument $arg) {
            return $arg;
        }, $arguments);
        $this->statements = array_map(function(GStatement $st) {
            return $st;
        }, $statements);
        $this->name = $name;
    } 

    public function render($indentation) {
        assert(is_int($indentation));
        $qualifier = $this->render_qualifier();
        $name = $this->name;
        $arguments = implode(", ", array_map(function(GArgument $arg) {
            return $arg->render(0);
        }, $this->arguments));
        return $this->cat_and_indent($indentation, array_merge
            ( array ( "$qualifier function $name($arguments) {" )
            , array_map(function(GStatement $stmt) {
                return $stmt->render(1);
            }, $this->statements)
            , array ( "}" )
            ));
    } 
}
