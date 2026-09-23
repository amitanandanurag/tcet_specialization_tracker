<?php
$candidates = [
    'admin/index_old.php',
    'admin/index1.php',
    'admin/student_admission_old.php',
    'admin/class_manage.php',
    'admin/class_manage_new.php',
    'admin/class_edit_new.php',
    'admin/class_edit_new_ajax.php',
    'admin/student-update.php',
    'admin/setting_profile.php',
    'admin/academic_import.php',
    'admin/get_specializations.php',
    'admin/subwrite.php',
    'admin/html_table.php',
    'admin/js/driver.js',
    'database/antigravity_academic_data_2026_27.txt',
    'database/tcet_st _updated.sql'
];

$root = 'c:/xampp/htdocs/st';
$allFiles = [];
$ite = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($ite as $f) {
    if (strpos($f->getPathname(), '.git') !== false || strpos($f->getPathname(), 'scratch') !== false) continue;
    $ext = strtolower($f->getExtension());
    if (in_array($ext, ['php', 'js', 'css', 'html', 'sql', 'htaccess', 'json', 'md'])) {
        $allFiles[$f->getPathname()] = file_get_contents($f->getPathname());
    }
}

echo "=== CANDIDATE REFERENCE CROSS-CHECK ===\n";
foreach ($candidates as $cand) {
    $base = basename($cand);
    $hits = [];
    foreach ($allFiles as $path => $content) {
        $relPath = str_replace('\\', '/', substr($path, strlen($root) + 1));
        if ($relPath === $cand) continue;
        
        if (stripos($content, $base) !== false) {
            $hits[] = $relPath;
        }
    }
    echo sprintf("%-45s : %d references -> %s\n", $cand, count($hits), implode(', ', $hits));
}
