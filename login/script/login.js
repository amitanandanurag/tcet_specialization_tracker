
$(document).ready(function () {
	$("#login-form").validate({
		rules: {
			username: { required: true },
			password: { required: true }
		},
		messages: {
			username: "Please enter your username or registration number.",
			password: "Please enter your password."
		},
		errorClass: "erp-input-error-msg",
		errorElement: "div",
		highlight: function(element) {
			$(element).closest('.erp-input-group').addClass('erp-input-invalid');
		},
		unhighlight: function(element) {
			$(element).closest('.erp-input-group').removeClass('erp-input-invalid');
		},
		submitHandler: function (form, event) {
			event.preventDefault();
			submitForm();
		}
	});

	function submitForm() {
		var formData = $("#login-form").serialize();
		if (formData.indexOf('login_button=') === -1) {
			formData += '&login_button=1';
		}

		var $btn = $("#login_button");
		var originalBtnText = $btn.html();

		$.ajax({
			type: 'POST',
			url: 'login.php',
			data: formData,
			beforeSend: function () {
				$("#error").fadeOut().empty();
				$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Signing In...');
			},
			success: function (response) {
				var cleanResponse = $.trim(response || '');
				var lowerResponse = cleanResponse.toLowerCase();

				if (
					lowerResponse.indexOf('fatal error') !== -1 ||
					lowerResponse.indexOf('uncaught') !== -1 ||
					lowerResponse.indexOf('mysqli_sql_exception') !== -1 ||
					lowerResponse.indexOf('unable to connect with database') !== -1
				) {
					cleanResponse = 'Unable to connect with the server database. Please try again later.';
				} else if (cleanResponse === "email or password does not exist.") {
					cleanResponse = 'Invalid username or password. Please verify your credentials.';
				}

				if (cleanResponse === "change_password") {
					$btn.html('<i class="fa fa-check"></i> Redirecting...');
					setTimeout(function () {
						window.location.href = "../admin/change_password.php";
					}, 400);
					return;
				} else if (cleanResponse === "ok" || cleanResponse === "ok1" || cleanResponse === "ok2" || cleanResponse === "ok3" || cleanResponse === "ok4") {
					$btn.html('<i class="fa fa-check"></i> Success! Redirecting...');
					var redirectUrl = cleanResponse === "ok4" ? "../admin/student_dashboard.php" : "../admin/index.php";
					setTimeout(function () {
						window.location.href = redirectUrl;
					}, 600);
				} else {
					$btn.prop('disabled', false).html('<i class="fa fa-sign-in"></i> Sign In');
					var alertHtml = '<div class="erp-login-alert">' +
						'<i class="fa fa-exclamation-circle"></i> ' + $('<div>').text(cleanResponse).html() +
						'</div>';
					$("#error").html(alertHtml).fadeIn();
				}
			},
			error: function() {
				$btn.prop('disabled', false).html('<i class="fa fa-sign-in"></i> Sign In');
				$("#error").html('<div class="erp-login-alert"><i class="fa fa-exclamation-circle"></i> Server connection error. Please try again.</div>').fadeIn();
			}
		});
		return false;
	}
});