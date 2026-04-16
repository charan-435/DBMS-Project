<?php
$host = "localhost";
$user = "roott";
$pass = "";
$port = "3307";

// Connect WITHOUT database first
#$conn = new mysqli($host, $user, $pass);
$conn = new mysqli($host, $user, $pass, "", $port);
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
$user = "roott";
$pass = "";
$db = "cinematic_lens_db";

// Connect to MySQL
#$conn = new mysqli($host, $user, $pass, $db);
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CSV file path
$file = "./data/add_revenue.csv";

if (($handle = fopen($file, "r")) !== FALSE) {

    // Read first row (column names)
    $columns = fgetcsv($handle);

    // Create table dynamically
    $table = "movies";

    $col_sql = "";
    $primaryKey = "";

    foreach ($columns as $index => $col) {
        $col = preg_replace('/[^a-zA-Z0-9_]/', '_', $col);

        // Define types
        switch ($col) {
            case 'tmdb_id':
                $type = "BIGINT";
                break;
            case 'imdb_id':
                $type = "VARCHAR(20)";
                break;
            case 'release_date':
                $type = "DATE";
                break;
            case 'rating_tmdb':
            case 'rating_imdb':
                $type = "FLOAT";
                break;
            case 'votes_imdb':
            case 'runtimeMinutes':
                $type = "INT";
                break;
            case 'revenue':
                $type = "BIGINT";
                break;
            case 'language':
            case 'genres':
                $type = "VARCHAR(255)";
                break;
            case 'director':
                $type = "VARCHAR(255)";
                break;
            default:
                $type = "TEXT";
        }

        if ($index == 0) {
            $col_sql .= "`$col` $type,";
            $primaryKey = "PRIMARY KEY (`$col`)";
        } else {
            $col_sql .= "`$col` $type,";
        }
    }

    $col_sql .= $primaryKey;

    
    $createTable = "CREATE TABLE IF NOT EXISTS $table ($col_sql)";

    if (!$conn->query($createTable)) {
        die("❌ Table creation failed: " . $conn->error);
    }

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