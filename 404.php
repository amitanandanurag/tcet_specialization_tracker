<?php
// You can include your session check here if the dashboard requires login
// include('session_check.php'); 
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>404 Page Not Found</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <meta http-equiv="refresh" content="3;url=index.php" />
    
    <style>
        body { background-color: #f4f4f7; }
        .error-container { margin-top: 10%; }
        .error-code { font-size: 10px; font-weight: bold; color: #dd4b39; }
        .error-text { font-size: 24px; color: #555; }
    </style>
</head>
<body>

<div class="container text-center error-container">
    <svg fill="#423cbc" height="200px" width="200px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 60 60" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M9,39h6v8c0,0.552,0.448,1,1,1s1-0.448,1-1v-8h3c0.552,0,1-0.448,1-1s-0.448-1-1-1h-3v-2c0-0.552-0.448-1-1-1s-1,0.448-1,1 v2h-5V27c0-0.552-0.448-1-1-1s-1,0.448-1,1v11C8,38.552,8.448,39,9,39z"></path> <path d="M40,39h6v8c0,0.552,0.448,1,1,1s1-0.448,1-1v-8h3c0.552,0,1-0.448,1-1s-0.448-1-1-1h-3v-2c0-0.552-0.448-1-1-1 s-1,0.448-1,1v2h-5V27c0-0.552-0.448-1-1-1s-1,0.448-1,1v11C39,38.552,39.448,39,40,39z"></path> <path d="M29.5,48c3.584,0,6.5-2.916,6.5-6.5v-9c0-3.584-2.916-6.5-6.5-6.5S23,28.916,23,32.5v9C23,45.084,25.916,48,29.5,48z M25,32.5c0-2.481,2.019-4.5,4.5-4.5s4.5,2.019,4.5,4.5v9c0,2.481-2.019,4.5-4.5,4.5S25,43.981,25,41.5V32.5z"></path> <path d="M0,0v14v46h60V14V0H0z M2,2h56v10H2V2z M58,58H2V14h56V58z"></path> <polygon points="54.293,3.293 52,5.586 49.707,3.293 48.293,4.707 50.586,7 48.293,9.293 49.707,10.707 52,8.414 54.293,10.707 55.707,9.293 53.414,7 55.707,4.707 "></polygon> <path d="M3,11h39V3H3V11z M5,5h35v4H5V5z"></path> </g> </g></svg>
    <div class="error-code">Error 404</div>
    <div class="error-text">
        <i class="fa fa-warning text-yellow"></i> Oops! Page not found.
    </div>
    <p style="margin-top: 20px;">
        We couldn't find what you were looking for. <br>
        <strong>Redirecting you to the Dashboard within 3 seconds...</strong>
    </p>
    <a href="index.php" class="btn btn-primary btn-flat" style="margin-top: 20px;">
        <i class="fa fa-dashboard"></i> Go to HomePage Now
    </a>
</div>

</body>
</html>