<?php
// settings/profile.php
// Redirecting to unified settings module
header("Location: " . dirname(dirname($_SERVER['PHP_SELF'])) . "/settings/index.php#v-pills-profile");
exit;
