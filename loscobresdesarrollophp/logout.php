<?php
require_once 'controllers/AuthenticationController.php';

AuthenticationController::logout();

header("Location: login.php");
exit;
?>
