<?php
/*
= The STG for PHP =

This implements the Spineless Tagless G-Machine by Simon Peyton Jones in PHP.

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

function code_label($class, $method) {
    $label = new SplFixedArray(2);
    $label[0] = $class;
    $label[1] = $method;
    return $label;
}

function jump($state, SplFixedArray $label) {
    return $label[0]::$label[1]($state);
}

function call($state, SplFixedArray $label) {
    return $label[0]::$label[1]($state);
}

/*

=== Interpreter ===

We use an interpreter very similar to the one from the paper:

*/
function interpret($state) {
    while ($label !== null) {
        $label = jump($state, $label);
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

function info_table($standard_entry_code, $evacuation_code, $scavenge_code) {
    $info_table = new SplFixedArray(INFO_TABLE_LENGTH);
    $info_table[STANDARD_ENTRY_CODE] = $standard_entry_code;
    $info_table[EVACUATION_CODE] = $evacuation_code;
    $info_table[INFO_TABLE_LENGTH] = $scavenge_code;
    return $info_table;
}

const INFO_TABLE = 0;

function closure($info_table, $length) {
    $closure = new SplFixedArray($length + 1);
    $closure[0] = $info_table;
    return $closure;
}

function forwarding_pointer($closure_address) {
    $fp = closure
        ( info_table
            ( code_label("Error", "forwarding_pointer_entry_code_called")
            , code_label("Common", "return_first_field")
            , code_label("Common", "nop");
            )
        , 1 
        );
    $fp[1] = $closure_address;
    return $fp;
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

function state() {
    $state = new SplFixedArray(STATE_LENGTH);
    $state[HEAP] = array();
    return $state;
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

*/

class GarbageCollection {
    static public function evacuate($state, $closure_address) {
        // Copy closure from *from-space* to *two-space*.
        $state[HEAP][-1 * $closure_address] = $state[HEAP][$closure_address];
        // Overwrite closure in *from-space* with a a forwarding pointer which points 
        // to the new copy of the closure in the *to-space*.
        $state[HEAP][$closure_address] = forwarding_pointer(-1 * $closure_address);
        // Return new address of closure to caller.
        return -1 * $closure_address;
    }

    static public function scavenge($state, $closure_address) {
        // For every contained closure:
        $closure = $state[HEAP][$closure_address];
        foreach ($closure as $key => $inner_closure) {
            // That's the info-table
            if ($key === 0) continue;
            // Call evacuation code of the closure.
            $new_label = call($state, $closure[INFO_TABLE][EVACUATION_CODE]);
            // Replace the pointer in the original closure with the *to-space*-pointer 
            // returned by the evacuation code.
            $closure[$key] = $new_label;             
        }
    }
}

class Common {
    static public function nop() {
    }

    static public function return_first_field() {
        throw new Exception("NYI!");
    }
}

class Errors {
    static public function forwarding_pointer_entry_code_called() {
        die("The standard entry code of a forwarding pointer was called, that".
            " should not happen.");
    }
}
