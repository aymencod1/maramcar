<?php
session_start();
require __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // تحديد إذا كان المدير الرئيسي
            if ($user['email'] === 'aymen2020ps4@gmail.com') {
                $_SESSION['is_admin'] = 1;
                header("Location: admin/dashboard.php");
            } else {
                $_SESSION['is_admin'] = 0;
                header("Location: profile.php");
            }
            exit();
        } else {
            $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
        }
    } catch(PDOException $e) {
        $error = "خطأ في النظام: " . $e->getMessage();
    }
}

// بعد هذا السطر فقط يمكن بدء إخراج HTML
include 'includes/header.php'; 
?>

<!-- بقية كود HTML -->

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">تسجيل الدخول</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($email) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">تذكرني</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
                        
                        <div class="text-center mt-3">
                            <a href="forgot_password.php">نسيت كلمة المرور؟</a>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p>ليس لديك حساب؟ <a href="register.php">سجل حساب جديد</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>