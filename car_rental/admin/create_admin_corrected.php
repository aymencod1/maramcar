<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/database.php';

$database = new Database();
$conn = $database->connect();

$admin_email = "aymen2020ps4@gmail.com";
$admin_password = "babiho2001";

try {
    // حذف الحساب القديم إن وجد
    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    
    // إنشاء حساب مدير جديد
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 1)");
    $stmt->execute([
        'Aymen Admin',
        $admin_email,
        password_hash($admin_password, PASSWORD_DEFAULT)
    ]);
    
    echo "✅ تم إنشاء حساب المدير بنجاح!";
} catch(PDOException $e) {
    die("❌ خطأ: " . $e->getMessage());
}
?>