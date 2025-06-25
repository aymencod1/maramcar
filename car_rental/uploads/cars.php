<?php
include 'includes/config.php';
include 'includes/header.php';

try {
    $stmt = $conn->query("SELECT * FROM cars WHERE available = 1");
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>

<section class="py-5">
    <div class="container">
        <h1 class="text-center mb-5">جميع السيارات المتاحة</h1>
        
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach($cars as $car): ?>
            <div class="col">
                <div class="card h-100 shadow">
                    <img src="uploads/cars/<?= $car['image'] ?? 'default.jpg' ?>" class="card-img-top" alt="<?= htmlspecialchars($car['model']) ?>">
                    <div class="card-body">
                        <h3 class="card-title"><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?></h3>
                        <p class="card-text"><?= htmlspecialchars($car['description']) ?></p>
                        <div class="car-details">
                            <p><i class="fas fa-gas-pump"></i> <?= htmlspecialchars($car['fuel_type']) ?></p>
                            <p><i class="fas fa-users"></i> <?= htmlspecialchars($car['seats']) ?> مقاعد</p>
                            <p class="h4"><?= number_format($car['price']) ?> دج/يوم</p>
                        </div>
                        <a href="booking.php?car_id=<?= $car['id'] ?>" class="btn btn-primary">حجز الآن</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>