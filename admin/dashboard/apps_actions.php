<?php
require_once '../../auth/Auth.php';
require_once '../../auth/db.php';

if (!Auth::isLoggedIn() || $_SESSION['role'] !== 'admin') {
    die("Access Denied");
}

$action = $_POST['action'] ?? '';
$conn = DB::connect();

if ($action === 'save_app') {
    $id = $_POST['app_id'] ?? null;
    $name = trim($_POST['name']); // This is the folder name / slug
    $nav_title = trim($_POST['nav_title']);
    $home_title = trim($_POST['home_title']);
    $home_description = trim($_POST['home_description']);
    $icon_class = trim($_POST['icon_class']);
    $theme_color = $_POST['theme_color'] ?? 'blue';
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($name) || empty($nav_title) || empty($home_title)) {
        header("Location: index.php?msg=Error: Missing required fields&tab=apps");
        exit;
    }

    if ($id) {
        // Update
        $sql = "UPDATE apps SET name=?, nav_title=?, home_title=?, home_description=?, icon_class=?, theme_color=?, display_order=?, is_active=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssisi", $name, $nav_title, $home_title, $home_description, $icon_class, $theme_color, $display_order, $is_active, $id);
        
        if ($stmt->execute()) {
            $msg = "App updated successfully";
        } else {
            $msg = "Error updating app: " . $conn->error;
        }
    } else {
        // Insert
        // Check duplication
        $check = $conn->query("SELECT id FROM apps WHERE name = '$name'");
        if ($check->num_rows > 0) {
            header("Location: index.php?msg=Error: App with this name already exists&tab=apps");
            exit;
        }

        $sql = "INSERT INTO apps (name, nav_title, home_title, home_description, icon_class, theme_color, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssis", $name, $nav_title, $home_title, $home_description, $icon_class, $theme_color, $display_order, $is_active);
        
        if ($stmt->execute()) {
            $msg = "App added successfully";
        } else {
            $msg = "Error adding app: " . $conn->error;
        }
    }
    
    header("Location: index.php?msg=$msg&tab=apps");
    exit;
}

if ($action === 'delete_app') {
    $id = (int)$_POST['app_id'];
    if ($id) {
        $conn->query("DELETE FROM apps WHERE id=$id");
        header("Location: index.php?msg=App deleted successfully&tab=apps");
    } else {
        header("Location: index.php?msg=Error: Invalid ID&tab=apps");
    }
    exit;
}

header("Location: index.php");
exit;
?>
