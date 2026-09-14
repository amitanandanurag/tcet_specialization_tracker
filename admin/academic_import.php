<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header("Location: mentor_assignment.php");
exit();
