<?php

class Database {
    private static $host = "localhost";
    private static $db_name = "cinematic_lens_db";
    private static $username = "root";
    private static $password = "";
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                // First connect WITHOUT the database name to create it if it doesn't exist
                $tempConn = new PDO("mysql:host=" . self::$host . ";charset=utf8", self::$username, self::$password);
                $tempConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $tempConn->exec("CREATE DATABASE IF NOT EXISTS " . self::$db_name);
                
                // Now connect TO the specific database
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8",
                    self::$username,
                    self::$password
                );
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
            } catch(PDOException $exception) {
                error_log("Database Connection Error: " . $exception->getMessage());
                die("Database Connection Error: " . $exception->getMessage());
            }
        }
        return self::$conn;
    }
}
?>
