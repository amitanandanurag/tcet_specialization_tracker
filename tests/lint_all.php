<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/..');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/\.php$/');

$lintErrors = [];
foreach ($files as $file) {
    $path = $file->getRealPath();
    // Skip vendor / git / scratch
    if (strpos($path, '.git') !== false || strpos($path, 'scratch') !== false) {
        continue;
    }
    $output = [];
    $return_var = 0;
    exec("\"C:\\xampp\\php\\php.exe\" -l \"$path\"", $output, $return_var);
    if ($return_var !== 0) {
        $lintErrors[$path] = implode("\n", $output);
    }
}

if (empty($lintErrors)) {
    echo "All PHP files passed syntax check!\n";
} else {
    echo "Found syntax errors in " . count($lintErrors) . " files:\n";
    foreach ($lintErrors as $p => $err) {
        echo "File: $p\n$err\n\n";
    }
}
