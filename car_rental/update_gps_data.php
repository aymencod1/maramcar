<?php
// update_gps_data.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'فشل الاتصال بقاعدة البيانات']));
}

// جلب بيانات التتبع من Banool IoT API
function fetchBanoolIoTData($device_id) {
    // هذا مثال افتراضي، يجب استبداله بالاتصال الحقيقي بAPI Banool IoT
    // في الواقع، سيكون لديك تفاصيل الاتصال الفعلية هنا
    
    return [
        'latitude' => 36.7525 + (rand(-100, 100) / 1000),
        'longitude' => 3.042 + (rand(-100, 100) / 1000),
        'status' => ['online', 'offline', 'moving'][rand(0, 2)],
        'speed' => rand(0, 120),
        'battery' => rand(20, 100)
    ];
}

// جلب جميع أجهزة GPS
$sql = "SELECT DISTINCT device_id FROM car_gps";
$result = $conn->query($sql);

$trackedCars = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $device_id = $row['device_id'];
        $data = fetchBanoolIoTData($device_id);
        
        if ($data) {
            // تحديث قاعدة البيانات
            $update_sql = "UPDATE car_gps SET 
                          latitude = ?, 
                          longitude = ?, 
                          status = ?, 
                          speed = ?, 
                          battery_level = ?,
                          last_update = NOW()
                          WHERE device_id = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ddssds", 
                $data['latitude'], 
                $data['longitude'],
                $data['status'],
                $data['speed'],
                $data['battery'],
                $device_id
            );
            
            if ($stmt->execute()) {
                // جلب بيانات السيارة
                $car_sql = "SELECT c.id, c.brand, c.model 
                            FROM cars c 
                            WHERE c.gps_device_id = ?";
                $car_stmt = $conn->prepare($car_sql);
                $car_stmt->bind_param("s", $device_id);
                $car_stmt->execute();
                $car_result = $car_stmt->get_result();
                
                if ($car_result->num_rows > 0) {
                    $car = $car_result->fetch_assoc();
                    $trackedCars[] = [
                        'id' => $car['id'],
                        'brand' => $car['brand'],
                        'model' => $car['model'],
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'status' => $data['status'],
                        'speed' => $data['speed'],
                        'battery_level' => $data['battery'],
                        'device_id' => $device_id
                    ];
                }
            }
        }
    }
}

echo json_encode(['success' => true, 'trackedCars' => $trackedCars]);
$conn->close();
?>