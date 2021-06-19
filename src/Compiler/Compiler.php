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
class Compiler
{
    const STG_VAR_NAME = 'stg';

    /**
     * Create a code generator.
     */
    public function code_generator(
        string $namespace,
        string $stg_name,
        string $standard_name
    ) : Gen\Gen {
        return new Gen\Gen($namespace, $stg_name, $standard_name);
    }

    /**
     * Create a compilation results object
     */
    public function results() : Results
    {
        return new Results();
    }

    /**
     * The patterns that are applied to the syntax.
     *
     * @var Pattern[]
     */
    public $patterns = [];

    public function __construct()
    {
        $this->pattern =
            new AllSyntax([
                new Lambda(),
                new Expression([
                    new Application(),
                    new Constructor(),
                    new Literal(),
                    new LetBinding(),
                    new LetRecBinding(),
                    new CaseExpr(),
                    new PrimOp()
                ]),
                new Program()
            ]);
    }

    /**
     * Compile a program to a bunch of PHP files using the STG to execute the
     * defined program.
     *
     * @param   Lang\Program    $program        to be compiled
     * @param   string          $stg_class      to be used for STG implementation
     * @param   string          $namespace      to put the classes we create.
     * @param   strubg          $standard_class for standard closure.
     * @return  array<string,string> $filename => $content
     */
    public function compile(
        Lang\Program $program,
        string $stg_class_name,
        string $namespace = "",
        string $standard_name = "\\Lechimp\\STG\\Closures\\Standard"
    ) : array {
        $g = $this->code_generator($namespace, self::STG_VAR_NAME, $standard_name);
        // TODO: This should be going to the generator like namespace.
        $this->stg_class_name = $stg_class_name;

        $results = $this->compile_syntax($g, $program);

        assert(count($results->methods()) == 0);
        assert(count($results->statements()) == 0);

        // Render all classes to a single file.
        return [
            "main.php" => implode(
                "\n\n",
                array_map(
                    fn (Gen\GClass $cl) => $cl->render(0),
                    $results->classes()
                )
            )
        ];
    }

    public function compile_syntax(Gen\Gen $g, Lang\Syntax $s)
    {
        list($compiler, $res) = $this->pattern->search_compiler($s);
        return $compiler->compile($this, $g, $res);
    }
}


function array_flatten()
{
    $args = func_get_args();
    if (count($args) == 0) {
        return [];
    }
    if (count($args) == 1) {
        if (is_array($args[0])) {
            $returns = [];
            foreach ($args[0] as $val) {
                if (is_array($val)) {
                    $returns = array_merge($returns, array_flatten($val));
                } else {
                    $returns[] = $val;
                }
            }
            return $returns;
        } else {
            return $args[0];
        }
    }
    return array_flatten($args);
}
