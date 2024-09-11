<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


// Route: ดึงข้อมูลโซนทั้งหมด
$app->get('/zones', function (Request $request, Response $response, array $args) {
    // เชื่อมต่อฐานข้อมูล
    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับดึงข้อมูลจากตาราง Zones
    $stmt = $conn->prepare("SELECT * FROM Zones");
    $stmt->execute();

    // ดึงผลลัพธ์
    $result = $stmt->get_result();
    $zones = array();

    // แปลงผลลัพธ์เป็น associative array
    while ($row = $result->fetch_assoc()) {
        array_push($zones, $row);
    }

    // เขียนผลลัพธ์ไปยัง response
    $response->getBody()->write(json_encode($zones));

    // ส่งกลับข้อมูลเป็น JSON
    return $response->withHeader('Content-Type', 'application/json');
});

// Route: เพิ่มข้อมูลโซนใหม่
$app->post('/zones/insert', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['zone_name'], $bodyArr['zone_info'], $bodyArr['number_of_booths'])) {
        $error = ['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    // เชื่อมต่อฐานข้อมูล
    $conn = $GLOBALS['conn']; 
    $stmt = $conn->prepare("INSERT INTO Zones (zone_name, zone_info, number_of_booths) VALUES (?, ?, ?)");

    // bind ค่าจาก array ที่รับมาเป็น parameter
    $stmt->bind_param("ssi", 
        $bodyArr['zone_name'],   // ชื่อโซน
        $bodyArr['zone_info'],   // ข้อมูลเกี่ยวกับโซน
        $bodyArr['number_of_booths'] // จำนวนบูธในโซนนี้
    );

    // ดำเนินการคำสั่ง SQL
    $stmt->execute();
    $result = $stmt->affected_rows; // ตรวจสอบจำนวนแถวที่ถูกเพิ่ม

    // ส่งผลลัพธ์กลับไปยัง client
    $response->getBody()->write(json_encode(["rows_affected" => $result]));

    return $response->withHeader('Content-Type', 'application/json');
});

// Route: แก้ไขข้อมูลโซน
$app->put('/zones/update', function (Request $request, Response $response, array $args) {
    // รับข้อมูล JSON ที่ส่งมา
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['zone_id'])) {
        $error = ['error' => 'กรุณาระบุรหัสโซน'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // เชื่อมต่อฐานข้อมูล
    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับ update ข้อมูลในตาราง zones
    $updateFields = [];
    $params = [];
    $types = '';

    if (isset($bodyArr['zone_name'])) {
        $updateFields[] = 'zone_name = ?';
        $params[] = $bodyArr['zone_name'];
        $types .= 's';
    }

    if (isset($bodyArr['zone_info'])) {
        $updateFields[] = 'zone_info = ?';
        $params[] = $bodyArr['zone_info'];
        $types .= 's';
    }

    if (isset($bodyArr['number_of_booths'])) {
        $updateFields[] = 'number_of_booths = ?';
        $params[] = $bodyArr['number_of_booths'];
        $types .= 'i';
    }

    $sql = "UPDATE Zones SET " . implode(', ', $updateFields) . " WHERE zone_id = ?";
    $params[] = $bodyArr['zone_id'];
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

// Route: ลบข้อมูลโซน
$app->delete('/zones/delete', function (Request $request, Response $response, array $args) {
    // รับข้อมูล JSON ที่ส่งมา
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['zone_name'])) {
        $error = ['error' => 'กรุณาระบุชื่อโซน'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // เชื่อมต่อฐานข้อมูล
    $conn = $GLOBALS['conn'];

    // เริ่มต้น Transaction
    $conn->begin_transaction();

    try {
        // ลบข้อมูลจากตาราง booths ที่อ้างอิงถึงโซนที่ต้องการลบ
        $stmt = $conn->prepare("DELETE FROM booths WHERE zone_id = (SELECT zone_id FROM zones WHERE zone_name = ?)");
        $stmt->bind_param("s", $bodyArr['zone_name']);
        $stmt->execute();

        // ลบข้อมูลจากตาราง zones
        $stmt = $conn->prepare("DELETE FROM zones WHERE zone_name = ?");
        $stmt->bind_param("s", $bodyArr['zone_name']);
        $stmt->execute();

        $rowsAffected = $stmt->affected_rows;
        $conn->commit();
        $response->getBody()->write(json_encode(["rows_affected" => $rowsAffected]));

    } catch (Exception $e) {
    
        $conn->rollback();
        $response->getBody()->write(json_encode(["error" => "เกิดข้อผิดพลาด: " . $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    return $response->withHeader('Content-Type', 'application/json');
});