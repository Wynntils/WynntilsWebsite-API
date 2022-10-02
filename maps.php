<?php

$output = [];

// find all files in the maps folder
foreach (glob("maps/*.png", GLOB_NOSORT) as $filename) {
    // find corresponding json file for map
    $json_file = str_replace(".png", ".json", $filename);
    if (!file_exists($json_file)) {
        continue;
    }
    $mapData = json_decode(file_get_contents($json_file), true);

    // get hash of map
    $hash = md5_file($filename);

    // output list of all maps
    $output[] = [
        "name" => $mapData["name"],
        "url" => "https://api.wynntils.com/maps/" . basename($filename),
        "x1" => $mapData["x1"],
        "z1" => $mapData["z1"],
        "x2" => $mapData["x2"],
        "z2" => $mapData["z2"],
        "md5" => $hash
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($output, JSON_PRETTY_PRINT);
