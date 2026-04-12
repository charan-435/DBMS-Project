
<?php
$host = "localhost";
$user = "root";
$pass = "";

// Connect WITHOUT database first
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$db = "cinematic_lens_db";

$sql = "CREATE DATABASE IF NOT EXISTS $db";

if ($conn->query($sql) === TRUE) {
    echo "✅ Database created or already exists<br>";
} else {
    die("❌ Error creating database: " . $conn->error);
}

// Now connect to that DB
$conn->select_db($db);
?>
<?php


$host = "localhost";
$user = "root";
$pass = "";
$db = "cinematic_lens_db";

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CSV file path
$file = "../../data/add_revenue.csv";

if (($handle = fopen($file, "r")) !== FALSE) {

    // Read first row (column names)
    $columns = fgetcsv($handle);

    // Create table dynamically
    $table = "movies";

    $col_sql = "";
    $primaryKey = "";

    foreach ($columns as $index => $col) {
        $col = preg_replace('/[^a-zA-Z0-9_]/', '_', $col);

        if ($index == 0) {
            // First column as PRIMARY KEY
            $col_sql .= "`$col` VARCHAR(255),";
            $primaryKey = "PRIMARY KEY (`$col`)";
        } else {
            $col_sql .= "`$col` TEXT,";
        }
    }

    $col_sql .= $primaryKey;

    // Insert data
    while (($row = fgetcsv($handle)) !== FALSE) {

        $values = array_map(function ($val) use ($conn) {
            return "'" . $conn->real_escape_string($val) . "'";
        }, $row);

        $values = implode(",", $values);

        $insert = "INSERT IGNORE INTO $table VALUES ($values)";
        $conn->query($insert);
    }

    fclose($handle);
    echo "✅ Table created and data inserted successfully!";
} else {
    echo "❌ Failed to open CSV file.";
}

$conn->close();

?>