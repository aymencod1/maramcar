<?php
session_start();
include 'includes/config.php';

$errors = [];
$success = '';

// معالجة نموذج التسجيل
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // التحقق من البيانات
    if (empty($name)) {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    
    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'صيغة البريد الإلكتروني غير صالحة';
    }
    
    if (empty($phone)) {
        $errors[] = 'رقم الهاتف مطلوب';
    }
    
    if (empty($password)) {
        $errors[] = 'كلمة المرور مطلوبة';
    } elseif (strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'كلمتا المرور غير متطابقتين';
    }
    
    // التحقق من عدم وجود البريد الإلكتروني مسبقاً
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'البريد الإلكتروني مستخدم مسبقاً';
            } else {
                // تسجيل المستخدم في قاعدة البيانات
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $created_at = date('Y-m-d H:i:s');
                
                $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $hashed_password, $created_at]);
                
                // تسجيل دخول المستخدم تلقائياً
                $user_id = $conn->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // توجيه إلى الصفحة الشخصية
                header('Location: profile.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'خطأ في التسجيل: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد - MaramCAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --light: #f8f9fa;
            --dark: #212529;
            --gradient-start: #4e54c8;
            --gradient-end: #8f94fb;
        }
        
        body {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
            font-family: 'Tajawal', sans-serif;
            color: #333;
        }
        
        .register-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .register-card {
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .card-header {
            background: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
            color: white;
            text-align: center;
            padding: 30px 20px;
            border-bottom: none;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
        }
        
        .card-header h2 {
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .card-header p {
            opacity: 0.9;
            margin-bottom: 0;
            position: relative;
            z-index: 1;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
            display: flex;
            align-items: center;
        }
        
        .form-label i {
            margin-left: 8px;
            color: var(--primary);
        }
        
        .form-control {
            border-radius: 12px;
            padding: 14px 20px;
            border: 2px solid #e1e5eb;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
            border: none;
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 18px;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(78, 84, 200, 0.3);
        }
        
        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .logo-icon i {
            font-size: 28px;
            color: var(--gradient-start);
        }
        
        .logo-text {
            font-weight: 800;
            font-size: 32px;
            color: white;
            letter-spacing: -0.5px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        
        .feature-item {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 20px;
        }
        
        .feature-item h5 {
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--gradient-start);
        }
        
        .password-toggle {
            position: absolute;
            left: 15px;
            top: 42px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 16px;
        }
        
        .login-link a {
            color: var(--gradient-start);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            text-decoration: underline;
            color: var(--gradient-end);
        }
        
        @media (max-width: 768px) {
            .card-body {
                padding: 30px 20px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .logo-text {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="card-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="logo-text">MaramCAR</div>
                </div>
                <h2>تسجيل حساب جديد</h2>
                <p>أنشئ حسابك للوصول إلى جميع خدماتنا</p>
            </div>
            
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>خطأ:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $success ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="name">
                            <i class="fas fa-user"></i> الاسم الكامل
                        </label>
                        <input type="text" class="form-control" id="name" name="name" required
                               placeholder="أدخل اسمك الكامل">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope"></i> البريد الإلكتروني
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required
                               placeholder="example@domain.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phone">
                            <i class="fas fa-phone"></i> رقم الهاتف
                        </label>
                        <input type="tel" class="form-control" id="phone" name="phone" required
                               placeholder="05XXXXXXXX">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fas fa-lock"></i> كلمة المرور
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="6 أحرف على الأقل">
                        <span class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            <i class="fas fa-lock"></i> تأكيد كلمة المرور
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                               placeholder="أعد إدخال كلمة المرور">
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            أوافق على <a href="#" class="text-primary">الشروط والأحكام</a> و <a href="#" class="text-primary">سياسة الخصوصية</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i> إنشاء حساب
                    </button>
                </form>
                
                <div class="login-link">
                    <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول الآن</a></p>
                </div>
                
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5>حماية وأمان</h5>
                        <p class="mb-0">بياناتك محمية بتقنيات التشفير المتقدمة</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <h5>خدمة مميزة</h5>
                        <p class="mb-0">أفضل العروض وأحدث السيارات</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <h5>خصومات حصرية</h5>
                        <p class="mb-0">احصل على خصومات تصل إلى 20% للحسابات المسجلة</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h5>دعم فني 24/7</h5>
                        <p class="mb-0">فريق دعم فني متاح على مدار الساعة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // وظيفة إظهار/إخفاء كلمة المرور
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // التحقق من تطابق كلمتي المرور أثناء الكتابة
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('كلمتا المرور غير متطابقتين');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // إرسال النموذج
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('كلمتا المرور غير متطابقتين. الرجاء التأكد من تطابقهما.');
            }
        });
    </script>
</body>
</html>