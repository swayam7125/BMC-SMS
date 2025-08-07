<?php
// Database credentials for your Supabase project
$host = 'aws-0-ap-south-1.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.fwkvbvmmfwyjpqjileil';
$password = '0407111726'; // It's recommended to use environment variables for passwords

// This constant can help with file paths and URLs in your application.
define('BASE_URL', '/BMC-SMS/');

// Create a PostgreSQL connection string (DSN)
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";

try {
    // Create a PDO instance to establish the database connection
    $conn = new PDO($dsn);

    // Set the PDO error mode to exception for better error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the connection fails, stop the script and display a generic error.
    // Logging the actual error ($e->getMessage()) is better for production.
    // To debug, uncomment the next line:
    // die("Connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

// The $conn variable is now ready to be used for queries in other files.