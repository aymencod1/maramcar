<?php
require __DIR__ . '/includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    try {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // إنشاء رمز استعادة
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $conn->prepare("
                INSERT INTO password_resets (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $token, $expires]);
            
            // إرسال البريد الإلكتروني
            $reset_link = "http://yourdomain.com/reset_password.php?token=$token";
            $to = $email;
            $subject = "استعادة كلمة المرور - نظام تأجير السيارات";
            $message = "
                <html>
                <body>
                    <h2>مرحباً {$user['name']}</h2>
                    <p>لقد تلقينا طلباً لاستعادة كلمة المرور الخاصة بحسابك.</p>
                    <p>اضغط على الرابط التالي لتعيين كلمة مرور جديدة:</p>
                    <a href='$reset_link'>$reset_link</a>
                    <p>إذا لم تطلب هذا الرجاء تجاهل هذه الرسالة.</p>
                    <p>مع تحيات,<br>إدارة نظام تأجير السيارات</p>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: no-reply@yourdomain.com\r\n";
            
            if (mail($to, $subject, $message, $headers)) {
                $success = "تم إرسال رابط استعادة كلمة المرور إلى بريدك الإلكتروني";
            } else {
                $error = "حدث خطأ أثناء إرسال البريد الإلكتروني";
            }
        } else {
            $error = "البريد الإلكتروني غير مسجل";
        }
    } catch (PDOException $e) {
        $error = "حدث خطأ في النظام: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>استعادة كلمة المرور</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-key"></i> استعادة كلمة المرور</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-paper-plane me-2"></i> إرسال رابط الاستعادة
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-right me-1"></i> العودة لتسجيل الدخول
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>