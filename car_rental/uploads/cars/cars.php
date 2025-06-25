<?php
session_start(); // بدء جلسة المستخدم

require 'includes/config.php';

// التحقق من تسجيل الدخول والصلاحيات (للمسؤول فقط)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// معالجة حذف سيارة
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    try {
        // حذف السيارة
        $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "تم حذف السيارة بنجاح";
        header("Location: cars.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "خطأ في حذف السيارة: " . $e->getMessage();
        header("Location: cars.php");
        exit();
    }
}

// معالجة إضافة/تعديل سيارة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $year = intval($_POST['year']);
    $color = $_POST['color'];
    $fuel_type = $_POST['fuel_type'];
    $seats = intval($_POST['seats']);
    $price = floatval($_POST['price']);
    $available = isset($_POST['available']) ? 1 : 0;
    
    try {
        if ($id > 0) {
            // تحديث بيانات السيارة
            $stmt = $conn->prepare("
                UPDATE cars SET 
                    brand = ?,
                    model = ?,
                    year = ?,
                    color = ?,
                    fuel_type = ?,
                    seats = ?,
                    price = ?,
                    available = ?
                WHERE id = ?
            ");
            $stmt->execute([$brand, $model, $year, $color, $fuel_type, $seats, $price, $available, $id]);
            
            $_SESSION['success'] = "تم تحديث السيارة بنجاح";
        } else {
            // إضافة سيارة جديدة
            $stmt = $conn->prepare("
                INSERT INTO cars (
                    brand, model, year, color, 
                    fuel_type, seats, price, available
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$brand, $model, $year, $color, $fuel_type, $seats, $price, $available]);
            
            $_SESSION['success'] = "تم إضافة السيارة بنجاح";
        }
        
        header("Location: cars.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "خطأ في العملية: " . $e->getMessage();
        header("Location: cars.php");
        exit();
    }
}

// جلب بيانات السيارات
try {
    $stmt = $conn->query("SELECT * FROM cars");
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
    <title>إدارة السيارات - MaramCAR</title>
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
        
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .badge-available {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
            padding: 5px 10px;
            border-radius: 8px;
        }
        
        .badge-unavailable {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 5px 10px;
            border-radius: 8px;
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
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="section-title">إدارة السيارات</h2>
                <p class="text-muted">إضافة وتعديل وحذف سيارات الشركة</p>
            </div>
            <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addCarModal">
                <i class="fas fa-plus me-2"></i> إضافة سيارة جديدة
            </button>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-car me-2"></i> قائمة السيارات
            </div>
            <div class="card-body">
                <?php if(count($cars) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العلامة التجارية</th>
                                    <th>الموديل</th>
                                    <th>السنة</th>
                                    <th>اللون</th>
                                    <th>نوع الوقود</th>
                                    <th>المقاعد</th>
                                    <th>السعر/يوم</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cars as $car): ?>
                                <tr>
                                    <td><?= $car['id'] ?></td>
                                    <td><?= $car['brand'] ?></td>
                                    <td><?= $car['model'] ?></td>
                                    <td><?= $car['year'] ?></td>
                                    <td><?= $car['color'] ?></td>
                                    <td><?= $car['fuel_type'] ?></td>
                                    <td><?= $car['seats'] ?></td>
                                    <td><?= number_format($car['price']) ?> دج</td>
                                    <td>
                                        <?php if($car['available'] == 1): ?>
                                            <span class="badge-available">متاحة</span>
                                        <?php else: ?>
                                            <span class="badge-unavailable">غير متاحة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary me-2" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCarModal"
                                                data-id="<?= $car['id'] ?>"
                                                data-brand="<?= $car['brand'] ?>"
                                                data-model="<?= $car['model'] ?>"
                                                data-year="<?= $car['year'] ?>"
                                                data-color="<?= $car['color'] ?>"
                                                data-fuel_type="<?= $car['fuel_type'] ?>"
                                                data-seats="<?= $car['seats'] ?>"
                                                data-price="<?= $car['price'] ?>"
                                                data-available="<?= $car['available'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="cars.php?delete=<?= $car['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('هل أنت متأكد من حذف هذه السيارة؟')">
                                            <i class="fas fa-trash"></i>
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
                            <i class="fas fa-car fa-4x" style="color: rgba(255, 215, 0, 0.2);"></i>
                        </div>
                        <h3 class="mb-3">لا توجد سيارات مسجلة</h3>
                        <p class="text-muted mb-4">يمكنك إضافة سيارات جديدة باستخدام الزر بالأعلى</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal إضافة سيارة -->
    <div class="modal fade" id="addCarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-gold">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> إضافة سيارة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">العلامة التجارية</label>
                            <input type="text" name="brand" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الموديل</label>
                            <input type="text" name="model" class="form-control" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">السنة</label>
                                <input type="number" name="year" class="form-control" min="1900" max="<?= date('Y') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">اللون</label>
                                <input type="text" name="color" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">نوع الوقود</label>
                                <select name="fuel_type" class="form-select" required>
                                    <option value="بنزين">بنزين</option>
                                    <option value="ديزل">ديزل</option>
                                    <option value="كهرباء">كهرباء</option>
                                    <option value="هايبرد">هايبرد</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">عدد المقاعد</label>
                                <input type="number" name="seats" class="form-control" min="1" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">السعر اليومي (دج)</label>
                            <input type="number" name="price" class="form-control" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="available" id="available" checked>
                                <label class="form-check-label" for="available">
                                    متاحة للحجز
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-gold">إضافة السيارة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal تعديل سيارة -->
    <div class="modal fade" id="editCarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-gold">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> تعديل بيانات السيارة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">العلامة التجارية</label>
                            <input type="text" name="brand" id="editBrand" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الموديل</label>
                            <input type="text" name="model" id="editModel" class="form-control" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">السنة</label>
                                <input type="number" name="year" id="editYear" class="form-control" min="1900" max="<?= date('Y') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">اللون</label>
                                <input type="text" name="color" id="editColor" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">نوع الوقود</label>
                                <select name="fuel_type" id="editFuelType" class="form-select" required>
                                    <option value="بنزين">بنزين</option>
                                    <option value="ديزل">ديزل</option>
                                    <option value="كهرباء">كهرباء</option>
                                    <option value="هايبرد">هايبرد</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">عدد المقاعد</label>
                                <input type="number" name="seats" id="editSeats" class="form-control" min="1" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">السعر اليومي (دج)</label>
                            <input type="number" name="price" id="editPrice" class="form-control" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="available" id="editAvailable">
                                <label class="form-check-label" for="editAvailable">
                                    متاحة للحجز
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-gold">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // جافاسكريبت لتعبئة بيانات التعديل
        const editCarModal = document.getElementById('editCarModal');
        editCarModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            
            document.getElementById('editId').value = button.getAttribute('data-id');
            document.getElementById('editBrand').value = button.getAttribute('data-brand');
            document.getElementById('editModel').value = button.getAttribute('data-model');
            document.getElementById('editYear').value = button.getAttribute('data-year');
            document.getElementById('editColor').value = button.getAttribute('data-color');
            document.getElementById('editFuelType').value = button.getAttribute('data-fuel_type');
            document.getElementById('editSeats').value = button.getAttribute('data-seats');
            document.getElementById('editPrice').value = button.getAttribute('data-price');
            
            const available = button.getAttribute('data-available');
            document.getElementById('editAvailable').checked = available === '1';
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>