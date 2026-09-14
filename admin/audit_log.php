<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

if (!isset($_SESSION['user_session'])) {
	header("location: ../index.php");
	exit;
}

if ((int) ($_SESSION['user_type'] ?? 0) !== 1) {
	header("location: index.php");
	exit;
}

require "header/header.php";

$db_handle->ensureAuditLogTable();

$actionType = trim($_GET['action_type'] ?? '');
$search = trim($_GET['search'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$where = array();
$params = array();
$types = '';

if ($actionType !== '') {
	$where[] = 'a.action_type = ?';
	$params[] = $actionType;
	$types .= 's';
}

if ($search !== '') {
	$where[] = '(a.action_type LIKE ? OR a.description LIKE ? OR a.username LIKE ? OR a.ip_address LIKE ? OR CAST(a.user_id AS CHAR) LIKE ?)';
	$searchLike = '%' . $search . '%';
	$params[] = $searchLike;
	$params[] = $searchLike;
	$params[] = $searchLike;
	$params[] = $searchLike;
	$params[] = $searchLike;
	$types .= 'sssss';
}

if ($fromDate !== '') {
	$where[] = 'DATE(a.performed_at) >= ?';
	$params[] = $fromDate;
	$types .= 's';
}

if ($toDate !== '') {
	$where[] = 'DATE(a.performed_at) <= ?';
	$params[] = $toDate;
	$types .= 's';
}

$auditTypes = array();
$typeResult = mysqli_query($db_handle->conn, "SELECT DISTINCT action_type FROM st_audit_log ORDER BY action_type ASC");
if ($typeResult) {
	while ($typeRow = mysqli_fetch_assoc($typeResult)) {
		$actionName = trim((string) ($typeRow['action_type'] ?? ''));
		if ($actionName !== '') {
			$auditTypes[] = $actionName;
		}
	}
}
if (!in_array('NEVER_LOGGED_IN', $auditTypes, true)) {
	$auditTypes[] = 'NEVER_LOGGED_IN';
}

$auditSql = "SELECT a.audit_id, a.user_id, a.action_type, a.affected_table, a.affected_record, a.description, a.username, a.ip_address, a.browser_user_agent, a.session_duration_seconds, a.logout_at, a.performed_at FROM st_audit_log a";
if (!empty($where)) {
	$auditSql .= ' WHERE ' . implode(' AND ', $where);
}
$auditSql .= ' ORDER BY a.performed_at DESC, a.audit_id DESC LIMIT 500';

$auditLogs = array();
$auditStmt = mysqli_prepare($db_handle->conn, $auditSql);
if ($auditStmt) {
	if (!empty($params)) {
		$bindParams = array();
		$bindParams[] = $types;
		foreach ($params as $index => $value) {
			$bindParams[] = &$params[$index];
		}
		call_user_func_array(array($auditStmt, 'bind_param'), $bindParams);
	}

	mysqli_stmt_execute($auditStmt);
	$auditResult = mysqli_stmt_get_result($auditStmt);
	if ($auditResult) {
		while ($row = mysqli_fetch_assoc($auditResult)) {
			$auditLogs[] = $row;
		}
	}
	mysqli_stmt_close($auditStmt);
}

$neverLoggedRows = array();
$includeNeverLogged = ($actionType === '' || $actionType === 'NEVER_LOGGED_IN') && $fromDate === '' && $toDate === '';
if ($includeNeverLogged) {
	$neverSql = "SELECT u.user_id,
		COALESCE(NULLIF(TRIM(u.email_id), ''), NULLIF(TRIM(l.username), ''), NULLIF(TRIM(u.user_name), ''), CONCAT('User #', u.user_id)) AS username
		FROM st_user_master u
		LEFT JOIN st_login l ON l.user_id = u.user_id
		LEFT JOIN st_audit_log a ON a.user_id = u.user_id AND a.action_type = 'LOGIN_SUCCESS'
		WHERE a.audit_id IS NULL";

	$neverParams = array();
	$neverTypes = '';
	if ($search !== '') {
		$neverSql .= " AND (
			u.user_name LIKE ? OR u.email_id LIKE ? OR l.username LIKE ? OR CAST(u.user_id AS CHAR) LIKE ?
		)";
		$searchLike = '%' . $search . '%';
		$neverParams = array($searchLike, $searchLike, $searchLike, $searchLike);
		$neverTypes = 'ssss';
	}

	$neverSql .= ' ORDER BY u.user_id DESC LIMIT 300';
	$neverStmt = mysqli_prepare($db_handle->conn, $neverSql);
	if ($neverStmt) {
		if (!empty($neverParams)) {
			mysqli_stmt_bind_param($neverStmt, $neverTypes, $neverParams[0], $neverParams[1], $neverParams[2], $neverParams[3]);
		}

		mysqli_stmt_execute($neverStmt);
		$neverResult = mysqli_stmt_get_result($neverStmt);
		if ($neverResult) {
			while ($neverRow = mysqli_fetch_assoc($neverResult)) {
				$neverLoggedRows[] = array(
					'audit_id' => 0,
					'user_id' => (int) ($neverRow['user_id'] ?? 0),
					'action_type' => 'NEVER_LOGGED_IN',
					'affected_table' => '',
					'affected_record' => '',
					'description' => 'User has not logged in yet.',
					'username' => (string) ($neverRow['username'] ?? ''),
					'ip_address' => '',
					'browser_user_agent' => '',
					'session_duration_seconds' => null,
					'logout_at' => '',
					'performed_at' => ''
				);
			}
		}
		mysqli_stmt_close($neverStmt);
	}
}

if (!empty($neverLoggedRows)) {
	$auditLogs = array_merge($auditLogs, $neverLoggedRows);
}

$totalCountResult = mysqli_query($db_handle->conn, "SELECT COUNT(*) AS total FROM st_audit_log");
$totalCount = $totalCountResult ? (int) (mysqli_fetch_assoc($totalCountResult)['total'] ?? 0) : 0;
$totalCount += count($neverLoggedRows);
$filteredCount = count($auditLogs);
$loginCountResult = mysqli_query($db_handle->conn, "SELECT COUNT(*) AS total FROM st_audit_log WHERE action_type = 'LOGIN_SUCCESS'");
$loginCount = $loginCountResult ? (int) (mysqli_fetch_assoc($loginCountResult)['total'] ?? 0) : 0;
$logoutCountResult = mysqli_query($db_handle->conn, "SELECT COUNT(*) AS total FROM st_audit_log WHERE action_type = 'LOGIN_SUCCESS' AND logout_at IS NOT NULL");
$logoutCount = $logoutCountResult ? (int) (mysqli_fetch_assoc($logoutCountResult)['total'] ?? 0) : 0;
$activeCountResult = mysqli_query($db_handle->conn, "SELECT COUNT(*) AS total FROM st_audit_log WHERE action_type = 'LOGIN_SUCCESS' AND logout_at IS NULL");
$activeCount = $activeCountResult ? (int) (mysqli_fetch_assoc($activeCountResult)['total'] ?? 0) : 0;

function audit_format_datetime($value)
{
	$value = trim((string) $value);
	if ($value === '' || $value === '0000-00-00 00:00:00') {
		return '-';
	}

	$timestamp = strtotime($value);
	return $timestamp ? date('d M Y, h:i A', $timestamp) : $value;
}

function audit_session_status($logRow)
{
	$actionType = (string) ($logRow['action_type'] ?? '');
	$logoutAt = trim((string) ($logRow['logout_at'] ?? ''));

	if ($actionType === 'LOGIN_SUCCESS' && $logoutAt !== '') {
		return array('Completed', 'label-success', 'Login and logout recorded');
	}

	if ($actionType === 'LOGIN_SUCCESS') {
		return array('Logged In', 'label-warning', 'Logout pending');
	}

	if ($actionType === 'LOGIN_FAILED') {
		return array('Failed Login', 'label-danger', 'Invalid login attempt');
	}

	if ($actionType === 'LOGOUT_SUCCESS') {
		return array('Logged Out', 'label-default', 'Old separate logout record');
	}

	if ($actionType === 'NEVER_LOGGED_IN') {
		return array('Never Logged In', 'label-default', 'User exists but has not visited website yet');
	}

	return array($actionType, 'label-info', trim((string) ($logRow['description'] ?? '')));
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="row align-items-center">
      <div class="col-xs-12 col-sm-8">
        <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b; letter-spacing: -0.01em;">
          <i class="fa fa-shield text-muted" style="margin-right: 8px;"></i>System Audit & Session Ledger
        </h1>
        <p style="margin: 3px 0 0 0; font-size: 13px; color: #64748b;">Comprehensive security audit log of user authentications, activity timestamps, and administrative actions</p>
      </div>
      <div class="col-xs-12 col-sm-4 text-right">
        <ol class="breadcrumb" style="position: static; float: none; background: transparent; padding: 0; margin: 5px 0 0 0; font-size: 12px;">
          <li><a href="index.php" style="color: #64748b;"><i class="fa fa-home"></i> Home</a></li>
          <li class="active" style="color: #423cbc; font-weight: 600;">Audit Ledger</li>
        </ol>
      </div>
    </div>
  </section>

  <section class="content" style="padding-top: 15px;">
    <!-- ERP Metric Summary Strip -->
    <div class="erp-summary-bar" style="margin-bottom: 18px;">
      <div class="erp-metric-item">
        <span class="erp-metric-num"><?php echo number_format($totalCount); ?></span>
        <span class="erp-metric-label">Total Log Records</span>
      </div>
      <div class="erp-metric-item">
        <span class="erp-metric-num" style="color: #166534;"><?php echo number_format($logoutCount); ?></span>
        <span class="erp-metric-label">Completed Sessions</span>
      </div>
      <div class="erp-metric-item">
        <span class="erp-metric-num" style="color: #ca8a04;"><?php echo number_format($activeCount); ?></span>
        <span class="erp-metric-label">Active / In-Progress</span>
      </div>
      <div class="erp-metric-item">
        <span class="erp-metric-num" style="color: #dc2626;"><?php echo number_format(max(0, $totalCount - $loginCount)); ?></span>
        <span class="erp-metric-label">Failed / Legacy Logs</span>
      </div>
    </div>

    <div class="box box-default" style="border-top: 3px solid #423cbc; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-radius: 4px;">
      <div class="box-body" style="padding: 16px;">
        <!-- Filter Toolbar -->
        <div class="erp-toolbar" style="margin-bottom: 16px;">
          <form method="get" class="row">
            <div class="col-sm-4 col-md-3" style="margin-bottom: 8px;">
              <label class="erp-filter-label">Search User / IP / ID</label>
              <input type="text" name="search" class="form-control input-sm" placeholder="Enter username, IP, or ID..." value="<?php echo htmlspecialchars($search); ?>" style="border-radius: 3px;">
            </div>
            <div class="col-sm-4 col-md-3" style="margin-bottom: 8px;">
              <label class="erp-filter-label">Action / Status</label>
              <select name="action_type" class="form-control input-sm" style="border-radius: 3px;">
                <option value="">All Action Types</option>
                <?php foreach ($auditTypes as $typeName) { ?>
                  <option value="<?php echo htmlspecialchars($typeName); ?>" <?php echo $actionType === $typeName ? 'selected' : ''; ?>><?php echo htmlspecialchars(str_replace('_', ' ', $typeName)); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-sm-4 col-md-2" style="margin-bottom: 8px;">
              <label class="erp-filter-label">From Date</label>
              <input type="date" name="from_date" class="form-control input-sm" value="<?php echo htmlspecialchars($fromDate); ?>" style="border-radius: 3px;">
            </div>
            <div class="col-sm-4 col-md-2" style="margin-bottom: 8px;">
              <label class="erp-filter-label">To Date</label>
              <input type="date" name="to_date" class="form-control input-sm" value="<?php echo htmlspecialchars($toDate); ?>" style="border-radius: 3px;">
            </div>
            <div class="col-sm-4 col-md-2" style="margin-top: 22px; margin-bottom: 8px; display:flex; gap:6px;">
              <button type="submit" class="btn-erp-primary" style="padding: 5px 12px; font-size: 12px;">
                <i class="fa fa-filter"></i> Apply
              </button>
              <a href="audit_log.php" class="btn-erp-secondary" style="padding: 5px 12px; font-size: 12px;">
                <i class="fa fa-refresh"></i> Reset
              </a>
            </div>
          </form>
        </div>

        <div style="margin-bottom: 12px; font-size: 11px; color: #64748b;">
          <strong style="color: #475569;">Status Legend:</strong>
          <span style="display: inline-block; margin-left: 8px; padding: 2px 6px; border-radius: 2px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-weight: 600;">Completed</span> Normal Session
          <span style="display: inline-block; margin-left: 8px; padding: 2px 6px; border-radius: 2px; background: #fefce8; color: #854d0e; border: 1px solid #fef08a; font-weight: 600;">Logged In</span> Active Session
          <span style="display: inline-block; margin-left: 8px; padding: 2px 6px; border-radius: 2px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; font-weight: 600;">Failed</span> Invalid Authentication
          <span style="display: inline-block; margin-left: 8px; padding: 2px 6px; border-radius: 2px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; font-weight: 600;">Never Logged In</span> Inactive User
        </div>

        <div class="table-responsive" style="border: 1px solid #e2e8f0; border-radius: 4px;">
          <table class="table table-bordered table-hover table-striped erp-table" style="margin-bottom: 0;">
            <thead>
              <tr style="background: #f8fafc;">
                <th>USER ACCOUNT</th>
                <th style="width: 130px; text-align: center;">STATUS</th>
                <th>LOGIN TIMESTAMP</th>
                <th>LOGOUT TIMESTAMP</th>
                <th style="width: 90px; text-align: right;">DURATION</th>
                <th>IP ADDRESS</th>
                <th>ENVIRONMENT / DETAIL</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($auditLogs)) { ?>
                <?php foreach ($auditLogs as $logRow) { ?>
                  <?php
                  $statusData = audit_session_status($logRow);
                  $statusText = $statusData[0];
                  $statusClass = $statusData[1];
                  $statusDetail = $statusData[2];
                  $actionType = (string) ($logRow['action_type'] ?? '');
                  $isLegacyLogout = $actionType === 'LOGOUT_SUCCESS';
                  $loginTime = $isLegacyLogout ? '-' : audit_format_datetime($logRow['performed_at'] ?? '');
                  $logoutTime = $isLegacyLogout ? audit_format_datetime($logRow['performed_at'] ?? '') : audit_format_datetime($logRow['logout_at'] ?? '');
                  $browser = trim((string) ($logRow['browser_user_agent'] ?? ''));
                  ?>
                  <tr>
                    <td>
                      <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars(trim((string) ($logRow['username'] ?? '')) !== '' ? $logRow['username'] : 'User #' . (int) $logRow['user_id']); ?></div>
                      <div style="font-size: 11px; color: #64748b; font-family: monospace;">UID: <?php echo (int) $logRow['user_id']; ?> · LOG: #<?php echo (int) $logRow['audit_id']; ?></div>
                    </td>
                    <td class="text-center">
                      <span class="label <?php echo htmlspecialchars($statusClass); ?>" style="font-size: 11px; padding: 3px 6px; font-weight: 600;">
                        <?php echo htmlspecialchars($statusText); ?>
                      </span>
                    </td>
                    <td style="font-size: 12px; color: #334155;"><?php echo htmlspecialchars($loginTime); ?></td>
                    <td style="font-size: 12px; color: #334155;"><?php echo htmlspecialchars($logoutTime); ?></td>
                    <td class="col-num" style="font-family: monospace; font-size: 12px; font-weight: 600; text-align: right;">
                      <?php
                      $duration = $logRow['session_duration_seconds'];
                      echo $duration !== null ? gmdate('H:i:s', (int) $duration) : '-';
                      ?>
                    </td>
                    <td style="font-family: monospace; font-size: 11px; color: #475569;">
                      <?php
                      $locationIp = trim((string) ($logRow['ip_address'] ?? ''));
                      echo htmlspecialchars($locationIp !== '' ? $locationIp : '-');
                      ?>
                    </td>
                    <td style="font-size: 11px;">
                      <?php if ($browser !== '') { ?>
                        <div style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #475569;" title="<?php echo htmlspecialchars($browser); ?>"><?php echo htmlspecialchars($browser); ?></div>
                        <div style="color: #64748b;"><?php echo htmlspecialchars($statusDetail); ?></div>
                      <?php } else { ?>
                        <span style="color: #64748b;"><?php echo htmlspecialchars($statusDetail); ?></span>
                      <?php } ?>
                    </td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr>
                  <td colspan="7" class="text-center text-muted" style="padding: 24px;">No audit log records found for the selected criteria.</td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?php require "header/footer.php"; ?>
