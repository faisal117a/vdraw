<?php
require_once __DIR__ . '/../../../auth/db.php';

class EmailTemplate
{
    public static function getAll()
    {
        $stmt = DB::query("SELECT * FROM email_templates ORDER BY created_at DESC");
        if (!$stmt) return [];
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public static function get($id)
    {
        $stmt = DB::query("SELECT * FROM email_templates WHERE tid = ?", [$id], "i");
        if (!$stmt) return null;
        $res = $stmt->get_result();
        return $res->fetch_assoc();
    }

    public static function create($title, $text, $html, $isActive)
    {
        $sql = "INSERT INTO email_templates (template_title, text_body, html_body, template_version, is_active) VALUES (?, ?, ?, 1, ?)";
        return DB::query($sql, [$title, $text, $html, $isActive], "sssi");
    }

    public static function update($id, $title, $text, $html, $isActive, $incrementVersion = false)
    {
        if ($incrementVersion) {
            $sql = "UPDATE email_templates SET template_title=?, text_body=?, html_body=?, is_active=?, template_version=template_version+1 WHERE tid=?";
        } else {
            $sql = "UPDATE email_templates SET template_title=?, text_body=?, html_body=?, is_active=? WHERE tid=?";
        }
        return DB::query($sql, [$title, $text, $html, $isActive, $id], "sssii");
    }

    public static function delete($id)
    {
        return DB::query("DELETE FROM email_templates WHERE tid = ?", [$id], "i");
    }

    public static function getActiveVariables()
    {
        return [
            '{name}' => 'User First Name',
            '{email}' => 'User Email'
        ];
    }
}
