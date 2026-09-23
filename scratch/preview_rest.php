<?php
$files = [
    'admin/index_old.php',
    'admin/index1.php',
    'admin/academic_import.php',
    'admin/admin_dashboard_ajax.php',
    'admin/all_class_ajax.php',
    'admin/class_manage.php',
    'admin/class_manage_new.php',
    'admin/excel_marks_upload.php',
    'admin/get_specializations.php',
    'admin/html_table.php',
    'admin/list.php'
];

foreach ($files as $f) {
    $full = "c:/xampp/htdocs/st/$f";
    if (file_exists($full)) {
        echo "========================================================\n";
        echo "FILE: $f (" . filesize($full) . " bytes)\n";
        echo "--------------------------------------------------------\n";
        $content = file_get_contents($full);
        echo substr($content, 0, 400) . "\n\n";
    }
}
