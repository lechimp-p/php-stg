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
     * Take multiple lines in an array and concatenate them
     * together with some indentation.
     *
     * @param   int         $indentation
     * @param   string[]    $string
     * @return  string
    */
    protected function cat_and_indent($indentation, array $strings) {
        $ind = str_repeat(self::INDENTATION_ATOM, $indentation);
        return implode("\n", array_map(function($s) use ($ind) {
            return $ind.$s;
        }, $strings));
    }
}
