<?php

class ProgramTestBase extends PHPUnit_Framework_TestCase {
    protected function echo_program($program) {
        echo "\n\n-------- PROGRAM --------\n\n";
        $prg = split("\n", $program);
        foreach($prg as $no => $line) {
            echo sprintf("%3d", $no).": $line\n"; 
        } 
    }
}
