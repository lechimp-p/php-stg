<?php

namespace Lechimp\STG\Gen;

/**
 * Base class for PHP generation classes.
 */
abstract class Gen {
    const INDENTATION_ATOM = "    ";

    /**
     * Render some PHP.
     *
     * @param   int     $indentation
     * @return  string
     */
    abstract public function render($indentation);

    /**
     * Indent a string.
     *
     * @param   int         $indentation
     * @param   string      $string
     * @return  string
     */
    public function indent($indentation, $string) {
        assert(is_int($indentation));
        assert(is_string($string));
        return str_repeat(self::INDENTATION_ATOM, $indentation).$string;
    }
     

    /**
     * Take multiple lines in an array and concatenate them
     * together with some indentation.
     *
     * @param   int         $indentation
     * @param   string[]    $string
     * @return  string
    */
    protected function cat_and_indent($indentation, array $strings) {
        $ind = $this->indent($indentation, "");
        return implode("\n", array_map(function($s) use ($ind) {
            return $ind.$s;
        }, $strings));
    }
}
