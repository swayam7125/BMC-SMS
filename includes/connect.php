<?php
$conn = mysqli_connect("localhost", "root", "", "bmc");
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
// echo "Connected Successfully!";
?>