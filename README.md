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


