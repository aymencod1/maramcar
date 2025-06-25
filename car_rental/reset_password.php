<?php
require __DIR__ . '/includes/config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// التحقق من الرمز
try {
    $stmt = $conn->prepare("
        SELECT user_id FROM password_resets 
        WHERE token = ? AND expires_at > NOW() AND used = 0
    ");
    $stmt->execute([$token]);
    $reset_request = $stmt->fetch();
    
    if (!$reset_request) {
        $error = "رابط الاستعادة غير صالح أو منتهي الصلاحية";
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {
            $error = "كلمتا المرور غير متطابقتين";
        } else {
            // تحديث كلمة المرور
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $reset_request['user_id']]);
            
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $conn->commit();
            $success = "تم تحديث كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.";
        }
    }
} catch (PDOException $e) {
    $conn->rollBack();
    $error = "حدث خطأ في النظام: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعيين كلمة مرور جديدة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-key"></i> تعيين كلمة مرور جديدة</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                            <div class="text-center">
                                <a href="forgot_password.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-right me-1"></i> طلب رابط استعادة جديد
                                </a>
                            </div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-1"></i> تسجيل الدخول
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password" class="form-label">كلمة المرور الجديدة</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i> حفظ كلمة المرور الجديدة
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>