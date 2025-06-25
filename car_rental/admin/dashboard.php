<?php
// اتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// جلب إحصائيات النظام
$total_cars = 0;
$available_cars = 0;
$total_bookings = 0;
$active_bookings = 0;

$sql = "SELECT COUNT(*) as total FROM cars";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_cars = $row["total"];
}

$sql = "SELECT COUNT(*) as available FROM cars WHERE available = 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $available_cars = $row["available"];
}

$sql = "SELECT COUNT(*) as total FROM bookings";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_bookings = $row["total"];
}

$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as active FROM bookings 
        WHERE start_date <= '$today' 
        AND end_date >= '$today' 
        AND status = 'confirmed'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $active_bookings = $row["active"];
}

// جلب بيانات السيارات
$carsData = [];
$sql = "SELECT * FROM cars";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $carsData[] = $row;
    }
}

// جلب بيانات الحجوزات
$bookingsData = [];
$sql = "SELECT b.*, c.brand, c.model, u.name as customer_name 
        FROM bookings b 
        JOIN cars c ON b.car_id = c.id 
        JOIN users u ON b.user_id = u.id";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $bookingsData[] = $row;
    }
}

// جلب بيانات العملاء
$usersData = [];
$sql = "SELECT * FROM users WHERE role = 'user'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $usersData[] = $row;
    }
}

// جلب بيانات تتبع السيارات من Banool IoT GPS
$trackedCars = [];
$sql = "SELECT c.id, c.brand, c.model, g.latitude, g.longitude, g.status, g.speed, g.battery_level, g.device_id
        FROM cars c 
        JOIN car_gps g ON c.gps_device_id = g.device_id 
        WHERE g.last_update >= NOW() - INTERVAL 10 MINUTE";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $trackedCars[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المدير - نظام تأجير السيارات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --light: #f8f9fa;
            --dark: #121212;
            --gold: #FFD700;
            --admin-dark: #1a1d21;
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Tajawal', sans-serif;
            color: #333;
        }
        
        .admin-header {
            background: linear-gradient(to right, #121212, #2c2c2c);
            color: #FFD700;
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .admin-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: none;
            margin-bottom: 25px;
            transition: all 0.3s;
            height: 100%;
        }
        
        .admin-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
        
        .admin-card-header {
            background: linear-gradient(to right, #121212, #2c2c2c);
            color: #FFD700;
            border-top-left-radius: 15px !important;
            border-top-right-radius: 15px !important;
            font-weight: 700;
            padding: 15px 20px;
        }
        
        .admin-card-body {
            padding: 20px;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-title {
            font-size: 1rem;
            color: #6c757d;
        }
        
        .table th {
            background: rgba(255, 215, 0, 0.1);
            color: #121212;
            font-weight: 700;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .btn-admin {
            background: #FFD700;
            color: #121212;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .btn-admin:hover {
            background: #e6c200;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 600;
            border: none;
            border-radius: 12px 12px 0 0;
            padding: 12px 25px;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(to right, #121212, #2c2c2c);
            color: #FFD700;
            border: none;
        }
        
        .car-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-left: 10px;
        }
        
        #map {
            height: 500px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        
        .gps-info-box {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1000;
            margin-top: 15px;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .car-status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-online {
            background-color: #28a745;
        }
        
        .status-offline {
            background-color: #dc3545;
        }
        
        .status-moving {
            background-color: #0d6efd;
        }
        
        .sidebar {
            background: var(--admin-dark);
            color: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            right: 0;
            width: 250px;
            padding-top: 20px;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
        }
        
        .sidebar .nav-link i {
            margin-left: 10px;
        }
        
        .main-content {
            margin-right: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .navbar-admin {
            background: var(--admin-dark);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .car-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .search-form {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-form .form-control {
            padding-right: 40px;
        }
        
        .search-form .btn {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .filter-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }
        
        .settings-form .form-group {
            margin-bottom: 20px;
        }
        
        .car-details-modal img {
            max-height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .car-marker {
            background: #0d6efd;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            border: 2px solid white;
        }
        
        .moving {
            background: #28a745;
            animation: pulse 1.5s infinite;
        }
        
        .offline {
            background: #dc3545;
        }
        
        .online {
            background: #0d6efd;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        
        .gps-device-info {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            width: 300px;
        }
    </style>
</head>
<body>
    <!-- الشريط الجانبي -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <img src="https://via.placeholder.com/80x80?text=M" class="rounded-circle mb-2" alt="Logo">
            <h4 class="mb-0">نظام تأجير السيارات</h4>
            <p class="text-gold">مع Banool IoT GPS</p>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#">
                    <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#cars-section">
                    <i class="fas fa-car"></i> إدارة السيارات
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#bookings-section">
                    <i class="fas fa-calendar-alt"></i> إدارة الحجوزات
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#tracking-section">
                    <i class="fas fa-map-marked-alt"></i> تتبع السيارات
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#customers-section">
                    <i class="fas fa-users"></i> إدارة العملاء
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#reports-section">
                    <i class="fas fa-chart-bar"></i> التقارير والإحصائيات
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#settings-section">
                    <i class="fas fa-cog"></i> الإعدادات
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link bg-danger text-white" href="#">
                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                </a>
            </li>
        </ul>
    </div>
    
    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <!-- شريط التنقل العلوي -->
        <nav class="navbar navbar-admin navbar-expand-lg mb-4 rounded">
            <div class="container-fluid">
                <button class="btn btn-gold d-md-none" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="d-flex align-items-center">
                    <div class="position-relative me-3">
                        <a href="#" class="text-white">
                            <i class="fas fa-bell fs-5"></i>
                            <span class="notification-badge">3</span>
                        </a>
                    </div>
                    
                    <div class="dropdown">
                        <a href="#" class="d-block link-light text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://via.placeholder.com/40x40?text=A" class="rounded-circle" alt="Admin">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
                            <li><a class="dropdown-item" href="#">الملف الشخصي</a></li>
                            <li><a class="dropdown-item" href="#settings-section">الإعدادات</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">تسجيل الخروج</a></li>
                        </ul>
                    </div>
                    <div class="me-3">
                        <div class="text-gold small">أيمن</div>
                        <div class="text-white">مدير النظام</div>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- رسائل التنبيه -->
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            تم تحديث حالة الحجز بنجاح
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        
        <!-- إحصائيات النظام -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-number" id="total-cars"><?php echo $total_cars; ?></div>
                    <div class="stat-title">إجمالي السيارات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number" id="available-cars"><?php echo $available_cars; ?></div>
                    <div class="stat-title">سيارات متاحة</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number" id="total-bookings"><?php echo $total_bookings; ?></div>
                    <div class="stat-title">إجمالي الحجوزات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="stat-number" id="active-bookings"><?php echo $active_bookings; ?></div>
                    <div class="stat-title">حجوزات نشطة</div>
                </div>
            </div>
        </div>
        
        <!-- تبويبات التحكم -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="cars-tab" data-bs-toggle="tab" data-bs-target="#cars" type="button" role="tab">
                    <i class="fas fa-car me-2"></i> إدارة السيارات
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab">
                    <i class="fas fa-calendar-alt me-2"></i> إدارة الحجوزات
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="gps-tab" data-bs-toggle="tab" data-bs-target="#gps" type="button" role="tab">
                    <i class="fas fa-map-marked-alt me-2"></i> تتبع السيارات
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="adminTabsContent">
            <!-- تبويب إدارة السيارات -->
            <div class="tab-pane fade show active" id="cars" role="tabpanel" id="cars-section">
                <div class="row">
                    <div class="col-12">
                        <div class="admin-card">
                            <div class="admin-card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-car me-2"></i> قائمة السيارات</span>
                                <div class="d-flex">
                                    <div class="search-form me-3">
                                        <input type="text" class="form-control" placeholder="بحث عن سيارة..." id="car-search">
                                        <button class="btn btn-sm btn-link text-dark">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <button class="btn btn-admin btn-sm" data-bs-toggle="modal" data-bs-target="#addCarModal">
                                        <i class="fas fa-plus me-1"></i> إضافة سيارة جديدة
                                    </button>
                                </div>
                            </div>
                            <div class="admin-card-body">
                                <div class="filter-container">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label>العلامة التجارية</label>
                                            <select class="form-select" id="car-brand-filter">
                                                <option value="">الكل</option>
                                                <option>Toyota</option>
                                                <option>Hyundai</option>
                                                <option>Renault</option>
                                                <option>Peugeot</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>نوع الوقود</label>
                                            <select class="form-select" id="car-fuel-filter">
                                                <option value="">الكل</option>
                                                <option>بنزين</option>
                                                <option>ديزل</option>
                                                <option>كهرباء</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>الحالة</label>
                                            <select class="form-select" id="car-status-filter">
                                                <option value="">الكل</option>
                                                <option>متاحة</option>
                                                <option>غير متاحة</option>
                                                <option>قيد الصيانة</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>&nbsp;</label>
                                            <button class="btn btn-admin w-100" id="apply-car-filters">تطبيق الفلتر</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>الصورة</th>
                                                <th>السيارة</th>
                                                <th>السنة</th>
                                                <th>السعر/يوم</th>
                                                <th>GPS ID</th>
                                                <th>الحالة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cars-table-body">
                                            <?php foreach ($carsData as $car): 
                                                $statusText = $car['available'] == 1 ? 'متاحة' : 'غير متاحة';
                                                $statusClass = $car['available'] == 1 ? 'bg-success' : 'bg-danger';
                                            ?>
                                            <tr>
                                                <td><?php echo $car['id']; ?></td>
                                                <td>
                                                    <img src="<?php echo $car['image']; ?>" class="car-image" alt="<?php echo $car['brand'].' '.$car['model']; ?>">
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div>
                                                            <div class="fw-bold"><?php echo $car['brand'].' '.$car['model']; ?></div>
                                                            <div class="text-muted small"><?php echo $car['fuel_type'].' - '.$car['seats'].' مقاعد'; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $car['year'] ?? 'N/A'; ?></td>
                                                <td><?php echo number_format($car['price']).' دج'; ?></td>
                                                <td><?php echo $car['gps_device_id'] ?? 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary me-2" onclick="showCarDetails(<?php echo $car['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-info me-2" onclick="editCar(<?php echo $car['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteCar(<?php echo $car['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1">السابق</a>
                                        </li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item">
                                            <a class="page-link" href="#">التالي</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- تبويب إدارة الحجوزات -->
            <div class="tab-pane fade" id="bookings" role="tabpanel" id="bookings-section">
                <div class="row">
                    <div class="col-12">
                        <div class="admin-card">
                            <div class="admin-card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-calendar-alt me-2"></i> إدارة الحجوزات</span>
                                <div>
                                    <button class="btn btn-admin btn-sm me-2">
                                        <i class="fas fa-file-excel me-1"></i> تصدير Excel
                                    </button>
                                    <button class="btn btn-admin btn-sm">
                                        <i class="fas fa-print me-1"></i> طباعة
                                    </button>
                                </div>
                            </div>
                            <div class="admin-card-body">
                                <div class="filter-container">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label>حالة الحجز</label>
                                            <select class="form-select" id="booking-status-filter">
                                                <option value="">الكل</option>
                                                <option>قيد الانتظار</option>
                                                <option>مؤكد</option>
                                                <option>ملغى</option>
                                                <option>منتهي</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>من تاريخ</label>
                                            <input type="date" class="form-control" id="booking-from-date">
                                        </div>
                                        <div class="col-md-3">
                                            <label>إلى تاريخ</label>
                                            <input type="date" class="form-control" id="booking-to-date">
                                        </div>
                                        <div class="col-md-3">
                                            <label>&nbsp;</label>
                                            <button class="btn btn-admin w-100" id="apply-booking-filters">تطبيق الفلتر</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>السيارة</th>
                                                <th>العميل</th>
                                                <th>التواريخ</th>
                                                <th>المدة</th>
                                                <th>المبلغ</th>
                                                <th>الحالة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bookings-table-body">
                                            <?php foreach ($bookingsData as $booking): 
                                                $statusMapping = [
                                                    'pending' => 'قيد الانتظار',
                                                    'confirmed' => 'مؤكد',
                                                    'cancelled' => 'ملغى'
                                                ];
                                                
                                                $statusText = $statusMapping[$booking['status']] ?? $booking['status'];
                                                $statusClass = [
                                                    'مؤكد' => 'background: rgba(25, 135, 84, 0.1); color: #198754;',
                                                    'قيد الانتظار' => 'background: rgba(255, 193, 7, 0.1); color: #ffc107;',
                                                    'ملغى' => 'background: rgba(108, 117, 125, 0.1); color: #6c757d;'
                                                ];
                                                
                                                $style = $statusClass[$statusText] ?? '';
                                                
                                                $start = new DateTime($booking['start_date']);
                                                $end = new DateTime($booking['end_date']);
                                                $days = $start->diff($end)->days;
                                            ?>
                                            <tr>
                                                <td><?php echo $booking['id']; ?></td>
                                                <td>
                                                    <div class="fw-bold"><?php echo $booking['brand'].' '.$booking['model']; ?></div>
                                                </td>
                                                <td><?php echo $booking['customer_name']; ?></td>
                                                <td>
                                                    <div><?php echo $booking['start_date']; ?></div>
                                                    <div class="text-muted small">إلى <?php echo $booking['end_date']; ?></div>
                                                </td>
                                                <td><?php echo $days; ?> أيام</td>
                                                <td><?php echo number_format($booking['total_price']).' دج'; ?></td>
                                                <td>
                                                    <span class="status-badge" style="<?php echo $style; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <select class="form-select form-select-sm me-2" onchange="updateBookingStatus(<?php echo $booking['id']; ?>, this.value)">
                                                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>مؤكد</option>
                                                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>ملغى</option>
                                                        </select>
                                                        <button type="button" class="btn btn-sm btn-admin">
                                                            <i class="fas fa-save"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1">السابق</a>
                                        </li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item">
                                            <a class="page-link" href="#">التالي</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- تبويب تتبع السيارات -->
            <div class="tab-pane fade" id="gps" role="tabpanel" id="tracking-section">
                <div class="row">
                    <div class="col-md-8">
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <i class="fas fa-map-marked-alt me-2"></i> تتبع السيارات عبر Banool IoT GPS
                            </div>
                            <div class="admin-card-body position-relative">
                                <div id="map"></div>
                                
                                <div class="gps-device-info" id="gps-device-info" style="display: none;">
                                    <h6 id="device-title" class="mb-3">معلومات الجهاز</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-2">
                                                <i class="fas fa-car me-2"></i>
                                                <span id="device-car">-</span>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-id-card me-2"></i>
                                                <span id="device-id">-</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-2">
                                                <i class="fas fa-battery-full me-2"></i>
                                                <span id="device-battery">-</span>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-tachometer-alt me-2"></i>
                                                <span id="device-speed">-</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-admin w-100" id="center-map">
                                            <i class="fas fa-crosshairs me-1"></i> مركزة على الجهاز
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="gps-info-box mt-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <span class="car-status-indicator status-online"></span>
                                                <strong>متصل:</strong> <span id="online-cars"><?php echo count(array_filter($trackedCars, function($car) { return $car['status'] === 'online'; })); ?></span> سيارات
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <span class="car-status-indicator status-offline"></span>
                                                <strong>غير متصل:</strong> <span id="offline-cars"><?php echo count(array_filter($trackedCars, function($car) { return $car['status'] === 'offline'; })); ?></span> سيارات
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <span class="car-status-indicator status-moving"></span>
                                                <strong>في حركة:</strong> <span id="moving-cars"><?php echo count(array_filter($trackedCars, function($car) { return $car['status'] === 'moving'; })); ?></span> سيارات
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <i class="fas fa-car me-2"></i> حالة السيارات
                            </div>
                            <div class="admin-card-body">
                                <div class="list-group" id="cars-status-list">
                                    <?php foreach ($carsData as $car): 
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        if ($car['available'] == 1) {
                                            $statusClass = 'status-online';
                                            $statusText = 'متاحة';
                                        } else {
                                            $statusClass = 'status-offline';
                                            $statusText = 'غير متاحة';
                                        }
                                        
                                        // تحقق ما إذا كانت السيارة في حركة
                                        $trackedCar = array_filter($trackedCars, function($tracked) use ($car) {
                                            return $tracked['id'] == $car['id'] && $tracked['status'] === 'moving';
                                        });
                                        
                                        if (!empty($trackedCar)) {
                                            $statusClass = 'status-moving';
                                            $statusText = 'في حركة';
                                        }
                                    ?>
                                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center car-item" data-car-id="<?php echo $car['id']; ?>" data-device-id="<?php echo $car['gps_device_id']; ?>">
                                        <div>
                                            <span class="car-status-indicator <?php echo $statusClass; ?>"></span>
                                            <strong><?php echo $car['brand'].' '.$car['model']; ?></strong>
                                        </div>
                                        <span class="badge bg-primary rounded-pill"><?php echo $statusText; ?></span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5 class="card-title"><i class="fas fa-info-circle me-2"></i> معلومات التتبع</h5>
                                            <ul class="list-unstyled" id="tracking-info">
                                                <li><i class="fas fa-satellite me-2"></i> نظام تتبع: Banool IoT GPS</li>
                                                <li><i class="fas fa-sync-alt me-2"></i> تحديث البيانات: كل 30 ثانية</li>
                                                <li><i class="fas fa-history me-2"></i> آخر تحديث: <?php echo date('H:i:s'); ?></li>
                                                <li><i class="fas fa-car me-2"></i> السيارات المتعقبة: <?php echo count($carsData); ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- إدارة العملاء -->
        <div class="row mb-4" id="customers-section">
            <div class="col-12">
                <div class="admin-card">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-users me-2"></i> إدارة العملاء</span>
                        <button class="btn btn-admin btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                            <i class="fas fa-plus me-1"></i> إضافة عميل جديد
                        </button>
                    </div>
                    <div class="admin-card-body">
                        <div class="search-form mb-4">
                            <input type="text" class="form-control" placeholder="بحث عن عميل..." id="customer-search">
                            <button class="btn btn-sm btn-link text-dark">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>العميل</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>الهاتف</th>
                                        <th>عدد الحجوزات</th>
                                        <th>حالة الحساب</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody id="customers-table-body">
                                    <?php foreach ($usersData as $user): 
                                        $bookingsCount = 0; // سيتم حسابها من قاعدة البيانات
                                        $statusText = $user['is_admin'] == 1 ? 'مدير' : 'نشط';
                                        $statusClass = $user['is_admin'] == 1 ? 'bg-primary' : 'bg-success';
                                    ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/100x100?text=U" class="user-avatar me-3">
                                                <div>
                                                    <div class="fw-bold"><?php echo $user['name']; ?></div>
                                                    <div class="text-muted small">العضوية: <?php echo $bookingsCount > 10 ? 'مميزة' : 'عادية'; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td><?php echo $user['phone']; ?></td>
                                        <td><?php echo $bookingsCount; ?></td>
                                        <td>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-info me-2">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- الإعدادات -->
        <div class="row mb-4" id="settings-section">
            <div class="col-12">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <i class="fas fa-cog me-2"></i> إعدادات النظام
                    </div>
                    <div class="admin-card-body">
                        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                    الإعدادات العامة
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="gps-tab" data-bs-toggle="tab" data-bs-target="#gps-settings" type="button" role="tab">
                                    إعدادات GPS
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">
                                    إعدادات الدفع
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                                    الإشعارات
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="settingsTabsContent">
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <form class="settings-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">اسم النظام</label>
                                                <input type="text" class="form-control" value="نظام تأجير السيارات">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">عنوان الموقع</label>
                                                <input type="text" class="form-control" value="https://yourdomain.com">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">العملة</label>
                                                <select class="form-select">
                                                    <option>دينار جزائري (DZD)</option>
                                                    <option>دولار أمريكي (USD)</option>
                                                    <option>يورو (EUR)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">صورة الشعار</label>
                                                <input type="file" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="maintenance" checked>
                                                    <label class="form-check-label" for="maintenance">
                                                        وضع الصيانة
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="registration" checked>
                                                    <label class="form-check-label" for="registration">
                                                        السماح بالتسجيل للعملاء
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-admin">حفظ التعديلات</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="tab-pane fade" id="gps-settings" role="tabpanel">
                                <form class="settings-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">مزود خدمة GPS</label>
                                                <select class="form-select">
                                                    <option>Banool IoT GPS</option>
                                                    <option>Tracki</option>
                                                    <option>GPS Tracker</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">مفتاح API</label>
                                                <input type="text" class="form-control" value="bno-1a2b3c4d5e6f7g8h">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">معدل التحديث (ثانية)</label>
                                                <input type="number" class="form-control" value="30" min="5" max="120">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">نطاق المسافة (كم)</label>
                                                <input type="number" class="form-control" value="5" min="1" max="50">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">إشعارات الانحراف</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="deviation" checked>
                                                    <label class="form-check-label" for="deviation">
                                                        تفعيل الإشعارات
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">إشعارات السرعة الزائدة</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="speed" checked>
                                                    <label class="form-check-label" for="speed">
                                                        تفعيل الإشعارات
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-admin">حفظ التعديلات</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="tab-pane fade" id="payment" role="tabpanel">
                                <form class="settings-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">بوابة الدفع</label>
                                                <select class="form-select">
                                                    <option>PayPal</option>
                                                    <option>Stripe</option>
                                                    <option>PayFort</option>
                                                    <option>الدفع عند الاستلام</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">مفتاح API العام</label>
                                                <input type="text" class="form-control" value="pk_test_1234567890">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">مفتاح API السري</label>
                                                <input type="password" class="form-control" value="sk_test_0987654321">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">نسبة الضريبة (%)</label>
                                                <input type="number" class="form-control" value="7" min="0" max="20">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">رسوم الخدمة</label>
                                                <input type="number" class="form-control" value="500" min="0">
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="payment-test" checked>
                                                    <label class="form-check-label" for="payment-test">
                                                        وضع الاختبار
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-admin">حفظ التعديلات</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="tab-pane fade" id="notifications" role="tabpanel">
                                <form class="settings-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">إشعارات البريد الإلكتروني</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="email-notify" checked>
                                                    <label class="form-check-label" for="email-notify">
                                                        تفعيل الإشعارات
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">البريد الإلكتروني للإشعارات</label>
                                                <input type="email" class="form-control" value="notify@yourdomain.com">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">قوالب الإشعارات</label>
                                                <select class="form-select">
                                                    <option>إشعار حجز جديد</option>
                                                    <option>إشعار تأكيد حجز</option>
                                                    <option>إشعار انتهاء حجز</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">إشعارات الجوال</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="sms-notify">
                                                    <label class="form-check-label" for="sms-notify">
                                                        تفعيل الإشعارات
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">رقم الجوال للإشعارات</label>
                                                <input type="tel" class="form-control" value="+213550000000">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">مفتاح API للإشعارات</label>
                                                <input type="text" class="form-control" value="sms-api-key-123456">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-admin">حفظ التعديلات</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- إحصائيات إضافية -->
        <div class="row mb-4" id="reports-section">
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <i class="fas fa-chart-line me-2"></i> إحصائيات الحجوزات
                    </div>
                    <div class="admin-card-body">
                        <div class="chart-container">
                            <canvas id="bookingsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <i class="fas fa-car me-2"></i> السيارات الأكثر طلباً
                    </div>
                    <div class="admin-card-body">
                        <div class="chart-container">
                            <canvas id="popularCarsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <!-- ... (نفس Modals السابقة) ... -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // خريطة تتبع السيارات
        const map = L.map('map').setView([36.7525, 3.042], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // إضافة علامات للسيارات
        const trackedCars = <?php echo json_encode($trackedCars); ?>;
        const carMarkers = {};
        let selectedCar = null;
        
        trackedCars.forEach(car => {
            const carStatus = car.status;
            const markerClass = carStatus === 'moving' ? 'moving' : 
                              (carStatus === 'online' ? 'online' : 'offline');
            
            const markerDiv = document.createElement('div');
            markerDiv.className = `car-marker ${markerClass}`;
            markerDiv.innerHTML = `<i class="fas fa-car"></i>`;
            
            const icon = L.divIcon({
                className: 'car-icon',
                html: markerDiv.outerHTML,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
            
            const marker = L.marker([car.latitude, car.longitude], {icon: icon})
                .addTo(map);
            
            const popupContent = `
                <div class="text-center">
                    <h6>${car.brand} ${car.model}</h6>
                    <div class="d-flex justify-content-between">
                        <div class="text-start">
                            <div><strong>الحالة:</strong> ${carStatus === 'moving' ? 'في حركة' : (carStatus === 'online' ? 'متوقفة' : 'غير متصلة')}</div>
                            <div><strong>السرعة:</strong> ${car.speed} كم/س</div>
                        </div>
                        <div class="text-end">
                            <div><strong>البطارية:</strong> ${car.battery_level}%</div>
                            <div><strong>GPS ID:</strong> ${car.device_id}</div>
                        </div>
                    </div>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            
            // تخزين المرجع للعلامة باستخدام معرف السيارة
            carMarkers[car.id] = marker;
            
            // إضافة حدث النقر
            marker.on('click', function(e) {
                showDeviceInfo(car);
                selectedCar = car.id;
            });
        });
        
        // عرض معلومات الجهاز
        function showDeviceInfo(car) {
            const deviceInfo = document.getElementById('gps-device-info');
            const carStatus = car.status === 'moving' ? 'في حركة' : 
                           (car.status === 'online' ? 'متصل' : 'غير متصل');
            
            document.getElementById('device-title').innerHTML = `جهاز ${car.device_id}`;
            document.getElementById('device-car').innerHTML = `${car.brand} ${car.model}`;
            document.getElementById('device-id').innerHTML = car.device_id;
            document.getElementById('device-battery').innerHTML = `${car.battery_level}%`;
            document.getElementById('device-speed').innerHTML = `${car.speed} كم/س`;
            
            deviceInfo.style.display = 'block';
        }
        
        // مركزة الخريطة على سيارة محددة
        document.getElementById('center-map').addEventListener('click', function() {
            if (selectedCar && carMarkers[selectedCar]) {
                map.setView(carMarkers[selectedCar].getLatLng(), 15);
            }
        });
        
        // اختيار سيارة من القائمة
        document.querySelectorAll('.car-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const carId = this.getAttribute('data-car-id');
                const deviceId = this.getAttribute('data-device-id');
                
                // البحث عن السيارة في بيانات التتبع
                const car = trackedCars.find(c => c.id == carId);
                
                if (car) {
                    // مركزة الخريطة على السيارة
                    if (carMarkers[carId]) {
                        map.setView(carMarkers[carId].getLatLng(), 15);
                        carMarkers[carId].openPopup();
                        showDeviceInfo(car);
                        selectedCar = carId;
                    }
                } else {
                    alert('لا توجد بيانات تتبع لهذه السيارة حالياً');
                }
            });
        });
        
        // مخطط إحصائيات الحجوزات
        const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
        const bookingsChart = new Chart(bookingsCtx, {
            type: 'line',
            data: {
                labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
                datasets: [{
                    label: 'عدد الحجوزات',
                    data: [12, 19, 15, 22, 18, 24],
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // مخطط السيارات الأكثر طلباً
        const popularCarsCtx = document.getElementById('popularCarsChart').getContext('2d');
        const popularCarsChart = new Chart(popularCarsCtx, {
            type: 'bar',
            data: {
                labels: ['تويوتا كامري', 'هيونداي سوناتا', 'رينو سيمبل', 'بيجو 301', 'مرسيدس C200'],
                datasets: [{
                    label: 'عدد الحجوزات',
                    data: [24, 18, 15, 12, 8],
                    backgroundColor: [
                        'rgba(255, 215, 0, 0.7)',
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(108, 117, 125, 0.7)',
                        'rgba(220, 53, 69, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // تحديث بيانات التتبع كل 30 ثانية
        setInterval(() => {
            fetch('update_gps_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // تحديث الخريطة
                        data.trackedCars.forEach(car => {
                            if (carMarkers[car.id]) {
                                const newLatLng = new L.LatLng(car.latitude, car.longitude);
                                carMarkers[car.id].setLatLng(newLatLng);
                                
                                // تحديث حالة العلامة
                                const icon = carMarkers[car.id].getIcon();
                                const markerDiv = document.createElement('div');
                                markerDiv.className = `car-marker ${car.status === 'moving' ? 'moving' : 
                                                    (car.status === 'online' ? 'online' : 'offline')}`;
                                markerDiv.innerHTML = `<i class="fas fa-car"></i>`;
                                icon.options.html = markerDiv.outerHTML;
                                carMarkers[car.id].setIcon(icon);
                                
                                // تحديث آخر تحديث
                                document.querySelector('#tracking-info li:nth-child(3)').innerHTML = 
                                    `<i class="fas fa-history me-2"></i> آخر تحديث: ${new Date().toLocaleTimeString()}`;
                            }
                        });
                    }
                });
        }, 30000);
        
        // وظائف لوحة التحكم
        function showCarDetails(carId) {
            // ... (نفس الوظائف السابقة) ...
        }
        
        function editCar(carId) {
            // ... (نفس الوظائف السابقة) ...
        }
        
        function deleteCar(carId) {
            if (confirm('هل أنت متأكد من حذف هذه السيارة؟')) {
                fetch(`delete_car.php?id=${carId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم حذف السيارة بنجاح');
                        location.reload();
                    } else {
                        alert('حدث خطأ أثناء حذف السيارة');
                    }
                });
            }
        }
        
        function updateBookingStatus(bookingId, status) {
            fetch(`update_booking_status.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    booking_id: bookingId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم تحديث حالة الحجز بنجاح');
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء تحديث حالة الحجز');
                }
            });
        }
        
        // التنقل بين الأقسام
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if(this.getAttribute('href') !== '#') {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if(targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>