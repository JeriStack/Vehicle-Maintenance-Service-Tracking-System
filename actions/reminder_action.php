<?php
// actions/reminder_action.php
header("Location: " . dirname(dirname($_SERVER['PHP_SELF'])) . "/actions/notification_action.php?" . $_SERVER['QUERY_STRING']);
exit;
