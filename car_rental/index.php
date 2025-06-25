<?php
// تفعيل عرض جميع الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تسجيل أخطاء SQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'includes/config.php';
include 'includes/header.php';
// معالجة نموذج الحجز
$booking_success = '';
$booking_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['car_id'])) {
    // استقبال البيانات من النموذج
    $car_id = $_POST['car_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'];
    $pickup_date = $_POST['pickup_date'];
    $days = intval($_POST['days']);
    $airport_pickup = isset($_POST['airport_pickup']) ? 1 : 0;
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // التحقق من صحة البيانات
    if (empty($full_name) || empty($phone) || empty($pickup_date) || $days < 1) {
        $booking_error = $translations['booking_error'] . ": " . $translations['required_fields'];
    } else {
        // جلب سعر السيارة من قاعدة البيانات
        try {
            $car_stmt = $conn->prepare("SELECT price FROM cars WHERE id = ?");
            $car_stmt->execute([$car_id]);
            $car = $car_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$car) {
                throw new Exception($translations['booking_error'] . ": " . $translations['car_not_found']);
            }
            
            $car_price = $car['price'];
            $total_price = $car_price * $days;
            
            if ($airport_pickup) {
                $total_price += 2000;
            }
            
            // إدخال الحجز في قاعدة البيانات
            $stmt = $conn->prepare("
                INSERT INTO bookings (
                    car_id, 
                    full_name, 
                    email, 
                    phone, 
                    pickup_date, 
                    days, 
                    total_price, 
                    airport_pickup,
                    user_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $car_id,
                $full_name,
                $email,
                $phone,
                $pickup_date,
                $days,
                $total_price,
                $airport_pickup,
                $user_id
            ]);
            
            $booking_id = $conn->lastInsertId();
            $booking_success = $translations['booking_success'] . $booking_id;
            
        } catch (PDOException $e) {
            $booking_error = $translations['booking_error'] . ": " . $e->getMessage();
        } catch (Exception $e) {
            $booking_error = $e->getMessage();
        }
    }
}

// جلب السيارات من قاعدة البيانات
try {
    $stmt = $conn->query("SELECT * FROM cars WHERE available = 1");
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}

// جلب بيانات المستخدم إذا كان مسجلاً
$user_data = [];
if (isset($_SESSION['user_id'])) {
    try {
        $user_stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
        $user_stmt->execute([$_SESSION['user_id']]);
        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // يمكنك التعامل مع الخطأ إذا لزم الأمر
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعلان مميز - MaramCAR</title>
    <title>اسم موقعك</title>
    <!-- داخل قسم <head> في ملف index.php -->
<link rel="icon" href="assets/images/maramcar.png.jpg" type="image/x-icon">
<link rel="shortcut icon" href="assets/images/maramcar.png.jpg" type="image/x-icon">
<link rel="apple-touch-icon" href="assets/images/maramcar.png.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold: #FFD700;
            --dark: #121212;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .promo-section {
            background: linear-gradient(135deg, rgba(18,18,18,0.95), rgba(30,30,30,0.9)), 
                        url('https://images.unsplash.com/photo-1553440569-bcc63803a83d') center/cover;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
            border-top: 3px solid var(--gold);
            border-bottom: 3px solid var(--gold);
        }
        
        .promo-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.7) 100%);
        }
        
        .promo-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 2px solid var(--gold);
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
        }
        
        .promo-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        
        .promo-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,215,0,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
            z-index: -1;
        }
        
        .promo-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--gold);
            margin-bottom: 20px;
            position: relative;
            text-shadow: 0 0 15px rgba(255,215,0,0.3);
        }
        
        .promo-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100px;
            height: 4px;
            background: var(--gold);
            border-radius: 2px;
        }
        
        .promo-subtitle {
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 30px;
            opacity: 0.9;
            max-width: 700px;
        }
        
        .promo-features {
            margin: 30px 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #fff;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,215,0,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            color: var(--gold);
            font-size: 18px;
        }
        
        .discount-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--gold);
            color: var(--dark);
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 1.2rem;
            transform: rotate(5deg);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
        }
        
        .btn-promo {
            background: var(--gold);
            color: var(--dark);
            font-weight: 700;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.2rem;
            border: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-promo::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: rgba(18,18,18,0.1);
            transition: all 0.5s;
            z-index: -1;
        }
        
        .btn-promo:hover::before {
            width: 100%;
        }
        
        .btn-promo:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .promo-countdown {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            max-width: 500px;
        }
        
        .countdown-title {
            color: #fff;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .countdown-box {
            display: flex;
            gap: 10px;
        }
        
        .countdown-item {
            background: rgba(18,18,18,0.5);
            border: 1px solid var(--gold);
            border-radius: 10px;
            width: 80px;
            height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        
        .countdown-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--gold);
        }
        
        .countdown-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        @keyframes pulse {
            0% { transform: rotate(5deg) scale(1); }
            50% { transform: rotate(5deg) scale(1.05); }
            100% { transform: rotate(5deg) scale(1); }
        }
        
        @media (max-width: 768px) {
            .promo-section {
                padding: 50px 0;
            }
            
            .promo-card {
                padding: 30px 20px;
            }
            
            .promo-title {
                font-size: 2.2rem;
            }
            
            .promo-subtitle {
                font-size: 1.2rem;
            }
            
            .countdown-item {
                width: 65px;
                height: 65px;
            }
            
            .countdown-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- قسم الإعلان -->
    <section class="promo-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="promo-card">
                        <span class="discount-badge">خصم 25%</span>
                        
                        <h1 class="promo-title">عرض خاص لفترة محدودة!</h1>
                        <p class="promo-subtitle">
                            احجز الآن واستمتع بتخفيضات هائلة على جميع سياراتنا الفاخرة. 
                            عروضنا الحصرية تنتهي قريباً، فلا تفوت الفرصة!
                        </p>
                        
                        <div class="promo-features">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-car"></i>
                                </div>
                                <div>تخفيضات تصل إلى 25% على جميع السيارات</div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-gift"></i>
                                </div>
                                <div>توصيل مجاني من وإلى المطار</div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>تأمين مجاني شامل طوال فترة التأجير</div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>خدمة دعم فني متاحة على مدار الساعة</div>
                            </div>
                        </div>
                        
                        <a href="#" class="btn btn-promo">
                            <i class="fas fa-tag me-2"></i> احصل على العرض الآن
                        </a>
                        
                        <div class="promo-countdown">
                            <div class="countdown-title">ينتهي العرض خلال:</div>
                            <div class="countdown-box">
                                <div class="countdown-item">
                                    <div class="countdown-value" id="days">03</div>
                                    <div class="countdown-label">أيام</div>
                                </div>
                                <div class="countdown-item">
                                    <div class="countdown-value" id="hours">12</div>
                                    <div class="countdown-label">ساعات</div>
                                </div>
                                <div class="countdown-item">
                                    <div class="countdown-value" id="minutes">45</div>
                                    <div class="countdown-label">دقائق</div>
                                </div>
                                <div class="countdown-item">
                                    <div class="countdown-value" id="seconds">30</div>
                                    <div class="countdown-label">ثواني</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // كود عداد التوقيت (للتوضيح فقط)
        function updateCountdown() {
            const daysEl = document.getElementById('days');
            const hoursEl = document.getElementById('hours');
            const minutesEl = document.getElementById('minutes');
            const secondsEl = document.getElementById('seconds');
            
            let days = parseInt(daysEl.textContent);
            let hours = parseInt(hoursEl.textContent);
            let minutes = parseInt(minutesEl.textContent);
            let seconds = parseInt(secondsEl.textContent);
            
            seconds--;
            
            if (seconds < 0) {
                seconds = 59;
                minutes--;
                
                if (minutes < 0) {
                    minutes = 59;
                    hours--;
                    
                    if (hours < 0) {
                        hours = 23;
                        days--;
                    }
                }
            }
            
            daysEl.textContent = days.toString().padStart(2, '0');
            hoursEl.textContent = hours.toString().padStart(2, '0');
            minutesEl.textContent = minutes.toString().padStart(2, '0');
            secondsEl.textContent = seconds.toString().padStart(2, '0');
        }
        
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
<!-- قسم الهيرو -->
<section id="home" class="hero vh-100 d-flex align-items-center position-relative">
    <!-- طبقة التغطية -->
    <div class="overlay position-absolute w-100 h-100"></div>
    
    <!-- محتوى البانر -->
    <div class="container text-white position-relative z-index-1">
        <div class="banner-content text-center p-5 rounded-4" style="
            background: rgba(18, 18, 18, 0.8);
            border: 2px solid #FFD700;
            backdrop-filter: blur(8px);
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
            max-width: 800px;
            margin: 0 auto;
        ">
            <!-- العنوان الرئيسي -->
            <div class="brand-title mb-4">
                <h1 class="display-1 fw-bold mb-3 text-uppercase neon-text">MARAMCAR</h1>
                <h2 class="display-4 mb-3 text-uppercase"><?= $translations['car_rental'] ?></h2>
            </div>

            <!-- معلومات الاتصال -->
            <div class="contact-info fs-2 mb-4">
                <a href="tel:+213550123456" class="text-white text-decoration-none glow">
                    <i class="fas fa-phone-alt me-2"></i>+213 550-123-456
                </a>
            </div>
            
            <div class="mt-4">
                <a href="#cars" class="btn btn-lg" style="
                    background: #FFD700;
                    color: #121212;
                    border: none;
                    border-radius: 12px;
                    padding: 12px 30px;
                    font-size: 1.2rem;
                    font-weight: 600;
                    transition: all 0.3s;
                ">
                    <i class="fas fa-car me-2"></i><?= $translations['view_cars'] ?>
                </a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn btn-lg" style="
                        background: rgba(255,255,255,0.1); 
                        color: white;
                        border: 1px solid #FFD700;
                        margin-left: 15px;
                    ">
                        <i class="fas fa-user me-2"></i><?= $translations['profile'] ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .hero {
            background: linear-gradient(45deg, rgba(0,0,0,0.9), rgba(30,30,30,0.95)),
                        url('https://images.unsplash.com/photo-1553440569-bcc63803a83d') center/cover;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .overlay {
            background: rgba(0, 0, 0, 0.7);
        }

        .neon-text {
            color: #FFD700;
            text-shadow: 0 0 10px #FFD700,
                         0 0 20px #FFD700,
                         0 0 30px #FFD700;
            animation: neonGlow 1.5s ease-in-out infinite alternate;
        }

        .glow:hover {
            text-shadow: 0 0 15px #FFFFFF;
            transition: all 0.3s;
        }

        @keyframes neonGlow {
            from { text-shadow: 0 0 10px #FFD700; }
            to { text-shadow: 0 0 20px #FFD700, 0 0 30px #FFD700; }
        }

        @media (max-width: 768px) {
            .brand-title h1 { font-size: 3rem !important; }
            .brand-title h2 { font-size: 1.8rem !important; }
            .contact-info { font-size: 1.3rem !important; }
        }
    </style>
</section>

<!-- معرض السيارات -->
<section id="cars" class="cars-gallery py-5" style="background: #f8f9fa;">
    <div class="container">
        <h2 class="text-center mb-5" style="
            font-size: 2.5rem;
            color: #121212;
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
        ">
            <?= $translations['car_gallery'] ?>
            <span style="
                content: '';
                position: absolute;
                bottom: -10px;
                left: 50%;
                transform: translateX(-50%);
                width: 80px;
                height: 4px;
                background: #FFD700;
                border-radius: 2px;
            "></span>
        </h2>
        
        <?php if($booking_success): ?>
            <div class="alert alert-success text-center">
                <?= $booking_success ?>
            </div>
        <?php endif; ?>
        
        <?php if($booking_error): ?>
            <div class="alert alert-danger text-center">
                <?= $booking_error ?>
            </div>
        <?php endif; ?>
        
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach($cars as $car): ?>
            <div class="col">
                <div class="card h-100 shadow border-0" style="border-radius: 20px; overflow: hidden; border: 1px solid #FFD700;">
                    <img src="uploads/cars/<?= $car['image'] ?>" 
                         class="card-img-top" 
                         alt="<?= $car['model'] ?>"
                         style="height: 250px; object-fit: cover;">
                    <div class="card-body">
                        <h3 class="card-title">
                            <?= isset($car['brand']) ? htmlspecialchars($car['brand']) . ' ' : '' ?>
                            <?= htmlspecialchars($car['model']) ?>
                        </h3>
                        <div class="car-details d-flex justify-content-between mb-3">
                            <span><i class="fas fa-gas-pump me-1"></i> <?= $car['fuel_type'] ?></span>
                            <span><i class="fas fa-users me-1"></i> <?= $car['seats'] ?> <?= $translations['seats'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="h4 text-primary fw-bold"><?= number_format($car['price']) ?> دج/<?= $translations['day'] ?></span>
                            <button class="btn btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#bookingModal"
                                    data-car-id="<?= $car['id'] ?>"
                                    data-car-model="<?= htmlspecialchars($car['model']) ?>"
                                    data-car-price="<?= $car['price'] ?>"
                                    style="background: #FFD700; color: #121212; border: none;">
                                <i class="fas fa-calendar-check"></i> <?= $translations['book_now'] ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- نافذة الحجز العائمة -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #121212; color: #FFD700;">
                <h5 class="modal-title"><?= $translations['book_now'] ?> <span id="selectedCar"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="car_id" id="modalCarId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= $translations['full_name'] ?> <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required
                                value="<?= isset($user_data['name']) ? htmlspecialchars($user_data['name']) : '' ?>"
                                <?= isset($user_data['name']) ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= $translations['email'] ?></label>
                            <input type="email" name="email" class="form-control"
                                value="<?= isset($user_data['email']) ? htmlspecialchars($user_data['email']) : '' ?>"
                                <?= isset($user_data['email']) ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= $translations['phone'] ?> <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" required
                                value="<?= isset($user_data['phone']) ? htmlspecialchars($user_data['phone']) : '' ?>"
                                <?= isset($user_data['phone']) ? 'readonly' : '' ?>>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= $translations['pickup_date'] ?> <span class="text-danger">*</span></label>
                            <input type="date" name="pickup_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= $translations['days'] ?> <span class="text-danger">*</span></label>
                            <input type="number" name="days" class="form-control" min="1" required id="daysInput">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= $translations['airport_pickup_question'] ?> (+2000 دج)</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="airport_pickup" id="airportPickup">
                                <label class="form-check-label" for="airportPickup">
                                    <?= $translations['airport_pickup_question'] ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="agree_terms" required>
                                <label class="form-check-label">
                                    <?= $translations['agree_terms'] ?> <a href="terms.php" target="_blank"><?= $translations['terms'] ?></a>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="alert alert-primary">
                                <h5 class="mb-2"><?= $translations['total_price_preview'] ?>: <span id="totalPricePreview">0</span> دج</h5>
                                <small class="text-muted"><?= $translations['price_confirmation'] ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn w-100 mt-3" style="background: #FFD700; color: #121212;">
                        <i class="fas fa-check-circle me-2"></i> <?= $translations['confirm_booking'] ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- خدمات إضافية -->
<section id="services" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold"><?= $translations['special_services'] ?></h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-plane fa-3x mb-3" style="color: #FFD700;"></i>
                        <h5><?= $translations['airport_pickup'] ?></h5>
                        <p><?= $translations['airport_pickup_desc'] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3" style="color: #FFD700;"></i>
                        <h5><?= $translations['24_7_service'] ?></h5>
                        <p><?= $translations['24_7_service_desc'] ?></p>
                        <div class="service-features">
                            <p><i class="fas fa-check-circle text-success"></i> <?= $translations['whatsapp_support'] ?></p>
                            <p><i class="fas fa-check-circle text-success"></i> <?= $translations['night_pickup'] ?></p>
                            <p><i class="fas fa-check-circle text-success"></i> <?= $translations['emergency_service'] ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-tag fa-3x mb-3" style="color: #FFD700;"></i>
                        <h5><?= $translations['competitive_prices'] ?></h5>
                        <p><?= $translations['competitive_prices_desc'] ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- قسم اتصل بنا -->
<section id="contact" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold"><?= $translations['contact'] ?></h2>
        <div class="row">
            <div class="col-md-6">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-map-marker-alt me-2"></i> <?= $translations['address'] ?></h5>
                        <p class="card-text">شارع الرئيسي، الجزائر العاصمة، الجزائر</p>
                        
                        <h5 class="card-title mt-4"><i class="fas fa-phone me-2"></i> <?= $translations['phone'] ?></h5>
                        <p class="card-text">+213 550-123-456</p>
                        
                        <h5 class="card-title mt-4"><i class="fas fa-envelope me-2"></i> <?= $translations['email'] ?></h5>
                        <p class="card-text">info@maramcar.com</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5 class="card-title mb-4"><?= $translations['send_message'] ?></h5>
                        <form>
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="<?= $translations['full_name'] ?>">
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="<?= $translations['email'] ?>">
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" rows="4" placeholder="<?= $translations['your_message'] ?>"></textarea>
                            </div>
                            <button type="submit" class="btn w-100" style="background: #FFD700; color: #121212;">
                                <?= $translations['submit'] ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// جافاسكريبت لحساب السعر المتوقع
document.addEventListener('DOMContentLoaded', function() {
    const bookingModal = document.getElementById('bookingModal');
    const daysInput = document.getElementById('daysInput');
    const airportPickup = document.getElementById('airportPickup');
    const totalPricePreview = document.getElementById('totalPricePreview');
    let carPrice = 0;
    
    bookingModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const carId = button.getAttribute('data-car-id');
        const carModel = button.getAttribute('data-car-model');
        carPrice = parseFloat(button.getAttribute('data-car-price'));
        
        document.getElementById('selectedCar').textContent = carModel;
        document.getElementById('modalCarId').value = carId;
        calculatePrice();
    });
    
    daysInput.addEventListener('input', calculatePrice);
    airportPickup.addEventListener('change', calculatePrice);
    
    function calculatePrice() {
        const days = parseInt(daysInput.value) || 0;
        const airport = airportPickup.checked;
        let total = carPrice * days;
        
        if (airport) {
            total += 2000;
        }
        
        totalPricePreview.textContent = total.toLocaleString();
    }
});
</script>

<?php include 'includes/footer.php'; ?>