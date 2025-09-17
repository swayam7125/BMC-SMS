<?php
// Database credentials for your Supabase project
$host = 'aws-1-ap-south-1.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.thfennvtfwzhxxcqpufz';
$password = 'SMS0407111726'; // It's recommended to use environment variables for passwords

// This constant can help with file paths and URLs in your application.
define('BASE_URL', '/BMC-SMS/');

// Create a PostgreSQL connection string (DSN) with additional options for Supabase
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password;options='--client_encoding=UTF8'";

try {
    // Create a PDO instance to establish the database connection
    $conn = new PDO($dsn);

    // Set the PDO error mode to exception for better error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Important: Disable prepared statements for Supabase pooler
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    // Set default fetch mode
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Set timeout options
    $conn->setAttribute(PDO::ATTR_TIMEOUT, 30);
} catch (PDOException $e) {
    // Log the actual error for debugging
    error_log("Database connection error: " . $e->getMessage());

    // Display a generic error to users
    die("Connection failed. Please try again later.");
}

// The $conn variable is now ready to be used for queries in other files.
