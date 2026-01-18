<?php
require_once __DIR__ . '/../../../auth/db.php';

class SMTPProfile
{
    // Basic encryption key - in production this should be in .env
    private static $encKey = 'VDRAW_SMTP_SECURE_KEY_2025';
    private static $cipher = 'AES-128-ECB';

    private static function encrypt($data)
    {
        return openssl_encrypt($data, self::$cipher, self::$encKey);
    }

    private static function decrypt($data)
    {
        return openssl_decrypt($data, self::$cipher, self::$encKey);
    }

    public static function getAll()
    {
        $stmt = DB::query("SELECT id, profile_name, smtp_host, smtp_port, smtp_username, sender_email, sender_name, encryption, is_default, created_at FROM smtp_profiles ORDER BY created_at DESC");
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public static function get($id)
    {
        $stmt = DB::query("SELECT * FROM smtp_profiles WHERE id = ?", [$id], "i");
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row) {
            $row['smtp_password'] = self::decrypt($row['smtp_password']);
        }
        return $row;
    }

    public static function create($name, $host, $port, $user, $pass, $senderEmail, $senderName, $enc, $isDefault)
    {
        if ($isDefault) {
            DB::query("UPDATE smtp_profiles SET is_default = 0");
        }

        $encPass = self::encrypt($pass);

        $sql = "INSERT INTO smtp_profiles (profile_name, smtp_host, smtp_port, smtp_username, smtp_password, sender_email, sender_name, encryption, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return DB::query($sql, [$name, $host, $port, $user, $encPass, $senderEmail, $senderName, $enc, $isDefault], "ssisssssi");
    }

    public static function update($id, $name, $host, $port, $user, $pass, $senderEmail, $senderName, $enc, $isDefault)
    {
        if ($isDefault) {
            DB::query("UPDATE smtp_profiles SET is_default = 0");
        }

        if (!empty($pass)) {
            $encPass = self::encrypt($pass);
            $sql = "UPDATE smtp_profiles SET profile_name=?, smtp_host=?, smtp_port=?, smtp_username=?, smtp_password=?, sender_email=?, sender_name=?, encryption=?, is_default=? WHERE id=?";
            return DB::query($sql, [$name, $host, $port, $user, $encPass, $senderEmail, $senderName, $enc, $isDefault, $id], "ssisssssii");
        } else {
            $sql = "UPDATE smtp_profiles SET profile_name=?, smtp_host=?, smtp_port=?, smtp_username=?, sender_email=?, sender_name=?, encryption=?, is_default=? WHERE id=?";
            return DB::query($sql, [$name, $host, $port, $user, $senderEmail, $senderName, $enc, $isDefault, $id], "ssissssii");
        }
    }

    public static function delete($id)
    {
        return DB::query("DELETE FROM smtp_profiles WHERE id = ?", [$id], "i");
    }

    public static function getDefault()
    {
        $stmt = DB::query("SELECT * FROM smtp_profiles WHERE is_default = 1 LIMIT 1");
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row) {
            $row['smtp_password'] = self::decrypt($row['smtp_password']);
        }
        return $row;
    }
}
