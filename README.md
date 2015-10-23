# The Spineless Tagless G-Machine in PHP

Ok, I actually do not really know if my machine is spineless or tagless, but it
certainly reduces graphs.

This is my attempt to *Implementing lazy functional languages on stock hardware:
the Spineless Tagless G-machine* by Simon L. Peyton Jones, where the stock hardware
is PHP. The mechanics in this implementation are a little different from the
mechanics described in the paper, which targets C. Thus i especially handle namespacing,
jumps and the heap a little different. Garbage collection and pointer managment is 
mostly done by PHP. I also tried to implement my machine in a way that uses no 
globals but a STG object instead. Most of the concepts outlined in the paper should
be recognizable still. This is for my fun and education, and hopefully yours too.
