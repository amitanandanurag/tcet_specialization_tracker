<?php
require_once __DIR__ . "/../database/db_connect.php";
$db = new DBController();
$conn = $db->conn;

echo "Checking st_mentor_subject_mapping columns...\n";
$res = mysqli_query($conn, "DESCRIBE st_mentor_subject_mapping");
while ($r = mysqli_fetch_assoc($res)) {
    echo " - {$r['Field']} ({$r['Type']})\n";
}

echo "Adding missing columns to st_mentor_subject_mapping...\n";
mysqli_query($conn, "ALTER TABLE st_mentor_subject_mapping ADD COLUMN IF NOT EXISTS academic_year_id INT(11) NOT NULL DEFAULT 1 AFTER subject_id");
mysqli_query($conn, "ALTER TABLE st_mentor_subject_mapping ADD COLUMN IF NOT EXISTS semester_id INT(11) NOT NULL DEFAULT 0 AFTER academic_year_id");
mysqli_query($conn, "ALTER TABLE st_mentor_subject_mapping ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
mysqli_query($conn, "ALTER TABLE st_mentor_subject_mapping ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

// Drop old unique index if any and add composite unique index
@mysqli_query($conn, "ALTER TABLE st_mentor_subject_mapping DROP INDEX uq_mentor_subject");
@mysqli_query($conn, "ALTER TABLE st_mentor_subject_mapping DROP INDEX unique_period_subject_mentor");
mysqli_query($conn, "ALTER TABLE st_mentor_subject_mapping ADD UNIQUE KEY unique_period_subject_mentor (subject_id, semester_id, academic_year_id)");

echo "Done upgrading st_mentor_subject_mapping schema!\n";
