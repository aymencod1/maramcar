<?php
session_start(); // بدء جلسة المستخدم

require 'includes/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// معالجة نموذج الحجز الجديد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = $_POST['car_id'];
    $days = intval($_POST['days']);
    $pickup_date = $_POST['pickup_date'];
    $airport_pickup = isset($_POST['airport_pickup']) ? 1 : 0;

    try {
        // جلب سعر السيارة
        $stmt = $conn->prepare("SELECT price FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        $car = $stmt->fetch();
        
        if (!$car) {
            throw new Exception("السيارة المحددة غير موجودة");
        }
        
        $total_price = ($car['price'] * $days) + ($airport_pickup ? 2000 : 0);
        
        // إدخال الحجز الجديد
        $stmt = $conn->prepare("
            INSERT INTO bookings (
                car_id, user_id, pickup_date, days, 
                airport_pickup, total_price
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $car_id,
            $userId,
            $pickup_date,
            $days,
            $airport_pickup,
            $total_price
        ]);
        
        $_SESSION['success'] = "تم الحجز بنجاح! سيتم التواصل معك للتأكيد";
        header("Location: bookings.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: bookings.php");
        exit();
    }
}

// جلب بيانات الحجوزات باستخدام استعلام معدل
try {
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY pickup_date DESC");
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب الحجوزات: " . $e->getMessage());
}

// جلب بيانات السيارات المتاحة للحجز
try {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE available = 1");
    $stmt->execute();
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب السيارات: " . $e->getMessage());
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجوزاتي - MaramCAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --light: #f8f9fa;
            --dark: #121212;
            --gold: #FFD700;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Tajawal', sans-serif;
            color: #333;
            padding-top: 20px;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(to right, #121212, #2c2c2c);
            color: #FFD700;
            border-top-left-radius: 15px !important;
            border-top-right-radius: 15px !important;
            font-weight: 700;
            padding: 15px 20px;
        }
        
        .table th {
            background: rgba(255, 215, 0, 0.1);
            color: #121212;
            font-weight: 700;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .btn-gold {
            background: #FFD700;
            color: #121212;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .btn-gold:hover {
            background: #e6c200;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }
        
        .btn-outline-gold {
            background: transparent;
            color: #121212;
            border: 2px solid #FFD700;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .btn-outline-gold:hover {
            background: #FFD700;
            transform: translateY(-3px);
        }
        
        .car-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-left: 10px;
        }
        
        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
            padding-bottom: 10px;
            font-weight: 700;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: #FFD700;
            border-radius: 2px;
        }
        
        .featured-car {
            border: 2px solid #FFD700;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .featured-car:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .price-preview {
            background: rgba(255, 215, 0, 0.1);
            border-radius: 12px;
            padding: 15px;
            font-weight: 700;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            background-color: #FFD700;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- رسائل التنبيه -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="text-center mb-5">
            <h2 class="section-title">حجوزاتي</h2>
            <p class="text-muted">إدارة حجوزات السيارات الخاصة بك</p>
        </div>
        
        <div class="row">
            <!-- حجوزاتي -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-history me-2"></i> الحجوزات السابقة</span>
                        <span class="badge bg-light text-dark"><?= count($bookings) ?> حجز</span>
                    </div>
                    <div class="card-body">
                        <?php if(count($bookings) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>السيارة</th>
                                            <th>التواريخ</th>
                                            <th>المدة</th>
                                            <th>المبلغ</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($bookings as $booking): 
                                            $start = new DateTime($booking['pickup_date']);
                                            $end = new DateTime($booking['pickup_date']);
                                            $end->modify('+' . $booking['days'] . ' days');
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <div class="fw-bold"><?= $booking['car_id'] ?> (ID)</div>
                                                        <small class="text-muted">حجز #<?= $booking['id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?= $start->format('Y/m/d') ?></div>
                                                <div class="text-muted small">إلى <?= $end->format('Y/m/d') ?></div>
                                            </td>
                                            <td><?= $booking['days'] ?> يوم</td>
                                            <td><?= number_format($booking['total_price'], 2) ?> دج</td>
                                            <td>
                                                <span class="status-badge" style="
                                                    background: <?= 
                                                        $booking['status'] === 'confirmed' ? 'rgba(25, 135, 84, 0.1)' : 
                                                        ($booking['status'] === 'pending' ? 'rgba(255, 193, 7, 0.1)' : 'rgba(108, 117, 125, 0.1)') 
                                                    ?>;
                                                    color: <?= 
                                                        $booking['status'] === 'confirmed' ? '#198754' : 
                                                        ($booking['status'] === 'pending' ? '#ffc107' : '#6c757d') 
                                                    ?>;
                                                ">
                                                    <?= $booking['status'] === 'confirmed' ? 'مؤكد' : 
                                                       ($booking['status'] === 'pending' ? 'قيد الانتظار' : 'ملغى') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="booking_details.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-outline-gold">
                                                    التفاصيل
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-calendar-times fa-4x" style="color: rgba(255, 215, 0, 0.2);"></i>
                                </div>
                                <h3 class="mb-3">لا توجد حجوزات سابقة</h3>
                                <p class="text-muted mb-4">لم تقم بحجز أي سيارة بعد. يمكنك حجز سيارة جديدة الآن</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- إضافة حجز جديد -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-plus-circle me-2"></i> حجز سيارة جديدة
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">اختر السيارة</label>
                                <select name="car_id" class="form-select" required id="carSelect">
                                    <option value="">اختر سيارة</option>
                                    <?php foreach($cars as $car): ?>
                                    <option value="<?= $car['id'] ?>" data-price="<?= $car['price'] ?>">
                                        <?= $car['brand'] ?> <?= $car['model'] ?> - <?= number_format($car['price']) ?> دج/يوم
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">تاريخ الاستلام</label>
                                <input type="date" name="pickup_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">عدد الأيام</label>
                                <input type="number" name="days" class="form-control" min="1" required id="daysInput" value="1">
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="airport_pickup" id="airportPickup">
                                    <label class="form-check-label" for="airportPickup">
                                        استلام من المطار (+2000 دج)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="price-preview">
                                    <h5 class="mb-1">السعر المتوقع: <span id="totalPricePreview">0</span> دج</h5>
                                    <small class="text-muted">سيتم تأكيد السعر النهائي قبل الحجز</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-gold w-100">
                                <i class="fas fa-check-circle me-2"></i> تأكيد الحجز
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- سيارة مميزة -->
                <?php if(count($cars) > 0): 
                    $featuredCar = $cars[array_rand($cars)];
                ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-star me-2"></i> سيارة مميزة
                    </div>
                    <div class="card-body">
                        <div class="featured-car">
                            <div class="p-3">
                                <h5 class="mb-1"><?= $featuredCar['brand'] ?> <?= $featuredCar['model'] ?></h5>
                                <div class="d-flex justify-content-between text-muted small mb-2">
                                    <span><i class="fas fa-gas-pump me-1"></i> <?= $featuredCar['fuel_type'] ?></span>
                                    <span><i class="fas fa-users me-1"></i> <?= $featuredCar['seats'] ?> مقاعد</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-primary fw-bold"><?= number_format($featuredCar['price']) ?> دج/يوم</span>
                                    <button class="btn btn-sm btn-outline-gold" 
                                            onclick="document.getElementById('carSelect').value='<?= $featuredCar['id'] ?>'; updatePrice();">
                                        <i class="fas fa-calendar-check"></i> احجز الآن
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // جافاسكريبت لحساب السعر المتوقع
        document.addEventListener('DOMContentLoaded', function() {
            const carSelect = document.getElementById('carSelect');
            const daysInput = document.getElementById('daysInput');
            const airportPickup = document.getElementById('airportPickup');
            const totalPricePreview = document.getElementById('totalPricePreview');
            
            // تحديث السعر عند تغيير المدخلات
            carSelect.addEventListener('change', updatePrice);
            daysInput.addEventListener('input', updatePrice);
            airportPickup.addEventListener('change', updatePrice);
            
            // تحديث السعر عند التحميل الأولي
            updatePrice();
            
            function updatePrice() {
                const selectedOption = carSelect.options[carSelect.selectedIndex];
                const carPrice = selectedOption ? parseFloat(selectedOption.getAttribute('data-price')) || 0 : 0;
                const days = parseInt(daysInput.value) || 0;
                const airport = airportPickup.checked;
                
                let total = carPrice * days;
                if (airport) total += 2000;
                
                totalPricePreview.textContent = total.toLocaleString();
            }
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>