<?php
include('header.php');
include_once("../database/db_connect.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCET</title>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        body {
            background: #2c3e50;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .w3layouts-main {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            height: 100%;
            width: 100%;
        }

        .bg-layer {
            background: rgba(0, 0, 0, 0.7);

            height: 100%;
            width: 40%;

            display: flex;
            justify-content: center;
            align-items: center;

            border-radius: 0;
            margin: 0;
            padding: 0;
        }

        .header-main {
            background: #34495e;
            padding: 40px 30px;

            width: 80%;
            max-width: 420px;

            border-radius: 12px;
        }

        .main-icon img {
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .school-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            font-family: 'Lucida Sans Unicode', sans-serif;
            color: #ffffff; 
        }

        .header-left-bottom {
            margin-top: 20px;
        }

        .icon1 {
            margin-bottom: 15px;
            position: relative;
        }

        .icon1 input {
            width: 100%;
            height: 48px;
            padding: 10px 10px 10px 40px;
            border-radius: 6px;
        }


        .icon1 i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #34495e;
        }

        .login-check {
            text-align: left;
            margin-bottom: 15px;
        }

        .bottom {
            margin-top: 20px;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;

            display: none;
            justify-content: center;
            align-items: center;

            background: rgba(0, 0, 0, 0.65);
            z-index: 1000;
        }


        .popup {
            position: relative;

            width: 520px;
            height: 620px;

            border-radius: 14px;

            background: linear-gradient(135deg, #2c5364, #203a43, #0f2027);

            display: flex;
            justify-content: center;
            align-items: center;

            box-shadow: 0px 20px 50px rgba(0, 0, 0, 0.5);
        }

        /* iframe */
        .popup iframe {
            width: 100%;
            height: 100%;
            border: none;

            display: block;
        }

        /* Close button */
        .popup span {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 22px;
            font-weight: bold;
            color: #ff4d6d;
            cursor: pointer;
        }

        .btn {
            background: #1abc9c;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            width: 100%;
            height: 48px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 15px;
        }

        .btn:hover {
            background: #16a085;
        }

        .copyright {
            margin-top: 10px;
        }

        .copyright a {
            color: #ED2C02;
            /* Bright red */
        }

        .alert {
            padding: 20px;
            background-color: #f44336;
            color: white;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .closebtn {
            margin-left: 15px;
            color: white;
            font-weight: bold;
            float: right;
            font-size: 22px;
            line-height: 20px;
            cursor: pointer;
            transition: 0.3s;
        }

        .closebtn:hover {
            color: black;
        }

        #login-form {
            width: 100%;
        }

        /* Inputs */
        .icon1 input {
            width: 100%;
            height: 45px;
            padding: 10px 10px 10px 40px;
            border-radius: 6px;
            border: none;
        }

        /
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="script/validation.min.js"></script>
    <script src="script/login.js"></script>
    <script>
        addEventListener("load", function () {
            setTimeout(hideURLbar, 0);
        }, false);

        function hideURLbar() {
            window.scrollTo(0, 1);
        }


        function openRegister() {
            document.getElementById("registerPopup").style.display = "flex";
        }

        function closePopup() {
            document.getElementById("registerPopup").style.display = "none";
        }

    </script>
</head>

<body>

    <div class="w3layouts-main">
        <div class="bg-layer"><br /><br /><br /><br /><br />
            <div class="header-main">
                <div class="main-icon">
                    <img src="images/school_logo.jpg" alt="logo" width='150px' height='150px'>
                </div>
                <div class="school-name">
                    SPECIALIZATION TRACKER
                </div>
                <div class="header-left-bottom">
                    <form id="login-form" method="post">
                        <div class="icon1">
                            <i class="fa fa-user"></i>
                            <input type="text" placeholder="Enter username" name="username" id="username" required="" />
                        </div>
                        <div class="icon1">
                            <i class="fa fa-lock"></i>
                            <input type="password" placeholder="Enter password" name="password" id="password"
                                required="" />
                        </div>
                        <div class="login-check">
                            <label class="checkbox">
                                <input type="checkbox" name="checkbox" checked="">
                                <i></i> Keep me logged in
                            </label>
                        </div>
                        <div id="error" style="color:red;"></div>
                        <div class="bottom">
                            <button type="submit" class="btn" name="login_button" id="login_button">Log In</button>
                            <div style="margin-top:15px; text-align:center;">
                                <span style="color:#fff;">Are you Student?</span><br>
                                <button type="button" class="btn" onclick="openRegister()">
                                    Register
                                </button>
                            </div>
                    </form>
                </div>
            </div>
            <div id="registerPopup" class="overlay">
                <div class="popup">

                    <span style="float:right; cursor:pointer;" onclick="closePopup()">❌</span>

                    <iframe src="student_register.php" width="100%" height="500px" style="border:none;"></iframe>

                </div>
            </div>
            <div class="copyright">
                <p>© 2019. All rights reserved | Designed by <a href="https://dignityitsolution.com/"
                        target="_blank">Dignity IT Solution</a></p>
            </div>
        </div>
    </div>

</body>

</html>