<?php
declare(strict_types=1);

/**
 * TCET_ST — Enrollment Concurrency & ID Allocation Service
 *
 * Implements non-invasive MariaDB GET_LOCK mutex allocation for st_enrollment.enrollment_id.
 * Safe under concurrent web requests and multi-process batch promotions.
 */
class EnrollmentService
{
    public const MUTEX_NAME = 'tcet_st_enrollment_mutex';
    public const DEFAULT_TIMEOUT_SECONDS = 10;

    /**
     * Allocates the next sequential enrollment_id safely under MariaDB advisory lock.
     *
     * @param mysqli $conn Active MySQLi connection
     * @param int $timeoutSeconds Timeout in seconds to wait for lock acquisition
     * @return int Next available enrollment_id (>= 1)
     * @throws RuntimeException If lock acquisition fails or query fails
     */
    public static function allocateNextEnrollmentId(mysqli $conn, int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS): int
    {
        $lockRes = $conn->query("SELECT GET_LOCK('" . self::MUTEX_NAME . "', {$timeoutSeconds}) AS acquired");
        if (!$lockRes) {
            throw new RuntimeException("Failed executing GET_LOCK: " . $conn->error);
        }
        $acquired = (int)$lockRes->fetch_assoc()['acquired'];
        if ($acquired !== 1) {
            throw new RuntimeException("Enrollment allocation mutex timeout after {$timeoutSeconds}s. Concurrent allocation in progress.");
        }

        try {
            $res = $conn->query("SELECT COALESCE(MAX(enrollment_id), 0) + 1 AS next_id FROM st_enrollment FOR UPDATE");
            if (!$res) {
                throw new RuntimeException("Failed querying next enrollment_id: " . $conn->error);
            }
            return (int)$res->fetch_assoc()['next_id'];
        } finally {
            $conn->query("SELECT RELEASE_LOCK('" . self::MUTEX_NAME . "')");
        }
    }

    /**
     * Atomically creates an enrollment record with a concurrency-safe enrollment_id.
     *
     * @param mysqli $conn Active MySQLi connection
     * @param int $studentId
     * @param int $specializationId
     * @param int $semesterId
     * @param int $sessionId
     * @param string $status
     * @param int $timeoutSeconds
     * @return int The newly created enrollment_id
     * @throws RuntimeException On failure
     */
    public static function createEnrollment(
        mysqli $conn,
        int $studentId,
        int $specializationId,
        int $semesterId,
        int $sessionId,
        string $status = 'Active',
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS
    ): int {
        $lockRes = $conn->query("SELECT GET_LOCK('" . self::MUTEX_NAME . "', {$timeoutSeconds}) AS acquired");
        if (!$lockRes || (int)$lockRes->fetch_assoc()['acquired'] !== 1) {
            throw new RuntimeException("Enrollment allocation mutex timeout after {$timeoutSeconds}s.");
        }

        try {
            $conn->begin_transaction();

            $res = $conn->query("SELECT COALESCE(MAX(enrollment_id), 0) + 1 AS next_id FROM st_enrollment FOR UPDATE");
            if (!$res) {
                throw new RuntimeException("Failed querying next enrollment_id: " . $conn->error);
            }
            $nextId = (int)$res->fetch_assoc()['next_id'];

            $stmt = $conn->prepare("
                INSERT INTO st_enrollment (enrollment_id, student_id, specialization_id, semester_id, session_id, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new RuntimeException("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param('iiiiis', $nextId, $studentId, $specializationId, $semesterId, $sessionId, $status);
            if (!$stmt->execute()) {
                throw new RuntimeException("Insert into st_enrollment failed: " . $stmt->error);
            }
            $stmt->close();

            $conn->commit();
            return $nextId;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        } finally {
            $conn->query("SELECT RELEASE_LOCK('" . self::MUTEX_NAME . "')");
        }
    }
}
