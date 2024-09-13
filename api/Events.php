<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


// Route: เพิ่มข้อมูลการจัดงานใหม่
$app->post('/events/insert', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); 
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['event_name'], $bodyArr['start_date'], $bodyArr['end_date'])) {
        $error = ['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['conn'];
    
    // เตรียมคำสั่ง SQL สำหรับ insert ข้อมูลลงในตาราง events
    $stmt = $conn->prepare("INSERT INTO Events (event_name, start_date, end_date) VALUES (?, ?, ?)");

    $stmt->bind_param("sss", 
        $bodyArr['event_name'],      // ชื่องาน
        $bodyArr['start_date'],      // วันที่จัดงาน
        $bodyArr['end_date']         // วันสิ้นสุดการจัดงาน
    );

    $stmt->execute();
    $result = $stmt->affected_rows; // ตรวจสอบจำนวนแถวที่ถูกเพิ่ม

    $response->getBody()->write(json_encode(["rows_affected" => $result]));

    return $response->withHeader('Content-Type', 'application/json');
});


// Route: อัปเดตข้อมูลการจัดงาน
$app->put('/events/update', function (Request $request, Response $response, array $args) {
    // รับข้อมูล JSON ที่ส่งมา
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['event_id'])) {
        $error = ['error' => 'กรุณาระบุรหัสจัดงาน'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['conn'];

    $updateFields = [];
    $params = [];
    $types = '';

    if (isset($bodyArr['event_name'])) {
        $updateFields[] = 'event_name = ?';
        $params[] = $bodyArr['event_name'];
        $types .= 's';
    }

    if (isset($bodyArr['start_date'])) {
        $updateFields[] = 'start_date = ?';
        $params[] = $bodyArr['start_date'];
        $types .= 's';
    }

    if (isset($bodyArr['end_date'])) {
        $updateFields[] = 'end_date = ?';
        $params[] = $bodyArr['end_date'];
        $types .= 's';
    }

    // เพิ่มเงื่อนไขสำหรับ event_id
    $sql = "UPDATE Events SET " . implode(', ', $updateFields) . " WHERE event_id = ?";
    $params[] = $bodyArr['event_id'];
    $types .= 'i';

    // เตรียมคำสั่ง SQL สำหรับ update ข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->affected_rows; 

    $response->getBody()->write(json_encode(["rows_affected" => $result]));

    return $response->withHeader('Content-Type', 'application/json');
});
