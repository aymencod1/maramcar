<?php
session_start();
include 'includes/config.php';
include 'includes/header.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// جلب بيانات المستخدم
$user = [];
$bookings = [];

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("المستخدم غير موجود");
    }

    // جلب حجوزات المستخدم (تم التعديل هنا)
    $booking_stmt = $conn->prepare("
        SELECT b.*, c.brand, c.model, c.image, c.fuel_type, c.seats 
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        WHERE b.user_id = ?
        ORDER BY b.pickup_date DESC
    ");
    $booking_stmt->execute([$_SESSION['user_id']]);
    $bookings = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
} catch(Exception $e) {
    die($e->getMessage());
}
?>

<section class="profile-section py-5" style="background: #f8f9fa; min-height: 100vh;">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold" style="
                font-size: 2.5rem;
                color: #121212;
                position: relative;
                display: inline-block;
            ">
                الملف الشخصي
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
            </h1>
            <p class="text-muted">إدارة معلومات حسابك وحجوزاتك</p>
        </div>

        <div class="row">
            <!-- معلومات المستخدم -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-lg h-100 border-0" style="
                    border-radius: 20px; 
                    overflow: hidden;
                    border: 2px solid #FFD700;
                ">
                    <div class="card-header py-4" style="
                        background: #121212;
                        color: #FFD700;
                        text-align: center;
                        border-bottom: none;
                    ">
                        <div class="d-flex justify-content-center mb-3">
                            <div class="avatar" style="
                                width: 100px;
                                height: 100px;
                                background: #FFD700;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 2.5rem;
                                color: #121212;
                                font-weight: bold;
                                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                            ">
                                <?= mb_substr($user['name'], 0, 1) ?>
                            </div>
                        </div>
                        <h3 class="mb-0"><?= htmlspecialchars($user['name']) ?></h3>
                        <p class="mb-0 opacity-75">عضو منذ <?= date('Y/m/d', strtotime($user['created_at'])) ?></p>
                    </div>
                    
                    <div class="card-body py-4">
                        <ul class="list-unstyled">
                            <li class="mb-3 d-flex align-items-center">
                                <div class="icon-circle" style="
                                    width: 40px;
                                    height: 40px;
                                    background: rgba(255, 215, 0, 0.1);
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    margin-left: 15px;
                                ">
                                    <i class="fas fa-envelope" style="color: #FFD700;"></i>
                                </div>
                                <div>
                                    <p class="mb-0 text-muted">البريد الإلكتروني</p>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($user['email']) ?></p>
                                </div>
                            </li>
                            
                            <li class="mb-3 d-flex align-items-center">
                                <div class="icon-circle" style="
                                    width: 40px;
                                    height: 40px;
                                    background: rgba(255, 215, 0, 0.1);
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    margin-left: 15px;
                                ">
                                    <i class="fas fa-phone" style="color: #FFD700;"></i>
                                </div>
                                <div>
                                    <p class="mb-0 text-muted">رقم الهاتف</p>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($user['phone'] ?? 'لم يتم التحديد') ?></p>
                                </div>
                            </li>
                            
                            <li class="d-flex align-items-center">
                                <div class="icon-circle" style="
                                    width: 40px;
                                    height: 40px;
                                    background: rgba(255, 215, 0, 0.1);
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    margin-left: 15px;
                                ">
                                    <i class="fas fa-calendar-alt" style="color: #FFD700;"></i>
                                </div>
                                <div>
                                    <p class="mb-0 text-muted">تاريخ التسجيل</p>
                                    <p class="mb-0 fw-bold"><?= date('Y/m/d', strtotime($user['created_at'])) ?></p>
                                </div>
                            </li>
                        </ul>
                        
                        <div class="mt-4 d-grid gap-2">
                            <a href="edit_profile.php" class="btn btn-lg" style="
                                background: #FFD700;
                                color: #121212;
                                border: none;
                                border-radius: 12px;
                                padding: 12px;
                                font-weight: 600;
                                transition: all 0.3s;
                            ">
                                <i class="fas fa-edit me-2"></i>تعديل الملف الشخصي
                            </a>
                            <a href="logout.php" class="btn btn-outline-dark btn-lg" style="
                                border: 2px solid #121212;
                                border-radius: 12px;
                                padding: 12px;
                                font-weight: 600;
                                transition: all 0.3s;
                            ">
                                <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الحساب -->
            <div class="col-lg-8">
                <div class="card shadow-lg border-0 mb-4" style="
                    border-radius: 20px;
                    border: 2px solid #FFD700;
                ">
                    <div class="card-header py-3" style="
                        background: #121212;
                        color: #FFD700;
                        border-top-left-radius: 20px !important;
                        border-top-right-radius: 20px !important;
                        border-bottom: none;
                    ">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>إحصائيات الحساب</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-3 col-6">
                                <div class="stats-box text-center p-3" style="
                                    background: rgba(255, 215, 0, 0.05);
                                    border-radius: 15px;
                                    transition: all 0.3s;
                                    height: 100%;
                                ">
                                    <div class="icon-circle mx-auto mb-3" style="
                                        width: 70px;
                                        height: 70px;
                                        background: rgba(255, 215, 0, 0.1);
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                    ">
                                        <i class="fas fa-calendar-check fa-2x" style="color: #FFD700;"></i>
                                    </div>
                                    <div class="stats-number fw-bold fs-3 mb-1">
                                        <?= count($bookings) ?>
                                    </div>
                                    <h5 class="mb-0">عدد الحجوزات</h5>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-6">
                                <div class="stats-box text-center p-3" style="
                                    background: rgba(255, 215, 0, 0.05);
                                    border-radius: 15px;
                                    transition: all 0.3s;
                                    height: 100%;
                                ">
                                    <div class="icon-circle mx-auto mb-3" style="
                                        width: 70px;
                                        height: 70px;
                                        background: rgba(255, 215, 0, 0.1);
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                    ">
                                        <i class="fas fa-coins fa-2x" style="color: #FFD700;"></i>
                                    </div>
                                    <div class="stats-number fw-bold fs-3 mb-1">
                                        <?php
                                        $total_spent = 0;
                                        foreach ($bookings as $booking) {
                                            $total_spent += $booking['total_price'];
                                        }
                                        echo number_format($total_spent);
                                        ?>
                                    </div>
                                    <h5 class="mb-0">إجمالي المصروف</h5>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-6">
                                <div class="stats-box text-center p-3" style="
                                    background: rgba(255, 215, 0, 0.05);
                                    border-radius: 15px;
                                    transition: all 0.3s;
                                    height: 100%;
                                ">
                                    <div class="icon-circle mx-auto mb-3" style="
                                        width: 70px;
                                        height: 70px;
                                        background: rgba(255, 215, 0, 0.1);
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                    ">
                                        <i class="fas fa-check-circle fa-2x" style="color: #FFD700;"></i>
                                    </div>
                                    <div class="stats-number fw-bold fs-3 mb-1">
                                        <?= count(array_filter($bookings, function($b) { return $b['status'] === 'confirmed'; })) ?>
                                    </div>
                                    <h5 class="mb-0">حجوزات مؤكدة</h5>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-6">
                                <div class="stats-box text-center p-3" style="
                                    background: rgba(255, 215, 0, 0.05);
                                    border-radius: 15px;
                                    transition: all 0.3s;
                                    height: 100%;
                                ">
                                    <div class="icon-circle mx-auto mb-3" style="
                                        width: 70px;
                                        height: 70px;
                                        background: rgba(255, 215, 0, 0.1);
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                    ">
                                        <i class="fas fa-clock fa-2x" style="color: #FFD700;"></i>
                                    </div>
                                    <div class="stats-number fw-bold fs-3 mb-1">
                                        <?= count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; })) ?>
                                    </div>
                                    <h5 class="mb-0">قيد الانتظار</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- حجوزاتي -->
                <div class="card shadow-lg border-0" style="
                    border-radius: 20px;
                    border: 2px solid #FFD700;
                ">
                    <div class="card-header py-3" style="
                        background: #121212;
                        color: #FFD700;
                        border-top-left-radius: 20px !important;
                        border-top-right-radius: 20px !important;
                        border-bottom: none;
                    ">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>حجوزاتي</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($bookings) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr style="background: rgba(255, 215, 0, 0.05);">
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
                                            $days = $booking['days'];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if($booking['image']): ?>
                                                    <img src="uploads/cars/<?= $booking['image'] ?>" 
                                                         alt="<?= $booking['model'] ?>" 
                                                         class="rounded me-3" 
                                                         width="60" height="40"
                                                         style="object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($booking['brand']) ?> <?= htmlspecialchars($booking['model']) ?></div>
                                                        <small class="text-muted"><?= $booking['fuel_type'] ?> - <?= $booking['seats'] ?> مقاعد</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?= $start->format('Y/m/d') ?></div>
                                                <div class="text-muted small">إلى <?= $end->format('Y/m/d') ?></div>
                                            </td>
                                            <td><?= $days ?> يوم</td>
                                            <td><?= number_format($booking['total_price'], 2) ?> دج</td>
                                            <td>
                                                <span class="badge" style="
                                                    background: <?= 
                                                        $booking['status'] === 'confirmed' ? 'rgba(25, 135, 84, 0.1)' : 
                                                        ($booking['status'] === 'pending' ? 'rgba(255, 193, 7, 0.1)' : 'rgba(108, 117, 125, 0.1)') 
                                                    ?>;
                                                    color: <?= 
                                                        $booking['status'] === 'confirmed' ? '#198754' : 
                                                        ($booking['status'] === 'pending' ? '#ffc107' : '#6c757d') 
                                                    ?>;
                                                    padding: 8px 12px;
                                                    border-radius: 12px;
                                                    font-weight: 600;
                                                ">
                                                    <?= $booking['status'] === 'confirmed' ? 'مؤكد' : 
                                                       ($booking['status'] === 'pending' ? 'قيد الانتظار' : 'ملغى') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="booking_details.php?id=<?= $booking['id'] ?>" class="btn btn-sm" style="
                                                    background: rgba(255, 215, 0, 0.1);
                                                    color: #121212;
                                                    border: 1px solid #FFD700;
                                                    border-radius: 12px;
                                                    padding: 8px 15px;
                                                    font-weight: 600;
                                                    transition: all 0.3s;
                                                ">
                                                    التفاصيل
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="cars.php" class="btn btn-lg" style="
                                    background: #FFD700;
                                    color: #121212;
                                    border: none;
                                    border-radius: 12px;
                                    padding: 12px 30px;
                                    font-weight: 600;
                                    transition: all 0.3s;
                                ">
                                    <i class="fas fa-car me-2"></i>حجز سيارة جديدة
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-calendar-times fa-4x" style="
                                        color: rgba(255, 215, 0, 0.2);
                                    "></i>
                                </div>
                                <h3 class="mb-3">لا توجد حجوزات</h3>
                                <p class="text-muted mb-4">لم تقم بحجز أي سيارة بعد. يمكنك استعراض السيارات المتاحة وحجز ما يناسبك</p>
                                <a href="#cars" class="btn btn-lg" style="
                                    background: #FFD700;
                                    color: #121212;
                                    border: none;
                                    border-radius: 12px;
                                    padding: 12px 30px;
                                    font-weight: 600;
                                    transition: all 0.3s;
                                ">
                                    <i class="fas fa-car me-2"></i>استعرض السيارات
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>