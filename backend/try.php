<?php
$host = "localhost";   // safer than localhost on Windows
$user = "roott";        // change if you created custom user
$pass = "";            // change if you set password
$port = 3307;          // YOUR port
$db   = "cinematic_lens_db"; // optional (can test with or without)

// ---------- TEST 1: Connect to MySQL (no DB) ----------
$conn = new mysqli($host, $user, $pass, "", $port);

if ($conn->connect_error) {
    die("❌ MySQL Connection Failed: " . $conn->connect_error);
} else {
    echo "✅ Connected to MySQL on port $port<br>";
}

// ---------- TEST 2: Check database ----------
if ($conn->select_db($db)) {
    echo "✅ Database '$db' exists and selected<br>";
} else {
    echo "⚠️ Database '$db' not found<br>";
}

// ---------- TEST 3: Try simple query ----------
$result = $conn->query("SHOW TABLES");

if ($result) {
    echo "✅ Query working. Tables:<br>";
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "<br>";
    }
} else {
    echo "❌ Query failed: " . $conn->error;
}

$conn->close();
?>