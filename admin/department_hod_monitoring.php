<?php
/**
 * TCET Specialization Tracker (TCET_ST)
 * Department & HOD Monitoring Entry Point
 *
 * Provides Super Admin and Admin with:
 * 1. Institutional summary of all academic departments.
 * 2. Active HODs, verified appointments, and contact details.
 * 3. Total assigned and unassigned faculty mentors per department.
 * 4. Active student enrollment metrics.
 * 5. Direct operational drill-down modals (Department, HOD, Mentors, Allocations).
 *
 * RBAC: Strictly restricted to Super Admin (role_id = 1) and Admin (role_id = 2).
 */

require_once __DIR__ . '/hod_mentor_allocation.php';
