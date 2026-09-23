<?php
$dataDir = "C:/xampp/mysql/data/tcet_st";
if (is_dir($dataDir)) {
    $files = scandir($dataDir);
    echo "Files in $dataDir:\n";
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = "$dataDir/$f";
        echo " - $f (" . (is_dir($full) ? 'DIR' : filesize($full) . ' bytes') . ")\n";
    }
} else {
    echo "$dataDir is not a directory or does not exist.\n";
}
