<?php
require_once __DIR__ . '/../../../auth/db.php';

class EmailBatch
{
    public static function getAll()
    {
        $stmt = DB::query("SELECT * FROM email_batches ORDER BY created_at DESC");
        if (!$stmt) return [];
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function get($id)
    {
        $stmt = DB::query("SELECT * FROM email_batches WHERE bid = ?", [$id], "i");
        if (!$stmt) return null;
        return $stmt->get_result()->fetch_assoc();
    }

    public static function create($title, $desc, $sourceList)
    {
        $sql = "INSERT INTO email_batches (title, description, email_list_source) VALUES (?, ?, ?)";
        return DB::query($sql, [$title, $desc, $sourceList], "sss");
    }

    public static function update($id, $title, $desc, $sourceList)
    {
        $sql = "UPDATE email_batches SET title=?, description=?, email_list_source=? WHERE bid=?";
        return DB::query($sql, [$title, $desc, $sourceList, $id], "sssi");
    }

    public static function delete($id)
    {
        return DB::query("DELETE FROM email_batches WHERE bid = ?", [$id], "i");
    }

    public static function getFilteredUsers($role, $dateFrom, $dateTo, $search, $activeOnly, $verifiedOnly, $offset = 0, $limit = 100)
    {
        $sql = "SELECT id, email, full_name, role, created_at FROM users WHERE 1=1";
        $params = [];
        $types = "";

        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
            $types .= "s";
        }
        if ($dateFrom) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $dateFrom;
            $types .= "s";
        }
        if ($dateTo) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }
        if ($search) {
            $sql .= " AND email LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        if ($verifiedOnly) {
            $sql .= " AND email_verified_at IS NOT NULL";
        }

        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = DB::query($sql, $params, $types);
        if (!$stmt) return [];

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countFilteredUsers($role, $dateFrom, $dateTo, $search, $activeOnly, $verifiedOnly)
    {
        $sql = "SELECT COUNT(*) as c FROM users WHERE 1=1";
        $params = [];
        $types = "";

        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
            $types .= "s";
        }
        if ($dateFrom) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $dateFrom;
            $types .= "s";
        }
        if ($dateTo) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }
        if ($search) {
            $sql .= " AND email LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        if ($verifiedOnly) {
            $sql .= " AND email_verified_at IS NOT NULL";
        }

        $stmt = DB::query($sql, $params, $types);
        if ($stmt) {
            return $stmt->get_result()->fetch_assoc()['c'];
        }
        return 0;
    }

    public static function createSnapshot($batchId)
    {
        $batch = self::get($batchId);
        if (!$batch) return false;

        $source = $batch['email_list_source'];
        // Split by comma, trim, filter
        $emails = [];
        $crude = explode(',', $source);
        foreach ($crude as $e) {
            $e = trim($e);
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $e;
            }
        }
        $emails = array_unique($emails);

        if (empty($emails)) return 0;

        $conn = DB::connect();

        // Fetch existing to avoid duplicates
        $existing = [];
        $res = DB::query("SELECT email FROM email_batch_items WHERE batch_id = ?", [$batchId], "i")->get_result();
        while ($r = $res->fetch_assoc()) {
            $existing[strtolower($r['email'])] = true;
        }

        $added = 0;
        $sql = "INSERT INTO email_batch_items (batch_id, email, status) VALUES (?, ?, 'pending')";
        $stmt = $conn->prepare($sql);

        foreach ($emails as $email) {
            if (!isset($existing[strtolower($email)])) {
                $stmt->bind_param("is", $batchId, $email);
                $stmt->execute();
                $added++;
            }
        }

        return $added;
    }

    public static function getSnapshotStats($batchId)
    {
        $res = DB::query("SELECT status, COUNT(*) as c FROM email_batch_items WHERE batch_id = ? GROUP BY status", [$batchId], "i")->get_result();
        $stats = ['pending' => 0, 'sent' => 0, 'failed' => 0, 'total' => 0];
        while ($r = $res->fetch_assoc()) {
            $stats[$r['status']] = (int)$r['c'];
            $stats['total'] += (int)$r['c'];
        }
        return $stats;
    }
}
