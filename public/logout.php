<?php
require_once __DIR__ . '/../app/auth.php';

startSession();
session_destroy();

header("Location: login.php");
exit;
