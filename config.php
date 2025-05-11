<?php
/**
 * Database configuration using MySQLi
 */

//Database credentials
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'task_manager');
define('DB_USER', 'root');
define('DB_PASS', ''); //Add your database password if needed

//Create database connection
function getDbConnection() {
    //Create connection
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    //Check connection
    if ($mysqli->connect_error) {
        error_log("Database connection error: " . $mysqli->connect_error);
        throw new Exception("Database connection failed");
    }
    
    //Set charset to utf8mb4
    $mysqli->set_charset("utf8mb4");
    
    return $mysqli;
}