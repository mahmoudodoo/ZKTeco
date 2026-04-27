<?php
$conn = new mysqli("localhost", "root", "", "semah_db");
if ($conn->connect_error) { die("DB Error: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");