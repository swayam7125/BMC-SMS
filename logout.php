<?php
// Remove cookies
setcookie("encrypted_user_id", "", time() - 3600, "/");
setcookie("encrypted_user_role", "", time() - 3600, "/");

header("Location: login.php");
exit;
