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
    // รับข้อมูล JSON ที่ส่งมา
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


$app->put('/booths/update/{booth_id}', function (Request $request, Response $response, array $args) {
    $boothId = $args['booth_id'];
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['booth_name'], $bodyArr['booth_size'], $bodyArr['booth_status'], $bodyArr['price'], $bodyArr['image_url'], $bodyArr['zone_id'])) {
        $error = ['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับอัปเดตข้อมูลในตาราง booths
    $stmt = $conn->prepare("UPDATE Booths SET booth_name = ?, booth_size = ?, booth_status = ?, price = ?, image_url = ?, zone_id = ? WHERE booth_id = ?");
    $stmt->bind_param("ssssssi", 
        $bodyArr['booth_name'], 
        $bodyArr['booth_size'], 
        $bodyArr['booth_status'], 
        $bodyArr['price'], 
        $bodyArr['image_url'], 
        $bodyArr['zone_id'], 
        $boothId
    );

    $stmt->execute();
    $result = $stmt->affected_rows;

    $response->getBody()->write(json_encode(["rows_affected" => $result]));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/booths/delete/{booth_id}', function (Request $request, Response $response, array $args) {
    $boothId = $args['booth_id'];

    // ตรวจสอบว่ามีค่า $boothId
    if (!$boothId) {
        $error = ['error' => 'กรุณาระบุรหัสบูธ'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับลบข้อมูลจากตาราง booths
    $stmt = $conn->prepare("DELETE FROM Booths WHERE booth_id = ?");
    $stmt->bind_param("i", $boothId);

    $stmt->execute();
    $result = $stmt->affected_rows;

    $response->getBody()->write(json_encode(["rows_affected" => $result]));

    return $response->withHeader('Content-Type', 'application/json');
});

