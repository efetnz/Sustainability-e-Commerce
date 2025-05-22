<?php
require_once 'config/config.php';

session_destroy();

// redirect to home
header('Location: index.php');
exit;
?> 