<?php

namespace Lechimp\STG;

/*
= The STG for PHP =

This implements the Spineless Tagless G-Machine by Simon Peyton Jones in PHP.

*/

class STG {
    /*
    == Construct from the Paper ==

    === Jumps ===

    The paper uses the term *code label* with the following characteristics:

    * names arbitrary blocks of code
    * can be pushed onto a stack, stored or be put in a table
    * can be used as a destination of a jump

    In C this is implemented via a function pointer. In PHP we could use a construct
    like `$foo()` to call a function foo. We would implement the code blocks as ordinary
    functions. To use the autoloading feature of PHP instead we'll use the construction
    `$foo::$bar()` instead and therefore use a 2-tuple of strings as a *code label*.
    */

    public static function code_label($class, $method) {
        $label = new \SplFixedArray(2);
        $label[0] = $class;
        $label[1] = $method;
        return $label;
    }

    public static function jump($state, \SplFixedArray $label) {
        return $label[0]::$label[1]($state);
    }

    public static function call($state, \SplFixedArray $label) {
        return $label[0]::$label[1]($state);
    }

    /*

    === Interpreter ===

    We use an interpreter very similar to the one from the paper:

    */
    public static function interpret($state) {
        while ($label !== null) {
            $label = static::jump($state, $label);
        } 
    }

    /*

    We could use another interpreter for debugging or other purposes.

    === Closures ===

    We implement closure kind of similar, but as we use PHP we are not determined
    to allocate space by ourselves and access it via pointers. We therefore use
    an array to represent the info table of the closure and the closure itself.

    */

    const STANDARD_ENTRY_CODE = 0;
    const EVACUATION_CODE = 1;
    const SCAVENGE_CODE = 2;
    const INFO_TABLE_LENGTH = 3;

    public static function info_table($standard_entry_code, $evacuation_code, $scavenge_code) {
        $info_table = new \SplFixedArray(STG::INFO_TABLE_LENGTH);
        $info_table[STG::STANDARD_ENTRY_CODE] = $standard_entry_code;
        $info_table[STG::EVACUATION_CODE] = $evacuation_code;
        $info_table[STG::INFO_TABLE_LENGTH] = $scavenge_code;
        return $info_table;
    }

    const INFO_TABLE = 0;

    public static function closure($info_table, $length) {
        $closure = new \SplFixedArray($length + 1);
        $closure[STG::INFO_TABLE] = $info_table;
        return $closure;
    }

    public static function enter($state, $closure_address) {
        $state[STG::NODE] = $closure_address;
        $closure = $state[STG::HEAP][$closure_address];
        return static::jump($state, $closure[STG::INFO_TABLE][STG::STANDARD_ENTRY_CODE]);
    }

    /*

    === Indirection Closure ===

    */

    protected static $indirection_info = null;

    public static function indirection_closure($closure_address) {
        if (static::$indirection_info === null) {
            static::$indirection_info = 
                static::info_table
                    ( static::code_label("STG", "indirection_standard_entry_code")
                    , static::code_label("STG", "indirection_evacuation_code")
                    , static::code_label("STG", "indirection_scavenge_code")
                    );
        }
        $closure = closure(static::$indirection_info, 1);
        $closure[1] = $closure_address;
        return $closure;
    }

    public static function indirection_standard_entry_code($state, $closure_address) {
        $closure = $state[STG::HEAP][$closure_address];
        // SPJ says to load the closure to NODE, but that does not seem to make sense
        // as enter already does this.
        // $state[NODE] = $closure[1];
        return enter($state, $closure[1]);
    }

    public static function indirection_evacuation_code($state, $closure_address) {
        $closure = $state[STG::HEAP][$closure_address];
        return jump($closure[1]);
    }

    public static function indirection_scavenge_code($state) {
        throw new \LogicException("The scavenge code for an indirection was called, that".
                                  " should not happen.");
    }

    /*

    === Forwarding Pointer ===

    */

    public static function forwarding_pointer($closure_address) {
        $fp = static::closure
            ( static::info_table
                ( static::code_label("STG", "forwarding_pointer_standard_entry_code")
                , static::code_label("STG", "return_first_field")
                , static::code_label("STG", "nop")
                )
            , 1 
            );
        $fp[1] = $closure_address;
        return $fp;
    }

    static public function forwarding_pointer_standard_entry_code() {
        throw \LogicException("The standard entry code of a forwarding pointer".
                              " was called, that should not happen.");
    }


    /*

    === Machine State ===

    The implementation presented in the paper uses global variables to keep the state
    of the machine. We use a style where we pass the state explicitly as another array
    to have the possibility to execute several machines in parallel. As we use a more
    efficient array implementation then `array` we define labels for the fields of
    state.

    */

    const NODE = 0;
    const HEAP = 1;
    const STATE_LENGTH = 2;

    public static function state() {
        $state = new \SplFixedArray(STATE_LENGTH);
        $state[STG::HEAP] = array();
        return $state;
    }

    /*

    === Common Operations ===

    */

    static public function nop() {
    }

    static public function return_first_field() {
        throw new \Exception("NYI!");
    }

    /*

    === Garbage Collection ===

    The implementation from the paper uses a two space garbage collection which we
    will adopt. It uses two spaces during collection. During stop-and-copy garbage
    collection, closures are copied from *from-space* to *to-space* in two phases:

    * evacuate live closures from *from-space* to *to-space*
    * closure must be scavenged, that is, the closure to which a closure points must
      be evacuated too.

    We represent the closure as an index of the heap of the state here. I guess the
    following only is an example, at least the scavenging code will be generated for
    every closure independently i guess.

    I most propably won't need Garbage Collection, as PHP already does it. I'll figure
    it out later...

    */

    static public function evacuate($state, $closure_address) {
        // TODO: This procedure will lead to "holes" in the array used for the
        //       heap. I should think of something more clever.

        // Copy closure from *from-space* to *two-space*.
        $state[STG::HEAP][-1 * $closure_address] = $state[STG::HEAP][$closure_address];
        // Overwrite closure in *from-space* with a a forwarding pointer which points 
        // to the new copy of the closure in the *to-space*.
        $state[STG::HEAP][$closure_address] = static::forwarding_pointer(-1 * $closure_address);
        // Return new address of closure to caller.
        return -1 * $closure_address;
    }

    static public function scavenge($state, $closure_address) {
        // For every contained closure:
        $closure = $state[STG::HEAP][$closure_address];
        foreach ($closure as $key => $inner_closure) {
            // That's the info-table
            if ($key === 0) continue;
            // Call evacuation code of the closure.
            $new_label = static::call($state, $closure[STG::INFO_TABLE][STG::EVACUATION_CODE]);
            // Replace the pointer in the original closure with the *to-space*-pointer 
            // returned by the evacuation code.
            $closure[$key] = $new_label;             
        }
    }
}
