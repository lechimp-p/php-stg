<?php

namespace Lechimp\STG\Compiler;

use Lechimp\STG\Lang\Syntax;
use Lechimp\STG\Gen\Gen;

/**
 * A pattern on the STG-syntax tree and how to compile it.
 */
abstract class Pattern
{
    /**
     * Patterns that are more specific than this one.
     * @var Pattern[]
     */
    protected $sub_patterns;

    public function __construct(array $sub_patterns = null)
    {
        if ($sub_patterns === null) {
            $sub_patterns = array();
        }

        $this->sub_patterns = array_map(function (Pattern $pattern) {
            return $pattern;
        }, $sub_patterns);
    }

    public function sub_patterns()
    {
        return $this->sub_patterns;
    }

    /**
     * Get a compiler for the given syntax construct.
     *
     * Returns the most specific pattern object it can find alongside
     * with results from matching.
     *
     * Throws an exception if no pattern matches.
     *
     * @param   Syntax
     * @throws  \LogicException
     * @return  [Pattern, mixed]
     */
    final public function search_compiler(Syntax $c)
    {
        $res = $this->matches($c);
        if ($res === null) {
            throw new \LogicException("Don't know how to compile " . get_class($s));
        }
        
        $most_specific = $this;

        $continue = true;
        while ($continue) {
            $continue = false;
            foreach ($most_specific->sub_patterns() as $pattern) {
                $tmp = $pattern->matches($c);
                if ($tmp !== null) {
                    $res = $tmp;
                    $most_specific = $pattern;
                    $continue = true;
                    break;
                }
            }
        }
        return array($most_specific, $res);
    }

    /**
     * Does this pattern match the language construct?
     *
     * Return null if pattern does not match. Returns something that will be
     * passed to compile if pattern matches.
     *
     * @param   Syntax     $c
     * @return  mixed|null
     */
    abstract public function matches(Syntax $c);

    /**
     * Compile the pattern based on the stuff returned from matches.
     *
     * @param   Compile $c
     * @param   Gen     $g
     * @param   mixed   &$p
     * @return  Results
     */
    abstract public function compile(Compiler $c, Gen $g, &$p);
}
