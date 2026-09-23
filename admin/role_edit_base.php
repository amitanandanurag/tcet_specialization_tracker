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

	<?php if ($roleId === 4) { ?>
		<div class="form-group">
			<label>Specialization Subject <span style="color:red;">*</span></label>
			<select name="subject_id" class="form-control" required>
				<option value="">Select Specialization Subject</option>
				<?php
				$subSql = "SELECT subject_id, subject_name FROM st_specialization_subject_master ORDER BY subject_name ASC";
				$subResult = $db_handle->query($subSql);
				while ($sub = $subResult->fetch_assoc()) {
					$subSelected = (intval($sub['subject_id']) === $currentSubjectId) ? 'selected' : '';
				?>
					<option value="<?php echo intval($sub['subject_id']); ?>" <?php echo $subSelected; ?>>
						<?php echo htmlspecialchars($sub['subject_name']); ?>
					</option>
				<?php } ?>
			</select>
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