<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Route: ดึงข้อมูลบูธทั้งหมด
$app->get('/booths', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับดึงข้อมูลจากตาราง booths
    $stmt = $conn->prepare("SELECT * FROM Booths");
    $stmt->execute();

    $result = $stmt->get_result();
    $booths = array();
    while ($row = $result->fetch_assoc()) {
        array_push($booths, $row);
    }

    $response->getBody()->write(json_encode($booths));
    return $response->withHeader('Content-Type', 'application/json');
});

// Route: เพิ่มข้อมูลบูธใหม่
$app->post('/booths/insert', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['booth_name'], $bodyArr['booth_size'], $bodyArr['booth_status'], $bodyArr['price'], $bodyArr['image_url'], $bodyArr['zone_id'])) {
        $error = ['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['conn'];
    // เตรียมคำสั่ง SQL สำหรับ insert ข้อมูลลงในตาราง booths
    $stmt = $conn->prepare("INSERT INTO Booths (booth_name, booth_size, booth_status, price, image_url, zone_id) VALUES (?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssi", 
        $bodyArr['booth_name'],      // ชื่อบูธ
        $bodyArr['booth_size'],      // ขนาดของบูธ
        $bodyArr['booth_status'],    // สถานะของบูธ
        $bodyArr['price'],           // ราคา
        $bodyArr['image_url'],       // URL ของรูปภาพบูธ
        $bodyArr['zone_id']          // รหัสโซนที่บูธนี้อยู่
    );

    $stmt->execute();
    $result = $stmt->affected_rows; 

    $response->getBody()->write(json_encode(["rows_affected" => $result]));

    return $response->withHeader('Content-Type', 'application/json');
});


$app->put('/booths/update', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['booth_id'])) {
        $error = ['error' => 'กรุณาระบุรหัสบูธ'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $conn = $GLOBALS['conn'];

    $updateFields = [];
    $params = [];
    $types = '';

    if (isset($bodyArr['booth_name'])) {
        $updateFields[] = 'booth_name = ?';
        $params[] = $bodyArr['booth_name'];
        $types .= 's';
    }

    if (isset($bodyArr['booth_size'])) {
        $updateFields[] = 'booth_size = ?';
        $params[] = $bodyArr['booth_size'];
        $types .= 's';
    }

    if (isset($bodyArr['booth_status'])) {
        $updateFields[] = 'booth_status = ?';
        $params[] = $bodyArr['booth_status'];
        $types .= 's';
    }

    if (isset($bodyArr['price'])) {
        $updateFields[] = 'price = ?';
        $params[] = $bodyArr['price'];
        $types .= 'd'; // สำหรับ float หรือ decimal
    }

    if (isset($bodyArr['image_url'])) {
        $updateFields[] = 'image_url = ?';
        $params[] = $bodyArr['image_url'];
        $types .= 's';
    }

    if (isset($bodyArr['zone_id'])) {
        $updateFields[] = 'zone_id = ?';
        $params[] = $bodyArr['zone_id'];
        $types .= 'i'; // สำหรับ integer
    }

    // เพิ่มเงื่อนไขสำหรับ booth_id
    $sql = "UPDATE booths SET " . implode(', ', $updateFields) . " WHERE booth_id = ?";
    $params[] = $bodyArr['booth_id'];
    $types .= 'i';

    // เตรียมคำสั่ง SQL สำหรับ update ข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    // ดำเนินการคำสั่ง SQL
    $stmt->execute();
    $result = $stmt->affected_rows; // ตรวจสอบจำนวนแถวที่ถูกแก้ไข

    // ส่งผลลัพธ์กลับไปยัง client
    $response->getBody()->write(json_encode(["rows_affected" => $result]));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/booths/delete', function (Request $request, Response $response, array $args) {
    // รับข้อมูล JSON ที่ส่งมา
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['booth_id'])) {
        $error = ['error' => 'กรุณาระบุรหัสบูธ'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // เชื่อมต่อฐานข้อมูล
    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับลบข้อมูล
    $stmt = $conn->prepare("DELETE FROM booths WHERE booth_id = ?");
    $stmt->bind_param("i", $bodyArr['booth_id']);

    // ดำเนินการคำสั่ง SQL
    $stmt->execute();
    $result = $stmt->affected_rows; // ตรวจสอบจำนวนแถวที่ถูกลบ

    // ส่งผลลัพธ์กลับไปยัง client
    $response->getBody()->write(json_encode(["rows_affected" => $result]));

    return $response->withHeader('Content-Type', 'application/json');
});

//ตรวจสอบบูธที่ว่าง และจำนวนบูธที่ผู้ใช้ๅ คนทำการจอง
$app->post('/booths/book/{booth_id}', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS['conn'];
    $boothId = $args['booth_id'];
    
    // ดึงข้อมูล user_id จาก request body
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array
    $userId = $bodyArr['user_id'];
    
    if (!isset($userId)) {
        $error = ['error' => 'กรุณาระบุ user_id'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // ตรวจสอบจำนวนบูธที่ผู้ใช้คนนี้จองไว้
    $stmt = $conn->prepare("
        SELECT COUNT(*) as booth_count 
        FROM Reservations 
        WHERE user_id = ? AND status IN ('under_review', 'booked')
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $countData = $result->fetch_assoc();

    if ($countData['booth_count'] >= 4) {
        $response->getBody()->write(json_encode(["error" => "ผู้ใช้นี้จองบูธครบ 4 บูธแล้ว ไม่สามารถจองเพิ่มได้"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // ตรวจสอบสถานะบูธ
    $stmt = $conn->prepare("
        SELECT booth_status 
        FROM Booths 
        WHERE booth_id = ?
    ");
    $stmt->bind_param("i", $boothId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booth = $result->fetch_assoc();

    if (!$booth) {
        $response->getBody()->write(json_encode(["error" => "ไม่พบบูธที่มี ID นี้"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // ตรวจสอบสถานะของบูธ
    if ($booth['booth_status'] !== 'available') {
        $response->getBody()->write(json_encode(["error" => "บูธนี้ไม่ว่าง ไม่สามารถจองได้ (สถานะ: " . $booth['booth_status'] . ")"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // เริ่มการจองบูธ
    $conn->begin_transaction();

    try {
        // อัปเดตสถานะบูธเป็น "under_review"
        $stmt = $conn->prepare("UPDATE Booths SET booth_status = 'under_review' WHERE booth_id = ?");
        $stmt->bind_param("i", $boothId);
        $stmt->execute();

        // เพิ่มการจองในตาราง Reservations
        $stmt = $conn->prepare("INSERT INTO Reservations (user_id, booth_id, reservation_date, status, payment_status, payment_proof) 
                                VALUES (?, ?, ?, ?, ?, ?)");

        $status = 'under_review';  // สถานะการจองคือ under_review ในขณะรอตรวจสอบ
        $paymentProof = isset($bodyArr['payment_proof']) ? $bodyArr['payment_proof'] : null;  // หลักฐานการชำระเงิน (ถ้ามี)

        $stmt->bind_param("iissss", 
            $userId,                            // รหัสผู้ใช้
            $boothId,                           // รหัสบูธ
            $bodyArr['reservation_date'],       // วันที่จอง
            $status,                            // สถานะการจอง
            $bodyArr['payment_status'],         // สถานะการชำระเงิน
            $paymentProof                       // หลักฐานการชำระเงิน (URL หรือไฟล์)
        );
        $stmt->execute();

        // ยืนยันการเปลี่ยนแปลง
        $conn->commit();
        $response->getBody()->write(json_encode(["message" => "จองบูธสำเร็จ บูธถูกเปลี่ยนเป็นสถานะ 'under_review'"]));

    } catch (Exception $e) {
        // ยกเลิกการเปลี่ยนแปลงหากเกิดข้อผิดพลาด
        $conn->rollback();
        $response->getBody()->write(json_encode(["error" => "เกิดข้อผิดพลาด: " . $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    return $response->withHeader('Content-Type', 'application/json');
});