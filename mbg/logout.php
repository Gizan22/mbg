<?php
require __DIR__ . '/config/auth.php';
session_destroy();
header('Location: login.php');
exit;
