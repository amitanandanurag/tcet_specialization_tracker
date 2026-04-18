$('document').ready(function() { 
	/* handling form validation */
	$("#login-form").validate({
		rules: {
			password: {
				required: true,
			},
			username: {
				required: true
				
			},
		},
		messages: {
			password:{
			  required: "please enter your password"
			 },
			username: "please enter your username",
		},
		submitHandler: submitForm	
	});	   
	/* Handling login functionality */
	function submitForm() {		
		var data = $("#login-form").serialize();				
		$.ajax({				
			type : 'POST',
			url  : 'login.php',
			data : data,
			beforeSend: function(){	
				$("#error").fadeOut();
				$("#login_button").html('<span class="glyphicon glyphicon-transfer"></span> &nbsp; sending ...');
			},
			success : function(response){	
				var cleanResponse = $.trim(response || '');
				var lowerResponse = cleanResponse.toLowerCase();

				if (
					lowerResponse.indexOf('fatal error') !== -1 ||
					lowerResponse.indexOf('uncaught') !== -1 ||
					lowerResponse.indexOf('mysqli_sql_exception') !== -1 ||
					lowerResponse.indexOf('unable to connect with database') !== -1
				) {
					cleanResponse = 'Unable to connect with database';
				}

				console.log(cleanResponse);


				if(cleanResponse=="ok" || cleanResponse=="ok1" || cleanResponse=="ok2" || cleanResponse=="ok3" || cleanResponse=="ok4"){
					$("#login_button").html('<img src="ajax-loader.gif" /> &nbsp; Signing In ...');
					setTimeout(function(){ window.location.href = "../admin"; }, 4000);
				} 
				else {
					$("#error").fadeIn(1000, function(){
						var $alert = $('<div class="alert alert-danger">');
						$('<span class="glyphicon glyphicon-info-sign">').appendTo($alert);
						$alert.append(document.createTextNode(' ' + cleanResponse + ' !'));
						$("#error").empty().append($alert);
						$("#login_button").html('<span class="glyphicon glyphicon-log-in"></span> &nbsp; Sign In');
					});
				}
			},
			error: function(){
				$("#error").fadeIn(1000, function(){
					$("#error").html('<div class="alert alert-danger"> <span class="glyphicon glyphicon-info-sign"></span> &nbsp; Unable to connect with database !</div>');
					$("#login_button").html('<span class="glyphicon glyphicon-log-in"></span> &nbsp; Sign In');
				});
			}
		});
		return false;
	}   
});