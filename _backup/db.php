<?php
// PyViz/auth/db.php

class DB {
    private static $conn = null;

    public static function connect() {
        if (self::$conn === null) {
            // Using standard local credits as per setup
            $host = 'localhost';
            $user = 'vdrawcc_pyviz_db7';
            $pass = 'Mann$9090@A9'; 
            $dbname = 'vdrawcc_pyviz_db7';

            self::$conn = new mysqli($host, $user, $pass, $dbname);

            if (self::$conn->connect_error) {
                die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
            }
            self::$conn->set_charset("utf8mb4");
        }
        return self::$conn;
    }

    public static function query($sql, $params = [], $types = '') {
        $conn = self::connect();
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        if (!empty($params)) {
             if (empty($types)) {
                $types = str_repeat('s', count($params)); // Default to string
             }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt;
    }
}


