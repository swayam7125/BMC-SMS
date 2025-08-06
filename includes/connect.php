<?php
// Database credentials for your Supabase project
$host = 'aws-0-ap-south-1.pooler.supabase.com'; // ✅ Correct host from your screenshot
$port = '5432';                                 // Usually 5432 for Supabase
$dbname = 'postgres';                           // Usually 'postgres' for Supabase
$user = 'postgres.fwkvbvmmfwyjpqjileil';         // ✅ Correct user from your screenshot
$password = '0407111726';  // 👈 IMPORTANT: Add your password here

// Create a PostgreSQL connection string (DSN)
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";

try {
    // Create a PDO instance
    $conn = new PDO($dsn);

    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // You can uncomment the line below to test if the connection is successful
    // echo "Connected to Supabase Successfully!";

} catch (PDOException $e) {
    // If connection fails, stop the script and show the error
    die("Connection failed: " . $e->getMessage());
}
?>
