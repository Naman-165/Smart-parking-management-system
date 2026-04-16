<?php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Report all PHP errors
error_reporting(E_ALL);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');     
define('DB_NAME', 'smartpark_db');

 $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($mysqli === false){
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database Connection Failed: ' . $mysqli->connect_error]);
    exit;
}
 $mysqli->set_charset("utf8");
?>