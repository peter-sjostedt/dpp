<?php
require_once __DIR__ . '/../src/Config/Auth.php';

use App\Config\Auth;

Auth::logout();
header('Location: login.php');
exit;
