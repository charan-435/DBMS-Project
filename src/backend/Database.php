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
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8",
                    self::$username,
                    self::$password
                );
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                // To avoid breaking the UI completely if the DB is not yet set up,
                // we'll suppress the generic death and just return null.
                error_log("Database Connection Error: " . $exception->getMessage());
                return null;
            }
        }
        return self::$conn;
    }
}
?>
