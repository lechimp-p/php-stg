<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang;
use Lechimp\STG\Gen;

/**
 * Compiles expressions from the stg language to php-based STG code.
 *
 * Only deals with high level question, as 'put var in local environment'
 * while Gen-class deals with the creation of actual php code.
 */
class Compiler {
    const STG_VAR_NAME = 'stg';

    /**
     * Create a code generator.
     *
     * @return Gen
     */
    public function code_generator($namespace, $stg_name, $standard_name) {
        return new Gen\Gen($namespace, $stg_name, $standard_name);
    }

    /**
     * Create a compilation results object 
     *
     * @return Results 
     */
    public function results() {
        return new Results();
    }

    /**
     * The patterns that are applied to the syntax.
     *
     * @var Pattern[]
     */
    public $patterns;

    /**
     * @var int
     */
    public $amount_of_patterns;

    public function __construct() {
        $this->pattern = 
            new AllSyntax(array
                ( new Lambda()
                , new Expression(array
                    ( new Application()
                    , new Constructor()
                    , new Literal()
                    , new LetBinding()
                    , new LetRecBinding()
                    , new CaseExpr()
                    , new PrimOp()
                    ))
                , new Program()
                ));
        $this->amount_of_patterns = count($this->patterns);
    }

    /**
     * Compile a program to a bunch of PHP files using the STG to execute the
     * defined program.
     *
     * @param   Lang\Program    $program
     * @param   string          $stg_class_name
     * @param   string          $namespace      Where to put the classes we create.
     * @param   strubg          $standard_name  Class name for standard closure.
     * @return  array           $filename => $content
     */
    public function compile( Lang\Program $program, $stg_class_name
                           , $namespace = ""
                           , $standard_name = "\\Lechimp\\STG\\Closures\\Standard") {
        assert(is_string($stg_class_name));

        $g = $this->code_generator($namespace, self::STG_VAR_NAME, $standard_name); 
        // TODO: This should be going to the generator like namespace.
        $this->stg_class_name = $stg_class_name;

        $results = $this->compile_syntax($g, $program);

        assert(count($results->methods()) == 0);
        assert(count($results->statements()) == 0);

        // Render all classes to a single file.
        return array("main.php" => implode("\n\n", array_map(function(Gen\GClass $cl) {
            return $cl->render(0);
        }, $results->classes())));
    }

    public function compile_syntax(Gen\Gen $g, Lang\Syntax $s) {
        list($compiler, $res) = $this->pattern->search_compiler($s);
        return $compiler->compile($this, $g, $res);
    }

    //---------------------
    // LAMBDAS
    //---------------------

    // TODO: remove this temporary method. It is just needed to ease
    // refactoring.
    public function compile_lambda_old(Gen\Gen $g, Lang\Lambda $lambda, $class_name) {
        $results = $this->compile_syntax($g, $lambda);
        return $results->add_class
            ($g->closure_class($class_name, $results->flush_methods()));
    }

    //---------------------
    // ATOMS
    //---------------------

    public function compile_atom(Gen\Gen $g, Lang\Atom $atom) {
        if ($atom instanceof Lang\Variable) {
            $var_name = $atom->name();
            return $g->local_env($var_name); 
        }
        if ($atom instanceof Lang\Literal) {
            return $atom->value();
        }
        throw new \LogicException("Unknown atom '$atom'.");
    }
} 


function array_flatten() {
    $args = func_get_args();
    if (count($args) == 0) {
        return array();
    }
    if (count($args) == 1) {
        if (is_array($args[0])) {
            $returns = array();
            foreach($args[0] as $val) {
                if (is_array($val)) {
                    $returns = array_merge($returns, array_flatten($val));
                }
                else {
                    $returns[] = $val;
                }
            }
            return $returns;
        }
        else {
            return $args[0];
        }
    }
    return array_flatten($args);
}
