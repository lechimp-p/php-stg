<?php

namespace Lechimp\STG;

/**
 * Shared methods for garbage collection.
 */
trait GC {
    protected function collect_garbage_in_array(array &$array, array &$survivors) {
        foreach ($array as $key => $value) {
            if ($value instanceof Closures\Standard) {
                $array[$key] = $value->collect_garbage($survivors);
            }
        }
    }

    protected function collect_garbage_in_stack(\SPLFixedArray $array, array &$survivors) {
        $cnt = $array->count();
        for($i = 0; $i < $cnt; $i++) {
            $value = $array[$i];
            if ($value instanceof Closures\Standard) {
                $array[$i] = $value->collect_garbage($survivors);
            }
            else if (is_array($value)) {
                $this->collect_garbage_in_array($value, $survivors); 
            }
        }
    }
}
