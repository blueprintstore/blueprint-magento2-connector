<?php

function load_state($file) {
    $default = '2000-01-01T00:00:00Z';
    if (!file_exists($file)) return $default;
    $json = file_get_contents($file);
    $data = json_decode($json);
    if (@$data->checkpoint) return $data->checkpoint;
    return $default;
}

function save_state($file, $checkpoint){
    file_put_contents($file, json_encode(['checkpoint'=>$checkpoint]));
}