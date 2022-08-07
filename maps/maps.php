<?php

/*

Format:


[
    {
        "download": location to download file
        "x1": Smaller x coordinate, left of file
        "z1": Smaller z coordinate, top of file
        "x2": Larger x coordinate, right of file
        "z2": Larger z coordinate, bottom of file,
        "hash": Hash of File
    },
    ...
]


*/


$data = array();

$file_names = array('main-map.png', 'main-map-1.18.png');

foreach ($file_names as $file_name) {
    $data[] = array(
        "file" => $file_name,
        "x1" => -2383,
        "z1" => -6573,
        "x2" => 1651,
        "z2" => -159,
        "hash" => md5_file($file_name)
    );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
?>
