<?php

namespace Lechimp\STG\Gen;

use Lechimp\STG\Lang;
use Lechimp\STG\Compiler\Compiler;

/**
 * Code generator class for the compiler.
 *
 * The code generator deals with the low level generation of code, that is
 * turn statements of the compiler like 'load variable to local environment'
 * to concrete PHP code.
 */
class Gen
{
    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $stg_name;

    /**
     * @var string
     */
    protected $standard_name;

    /**
     * Counter to create unique class and method names.
     *
     * @var int
     */
    protected $counter;

    public function __construct($namespace, $stg_name, $standard_name)
    {
        assert(is_string($namespace));
        assert(is_string($stg_name));
        assert(is_string($standard_name));
        $this->namespace = $namespace;
        $this->stg_name = $stg_name;
        $this->standard_name = $standard_name;
        $this->counter = 0;
    }

    public function closure_class($class_name, $methods)
    {
        return $this->_class($class_name, array(), $methods, $this->standard_name);
    }

    public function _class($name, $properties, $methods, $extends = null)
    {
        return new GClass($this->namespace, $name, $properties, $methods, $extends);
    }

    public function public_method($name, $arguments, $statements)
    {
        return new GPublicMethod($name, $arguments, $statements);
    }

    public function protected_method($name, $arguments, $statements)
    {
        return new GProtectedMethod($name, $arguments, $statements);
    }

    public function stg_args()
    {
        return array(new GArgument("\\Lechimp\\STG\\STG", Compiler::STG_VAR_NAME));
    }

    public function stmt($code)
    {
        return new GStatement($code);
    }

    public function if_then_else($if, $then, $else)
    {
        return new GIfThenElse($if, $then, $else);
    }

    public function multiline_dict($ind, array $array)
    {
        return
            "array\n$ind    ( " .
            implode("\n$ind    , ", array_map(function ($v, $k = null) {
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
                                         . " of type '" . gettype($k));
            }, $array, array_keys($array))) .
            "\n$ind    )";
    }

    public function multiline_array($ind, array $array)
    {
        return
            "array\n$ind    ( " .
            implode("\n$ind    , ", array_map(function ($v) {
                return "$v";
            }, $array)) .
            "\n$ind    )";
    }

    public function _var($varname)
    {
        return "\$$varname";
    }

    public function init_local_env()
    {
        return $this->stmt("\$local_env = array()");
    }

    public function local_env($var_name)
    {
        return "\$local_env[\"$var_name\"]";
    }

    public function to_local_env($var_name, $expr)
    {
        return $this->stmt("\$local_env[\"$var_name\"] = $expr");
    }

    public function stg_pop_local_env()
    {
        return $this->stg_pop_env_to("local_env");
    }

    public function stg_push_local_env()
    {
        return $this->stg_push_env('$local_env');
    }

    public function free_var_to_local_env($var_name)
    {
        return $this->to_local_env($var_name, "\$this->free_variables[\"$var_name\"]");
    }

    public function stg_pop_arg_to($arg_name)
    {
        return new GStatement("\$$arg_name = \${$this->stg_name}->pop_a_stack()");
    }

    public function stg_pop_arg_to_local_env($arg_name)
    {
        return $this->stg_pop_arg_to("local_env[\"$arg_name\"]");
    }

    public function stg_push_arg($what)
    {
        return new GStatement("\${$this->stg_name}->push_a_stack($what)");
    }

    public function stg_args_smaller_than($amount)
    {
        return "\${$this->stg_name}->count_a_stack() < $amount";
    }

    public function stg_enter($where)
    {
        return new GStatement("return \${$this->stg_name}->enter($where)");
    }

    public function stg_enter_local_env($var_name)
    {
        return $this->stg_enter("\$local_env[\"$var_name\"]");
    }

    public function stg_global_var($var_name)
    {
        return "\${$this->stg_name}->global_var(\"$var_name\")";
    }

    public function stg_pop_return()
    {
        return new GStatement("\${$this->stg_name}->pop_return()");
    }

    public function stg_pop_return_to($to)
    {
        return new GStatement("\${$to} = \${$this->stg_name}->pop_b_stack()");
    }

    public function stg_pop_return_to_local_env($var_name)
    {
        return $this->stg_pop_return_to("local_env[\"$var_name\"]");
    }

    public function stg_push_return($what)
    {
        return new GStatement("\${$this->stg_name}->push_b_stack($what)");
    }

    public function stg_return_stack_empty()
    {
        return "\${$this->stg_name}->count_return() == 0";
    }

    public function stg_pop_env_to($to)
    {
        return new GStatement("\${$to} = \${$this->stg_name}->pop_b_stack()");
    }

    public function stg_push_env($what)
    {
        return new GStatement("\${$this->stg_name}->push_b_stack($what)");
    }

    public function stg_push_register($what)
    {
        return new GStatement("\${$this->stg_name}->push_register($what)");
    }

    public function stg_pop_register()
    {
        return new GStatement("\${$this->stg_name}->pop_register()");
    }

    public function stg_pop_register_to($to)
    {
        return new GStatement("\${$to} = \${$this->stg_name}->pop_register()");
    }

    public function stg_pop_register_to_local_env($name)
    {
        return new GStatement($this->local_env($name) . " = \${$this->stg_name}->pop_register()");
    }

    public function stg_get_register_to($to)
    {
        return new GStatement("\${$to} = \${$this->stg_name}->get_register()");
    }

    public function stg_push_update_frame()
    {
        return new GStatement("\${$this->stg_name}->push_update_frame()");
    }

    public function stg_trigger_update()
    {
        return new GStatement("return " . $this->stg_code_label("update"));
    }

    public function stg_trigger_update_partial_application()
    {
        return new GStatement("return " . $this->stg_code_label("update_partial_application"));
    }

    public function stg_primitive_value_jump()
    {
        return array( $this->stg_pop_return_to("return")
            // We return an tuple as argument, so we could use the first entry similar
            // to $this in compile_constructor and the second one for comparison with
            // available return vectors. See compile_case_return also.
            , $this->stg_push_register("array(\$primitive_value, \$primitive_value)")
            , $this->stmt("return \$return")
            );
    }

    public function code_label($method_name)
    {
        return "new \\Lechimp\\STG\\CodeLabel(\$this, \"$method_name\")";
    }

    public function stg_code_label($method_name)
    {
        return "new \\Lechimp\\STG\\CodeLabel(\${$this->stg_name}, \"$method_name\")";
    }

    public function stg_new_closure($class_name, $free_vars_name)
    {
        return "\${$this->stg_name}->new_closure(\"\\{$this->namespace}\\$class_name\", \$free_vars_$free_vars_name)";
    }

    public function atom(Lang\Atom $atom)
    {
        if ($atom instanceof Lang\Variable) {
            $var_name = $atom->name();
            return $this->local_env($var_name);
        }
        if ($atom instanceof Lang\Literal) {
            return $atom->value();
        }
        throw new \LogicException("Unknown atom '$atom'.");
    }

    public function prim_op_IntAddOp($left, $right)
    {
        return $this->stmt("\$primitive_value = $left + $right");
    }

    public function prim_op_IntSubOp($left, $right)
    {
        return $this->stmt("\$primitive_value = $left - $right");
    }

    public function prim_op_IntMulOp($left, $right)
    {
        return $this->stmt("\$primitive_value = $left * $right");
    }

    //---------------------
    // HELPERS
    //---------------------

    public function class_name($name)
    {
        assert(is_string($name));
        $i = $this->counter;
        $this->counter++;
        return ucfirst($name) . "_{$i}_Closure";
    }

    public function method_name($name)
    {
        assert(is_string($name));
        $i = $this->counter;
        $this->counter++;
        return $name . "_$i";
    }
}
