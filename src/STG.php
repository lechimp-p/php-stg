<?php

function code_label($class, $method) {
    $label = new SplFixedArray(2);
    $label[0] = $class;
    $label[1] = $method;
    return $label;
}

function jump(SplFixedArray $label) {
    return $label[0]::$label[1]();
}
