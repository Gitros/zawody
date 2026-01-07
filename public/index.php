<?php
require_once __DIR__ . '/../app/auth.php';

startSession();
if (isLoggedIn()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
