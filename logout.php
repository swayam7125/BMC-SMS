<?php
// Set the expiration date to one hour in the past for all cookies to clear them.
setcookie("encrypted_user_id", "", time() - 3600, "/");
setcookie("encrypted_user_role", "", time() - 3600, "/");
setcookie("encrypted_profile_image", "", time() - 3600, "/");
setcookie("encrypted_user_name", "", time() - 3600, "/");

// Redirect to the login page
header("Location: login.php");
exit(); // It's good practice to call exit() after a header redirect.
?>
