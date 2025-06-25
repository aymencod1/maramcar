<?php
// دالة لجلب السيارات من قاعدة البيانات
function getCars($pdo, $limit = 6) {
    $stmt = $pdo->prepare("SELECT * FROM cars LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// دالة لحساب مدة التأجير بالساعات
function calculateRentalHours($start, $end) {
    $start = new DateTime($start);
    $end = new DateTime($end);
    $interval = $start->diff($end);
    return $interval->h + ($interval->days * 24);
}

// دالة للحصول على تفاصيل سيارة
function getCarDetails($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>