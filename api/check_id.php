<?php
require_once __DIR__ . '/../includes/db.php';
if (isset($_POST['national_id'])) {
    $id = $conn->real_escape_string($_POST['national_id']);
    $r = $conn->query("SELECT id FROM patients WHERE national_id='$id'");
    echo ($r && $r->num_rows > 0) ? "found" : "not_found";
}