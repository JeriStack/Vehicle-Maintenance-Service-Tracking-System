<?php
// reminders/mark_read.php
header("Location: " . dirname(dirname($_SERVER['PHP_SELF'])) . "/notifications/mark_read.php?id=" . urlencode($_GET['id'] ?? 'all'));
exit;
