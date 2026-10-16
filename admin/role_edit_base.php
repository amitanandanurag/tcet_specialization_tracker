<?php
session_start();
require "../database/db_connect.php";

$db_handle = new DBController();

$roleId = intval($roleId ?? 0);
$processFile = $processFile ?? '';

if ($roleId <= 0 || $processFile === '') {
	echo "<div class='alert alert-danger'>Role configuration is missing.</div>";
	exit;
}

$userId = intval($_POST['id'] ?? 0);
if ($userId <= 0) {
	echo "<div class='alert alert-danger'>Invalid user id.</div>";
	exit;
}

$sql = "SELECT * FROM st_user_master WHERE user_id = $userId AND role_id = $roleId LIMIT 1";
$result = $db_handle->query($sql);
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
	echo "<div class='alert alert-danger'>Record not found.</div>";
	exit;
}

$currentSubjectId = 0;
if ($roleId === 4) {
	$subjRes = $db_handle->query("SELECT subject_id FROM st_mentor_subject_mapping WHERE mentor_id = $userId LIMIT 1");
	if ($subjRes && $subjRow = $subjRes->fetch_assoc()) {
		$currentSubjectId = intval($subjMappingRow['subject_id'] ?? $subjRow['subject_id']);
	}
}
?>

<form method="post" action="<?php echo htmlspecialchars($processFile); ?>" autocomplete="off">
	<?php echo DBController::getCsrfInputField(); ?>
	<input type="hidden" name="user_id" value="<?php echo intval($userId); ?>">
	<input type="hidden" name="role_id" value="<?php echo intval($roleId); ?>">

	<div class="form-group">
		<label>Name <span style="color:red;">*</span></label>
		<input
			type="text"
			name="user_name"
			class="form-control"
			value="<?php echo htmlspecialchars($row['user_name'] ?? ''); ?>"
			required
		>
	</div>

	<div class="form-group">
		<label>Email <span style="color:red;">*</span></label>
		<input
			type="email"
			name="email_id"
			class="form-control"
			value="<?php echo htmlspecialchars($row['email_id'] ?? ''); ?>"
			required
		>
	</div>

	<div class="form-group">
		<label>Phone Number</label>
		<input
			type="text"
			name="phone_number"
			class="form-control"
			maxlength="15"
			value="<?php echo htmlspecialchars($row['phone_number'] ?? ''); ?>"
		>
	</div>

	<div class="form-group">
		<label>Department <span style="color:red;">*</span></label>
		<select name="department_id" class="form-control" required>
			<option value="">Select Department</option>
			<?php
			$deptSql = "SELECT department_id, department_name FROM st_department_master ORDER BY department_name ASC";
			$deptResult = $db_handle->query($deptSql);
			while ($dept = $deptResult->fetch_assoc()) {
				$selected = (intval($row['department_id']) === intval($dept['department_id'])) ? 'selected' : '';
			?>
				<option value="<?php echo intval($dept['department_id']); ?>" <?php echo $selected; ?>>
					<?php echo htmlspecialchars($dept['department_name']); ?>
				</option>
			<?php } ?>
		</select>
	</div>

	<?php if ($roleId === 4) { 
		$allocRes = $db_handle->query("
			SELECT msm.mapping_id, ssm.subject_name,
			       COALESCE(sem.semester_name, CONCAT('Semester ', msm.semester_id)) AS semester_name,
			       COALESCE(ay.session_name, CONCAT('AY ', msm.academic_year_id)) AS academic_year_name,
			       msm.is_active
			FROM st_mentor_subject_mapping msm
			JOIN st_specialization_subject_master ssm ON ssm.subject_id = msm.subject_id
			LEFT JOIN st_semester_master sem ON sem.semester_id = msm.semester_id
			LEFT JOIN st_session_master ay ON ay.session_id = msm.academic_year_id
			WHERE msm.mentor_id = $userId
			ORDER BY msm.academic_year_id DESC, msm.semester_id ASC
		");
	?>
		<div class="form-group" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 12px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
				<label style="margin: 0; font-size: 13px; font-weight: 600; color: #334155;">Active Semester & Subject Allocations</label>
				<a href="mentor_subject.php" class="btn btn-xs btn-erp-secondary"><i class="fa fa-external-link"></i> Manage Allocations</a>
			</div>
			<?php if ($allocRes && $allocRes->num_rows > 0) { ?>
				<div style="max-height: 150px; overflow-y: auto;">
					<table class="table table-condensed table-bordered" style="margin: 0; font-size: 12px; background: #fff;">
						<thead>
							<tr>
								<th>Subject</th>
								<th>Semester</th>
								<th>Academic Year</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php while ($al = $allocRes->fetch_assoc()) { ?>
								<tr>
									<td><strong><?php echo htmlspecialchars($al['subject_name']); ?></strong></td>
									<td><?php echo htmlspecialchars($al['semester_name']); ?></td>
									<td><?php echo htmlspecialchars($al['academic_year_name']); ?></td>
									<td>
										<?php if (intval($al['is_active']) === 1) { ?>
											<span class="label label-success">Active</span>
										<?php } else { ?>
											<span class="label label-default">Inactive</span>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			<?php } else { ?>
				<p class="text-muted" style="margin: 0; font-size: 12px;">No active semester allocations. Configure in <a href="mentor_subject.php">Mentor Subject Assignment</a>.</p>
			<?php } ?>
		</div>
	<?php } ?>

	<div style="margin-top: 16px; display: flex; align-items: center; gap: 8px;">
		<button
			type="submit"
			class="btn-erp-primary"
		><i class="fa fa-save"></i> Save Changes</button>
		<button
			type="reset"
			class="btn-erp-secondary"
		>Reset</button>
	</div>
</form>