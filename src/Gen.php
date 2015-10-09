<?php

namespace Lechimp\STG;

/**
 * Code generator class for the compiler.
 *
 * The code generator deals with the low level generation of code, that is 
 * turn statements of the compiler like 'load variable to local environment'
 * to concrete PHP code.
 */
class Gen {
    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $stg_name;

    /**
     * Counter to create unique class and method names.
     *
     * @var int
     */
    protected $counter;

    public function __construct($namespace, $stg_name) {
        assert(is_string($namespace));
        assert(is_string($stg_name));
        $this->namespace = $namespace;
        $this->stg_name = $stg_name;
        $this->counter = 0;
    }

    public function _class($name, $properties, $methods, $extends = null) {
        return new Gen\GClass($this->namespace, $name, $properties, $methods, $extends);
    }

    public function public_method($name, $arguments, $statements) {
        return new Gen\GPublicMethod($name, $arguments, $statements);
    }

    public function stg_args() {
        return array(new Gen\GArgument("\\Lechimp\\STG\\STG", Compiler::STG_VAR_NAME));
    }

    public function stmt($code) {
        return new Gen\GStatement($code);
    }

    public function if_then_else($if, $then, $else) {
        return new Gen\GIfThenElse($if, $then, $else);
    }

    public function multiline_dict($ind, array $array) {
        return 
            "array\n$ind    ( ".
            implode("\n$ind    , " , array_map(function($v, $k = null) {
                if (is_null($k) || $k == "") {
                    return "\"\" => $v";
                }
                if (is_string($k)) {
                    return "\"$k\" => $v";
                }
                if (is_int($k)) {
                    return "$k => $v";
                }
                throw new \LogicException("Can't render multiline dict with key"
                                         ." of type '".gettype($k));
            }, $array, array_keys($array))).
            "\n$ind    )";
            
    }

    public function multiline_array($ind, array $array) {
        return 
            "array\n$ind    ( ".
            implode("\n$ind    , " , array_map(function($v) {
                return "$v";
            }, $array)).
            "\n$ind    )";
    }

    public function _var($varname) {
        return "\$$varname";
    }

    public function init_local_env() {
        return $this->stmt("\$local_env = array()");
    }

    public function local_env($var_name) {
        return "\$local_env[\"$var_name\"]"; 
    }

    public function to_local_env($var_name, $expr) {
        return $this->stmt("\$local_env[\"$var_name\"] = $expr");
    }

    public function stg_pop_local_env() {
        return $this->stg_pop_env_to("local_env");
    }

    public function stg_push_local_env() {
        return $this->stg_push_env('$local_env');
    }

    public function free_var_to_local_env($var_name) {
        return $this->to_local_env($var_name, "\$this->free_variables[\"$var_name\"]");
    }

    public function stg_pop_arg_to($arg_name) {
        return new Gen\GStatement("\$$arg_name = \${$this->stg_name}->pop_a_stack()");
    }

    public function stg_pop_arg_to_local_env($arg_name) {
        return $this->stg_pop_arg_to("local_env[\"$arg_name\"]");
    }

    public function stg_push_arg($what) {
        return new Gen\GStatement("\${$this->stg_name}->push_a_stack($what)");
    }

    public function stg_enter($where) {
        return new Gen\GStatement("return \${$this->stg_name}->enter($where)");
    }

    public function stg_enter_local_env($var_name) {
        return $this->stg_enter("\$local_env[\"$var_name\"]");
    }


    public function stg_global_var($var_name) {
        return "\${$this->stg_name}->global_var(\"$var_name\")";
    }

    public function stg_pop_return() {
        return new Gen\GStatement("\${$this->stg_name}->pop_return()");
    }

    public function stg_pop_return_to($to) {
        return new Gen\GStatement("\${$to} = \${$this->stg_name}->pop_b_stack()");
    }

    public function stg_pop_return_to_local_env($var_name) {
        return $this->stg_pop_return_to("local_env[\"$var_name\"]");
    }

    public function stg_push_return($what) {
        return new Gen\GStatement("\${$this->stg_name}->push_b_stack($what)");
    }

    public function stg_pop_env_to($to) {
        return new Gen\GStatement("\${$to} = \${$this->stg_name}->pop_b_stack()");
    }

    public function stg_push_env($what) {
        return new Gen\GStatement("\${$this->stg_name}->push_b_stack($what)");
    }

    public function stg_push_argument_register($what) {
        return new Gen\GStatement("\${$this->stg_name}->push_argument_register($what)");
    }

    public function stg_pop_argument_register() {
        return new Gen\GStatement("\${$this->stg_name}->pop_argument_register()");
    }

    public function stg_pop_argument_register_to($to) {
        return new Gen\GStatement("\${$to} = \${$this->stg_name}->pop_argument_register()");
    }

    public function stg_pop_argument_register_to_local_env($name) {
        return new Gen\GStatement($this->local_env($name)." = \${$this->stg_name}->pop_argument_register()");
    }

    public function stg_get_argument_register_to($to) {
        return new Gen\GStatement("\${$to}= \${$this->stg_name}->get_argument_register()");
    }

    public function code_label($method_name) {
        return "new \\Lechimp\\STG\\CodeLabel(\$this, \"$method_name\")";
    }

    //---------------------
    // HELPERS
    //---------------------

    public function class_name($name) {
        assert(is_string($name));
        $i = $this->counter;
        $this->counter++;
        return ucfirst($name)."_{$i}_Closure";
    }

    public function method_name($name) {
        assert(is_string($name));
        $i = $this->counter;
        $this->counter++;
        return $name."_$i";
    }
}
