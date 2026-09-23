<?php
$filesToInspect = [
    'admin/academic_import.php',
    'admin/admin_dashboard_ajax.php',
    'admin/all_class_ajax.php',
    'admin/class_manage.php',
    'admin/class_manage_new.php',
    'admin/class_edit_new.php',
    'admin/class_edit_new_ajax.php',
    'admin/excel_marks_upload.php',
    'admin/get_specializations.php',
    'admin/html_table.php',
    'admin/index1.php',
    'admin/index_old.php',
    'admin/list.php',
    'admin/reg_no.php',
    'admin/setting_profile.php',
    'admin/student-edit.php',
    'admin/student-update.php',
    'admin/student.php',
    'admin/student_admission_old.php',
    'admin/subwrite.php',
    'database/academic_importer.php',
    'database/antigravity_academic_data_2026_27.txt',
    'database/reset_test_data.sql',
    'database/tcet_st _updated.sql',
    'database/complete_database.sql',
    'database/migrations/20260827_semester_history.sql'
];

$root = 'c:/xampp/htdocs/st';
$report = [];

foreach ($filesToInspect as $f) {
    $full = "$root/$f";
    if (!file_exists($full)) {
        $report[$f] = ['exists' => false];
        continue;
    }
    $lines = file($full);
    $size = filesize($full);
    $head = array_slice($lines, 0, 15);
    $report[$f] = [
        'exists' => true,
        'size' => $size,
        'lines' => count($lines),
        'preview' => implode('', $head)
    ];
}

file_put_contents(__DIR__ . '/inspected_details.json', json_encode($report, JSON_PRETTY_PRINT));
echo "Inspected " . count($report) . " files. Details written to scratch/inspected_details.json\n";
