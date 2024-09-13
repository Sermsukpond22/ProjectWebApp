<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/reservations', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับดึงข้อมูลจากตาราง reservations พร้อมกับชื่อและนามสกุลจาก users
    $stmt = $conn->prepare("
        SELECT r.reservation_id, r.reservation_date, r.status, r.payment_status, r.payment_proof, 
               u.fname, u.lname, b.booth_name 
        FROM Reservations r 
        JOIN Users u ON r.user_id = u.user_id 
        JOIN Booths b ON r.booth_id = b.booth_id
    ");
    $stmt->execute();

    $result = $stmt->get_result();
    $reservations = array();
    
    while ($row = $result->fetch_assoc()) {
        array_push($reservations, $row);
    }

    $response->getBody()->write(json_encode($reservations));
    return $response->withHeader('Content-Type', 'application/json');
});

//เพิ่มการจอง
$app->post('/reservations/insert', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($bodyArr['user_id'], $bodyArr['booth_id'], $bodyArr['reservation_date'], $bodyArr['status'], $bodyArr['payment_status'])) {
        $error = ['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $conn = $GLOBALS['conn'];
    
    // เตรียมคำสั่ง SQL สำหรับ insert ข้อมูลลงในตาราง reservations
    $stmt = $conn->prepare("INSERT INTO Reservations (user_id, booth_id, reservation_date, status, payment_status, payment_proof) 
                            VALUES (?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("iissss", 
        $bodyArr['user_id'],           // รหัสผู้ใช้
        $bodyArr['booth_id'],          // รหัสบูธ
        $bodyArr['reservation_date'],  // วันที่จอง
        $bodyArr['status'],            // สถานะการจอง
        $bodyArr['payment_status'],    // สถานะการชำระเงิน
        $bodyArr['payment_proof']      // หลักฐานการชำระเงิน (URL หรือไฟล์)
    );

    $stmt->execute();
    $result = $stmt->affected_rows;

    // ส่งผลลัพธ์กลับไปยัง client
    $response->getBody()->write(json_encode(["rows_affected" => $result]));
    return $response->withHeader('Content-Type', 'application/json');
});

//ดึงข้อมูลการจองที่สถานะการจองเป็น "booked" และยังไม่ได้ชำระเงิน
$app->get('/reservations/unpaid', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับดึงข้อมูลผู้ที่ยังไม่ชำระเงิน
    $stmt = $conn->prepare("
        SELECT u.fname, u.lname, u.phone, b.booth_name, z.zone_name 
        FROM Reservations r
        JOIN Users u ON r.user_id = u.user_id
        JOIN Booths b ON r.booth_id = b.booth_id
        JOIN Zones z ON b.zone_id = z.zone_id
        WHERE r.status = 'reserve' AND r.payment_status = 'unpaid'
    ");

    $stmt->execute();

    $result = $stmt->get_result();
    $unpaidReservations = array();

    while ($row = $result->fetch_assoc()) {
        array_push($unpaidReservations, $row);
    }

    $response->getBody()->write(json_encode($unpaidReservations));
    return $response->withHeader('Content-Type', 'application/json');
});


$app->get('/reservations/paid', function (Request $request, Response $response, array $args) {
 
    $conn = $GLOBALS['conn'];
    $stmt = $conn->prepare("
        SELECT r.reservation_id, r.reservation_date, u.fname, u.lname, u.phone, 
               b.booth_name, z.zone_name
        FROM Reservations r
        JOIN Users u ON r.user_id = u.user_id
        JOIN Booths b ON r.booth_id = b.booth_id
        JOIN Zones z ON b.zone_id = z.zone_id
         WHERE r.status = 'available' AND r.payment_status = 'paid'
    ");

    // ตรวจสอบว่าการเตรียมคำสั่ง SQL สำเร็จหรือไม่
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // ดำเนินการคำสั่ง SQL
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $paidReservations = array();
        while ($row = $result->fetch_assoc()) {
            array_push($paidReservations, $row);
        }
        $response->getBody()->write(json_encode($paidReservations));
    } else {

        $response->getBody()->write(json_encode(["message" => "No paid reservations found."]));
    }

    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/reservations/pending', function (Request $request, Response $response, array $args) {
    // เชื่อมต่อกับฐานข้อมูล
    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับดึงข้อมูลเฉพาะการจองที่มีสถานะ 'under_review'
    $stmt = $conn->prepare("
        SELECT  u.fname, u.lname, u.phone, b.booth_name, z.zone_name
        FROM Reservations r
        JOIN Users u ON r.user_id = u.user_id
        JOIN Booths b ON r.booth_id = b.booth_id
        JOIN Zones z ON b.zone_id = z.zone_id
        WHERE r.status = 'under_review' AND r.payment_status = 'pending'
    ");

    // ตรวจสอบว่าการเตรียมคำสั่ง SQL สำเร็จหรือไม่
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // ดำเนินการคำสั่ง SQL
    $stmt->execute();
    $result = $stmt->get_result();

    // ตรวจสอบว่ามีผลลัพธ์หรือไม่
    if ($result->num_rows > 0) {
        $underReviewReservations = array();
        while ($row = $result->fetch_assoc()) {
            array_push($underReviewReservations, $row);
        }
        // ส่งผลลัพธ์ในรูปแบบ JSON
        $response->getBody()->write(json_encode($underReviewReservations));
    } else {
        // กรณีไม่มีข้อมูลการจองที่รอตรวจสอบ
        $response->getBody()->write(json_encode(["message" => "No under_review reservations found."]));
    }

    return $response->withHeader('Content-Type', 'application/json');
});
 
//อนุมัติการจอง
$app->put('/reservations/approve', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS['conn'];

    // ดึงข้อมูลการจองที่มีสถานะการชำระเงินเป็น "paid"
    $stmt = $conn->prepare("
        SELECT reservation_id, booth_id 
        FROM Reservations 
        WHERE payment_status = 'paid' AND status = 'approve'
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $reservationsToApprove = array();

    while ($row = $result->fetch_assoc()) {
        $reservationsToApprove[] = $row;
    }

    if (empty($reservationsToApprove)) {
        $response->getBody()->write(json_encode(["message" => "ไม่มีการจองที่ต้องอนุมัติ"]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // เริ่มต้น Transaction
    $conn->begin_transaction();

    try {
        // อัปเดตสถานะการจองเป็น "approve" และสถานะบูธเป็น "booked"
        foreach ($reservationsToApprove as $reservation) {
            $reservationId = $reservation['reservation_id'];
            $boothId = $reservation['booth_id'];

            // อัปเดตสถานะการจอง
            $stmt = $conn->prepare("UPDATE Reservations SET status = 'approve' WHERE reservation_id = ?");
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();

            // อัปเดตสถานะบูธ
            $stmt = $conn->prepare("UPDATE Booths SET booth_status = 'booked' WHERE booth_id = ?");
            $stmt->bind_param("i", $boothId);
            $stmt->execute();
        }

        $conn->commit();
        $response->getBody()->write(json_encode(["message" => "การจองได้รับการอนุมัติและบูธได้รับการอัปเดต"]));

    } catch (Exception $e) {

        $conn->rollback();
        $response->getBody()->write(json_encode(["error" => "เกิดข้อผิดพลาด: " . $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->put('/reservations/cancel/{reservation_id}', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS['conn'];
    $reservationId = $args['reservation_id'];

    // ดึงข้อมูลบูธที่เชื่อมกับการจอง
    $stmt = $conn->prepare("
        SELECT booth_id 
        FROM Reservations 
        WHERE reservation_id = ?
    ");
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();

    if (!$reservation) {
        $response->getBody()->write(json_encode(["error" => "ไม่พบการจองที่มี ID นี้"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $boothId = $reservation['booth_id'];

    // เริ่มต้น Transaction
    $conn->begin_transaction();

    try {
        // อัปเดตสถานะการจองเป็น "failed"
        $stmt = $conn->prepare("UPDATE Reservations SET status = 'failed' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();

        // อัปเดตสถานะบูธเป็น "available"
        $stmt = $conn->prepare("UPDATE Booths SET booth_status = 'available' WHERE booth_id = ?");
        $stmt->bind_param("i", $boothId);
        $stmt->execute();

        // ยืนยันการเปลี่ยนแปลง
        $conn->commit();
        $response->getBody()->write(json_encode(["message" => "ยกเลิกการจองสำเร็จ และบูธถูกเปลี่ยนเป็นสถานะ 'available'"]));

    } catch (Exception $e) {
        // ยกเลิกการเปลี่ยนแปลงหากเกิดข้อผิดพลาด
        $conn->rollback();
        $response->getBody()->write(json_encode(["error" => "เกิดข้อผิดพลาด: " . $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/reservations/payment-check', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS['conn'];
    
    // รับค่า user_id และ event_id จาก body ของ request
    $body = $request->getBody();
    $bodyArr = json_decode($body, true);

    if (!isset($bodyArr['reservation_id'])) {
        $response->getBody()->write(json_encode(["error" => "กรุณาระบุ reservation_id"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $reservation_id = $bodyArr['reservation_id'];

    // ดึงข้อมูลการจองและงานจากฐานข้อมูล
    $stmt = $conn->prepare("
        SELECT r.booth_id, r.status, e.start_date
        FROM Reservations r
        JOIN Events e ON e.event_id = (SELECT event_id FROM Booths WHERE booth_id = r.booth_id)
        WHERE r.reservation_id = ?
    ");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();

    if (!$reservation) {
        $response->getBody()->write(json_encode(["error" => "ไม่พบการจองนี้"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $currentDate = new DateTime(); // วันที่ปัจจุบัน
    $startDate = new DateTime($reservation['start_date']); // วันที่เริ่มงาน
    $daysDifference = $startDate->diff($currentDate)->days; // หาความแตกต่างของวันที่

    // ตรวจสอบเงื่อนไขการชำระเงิน
    if ($daysDifference < 5) {
        // หากน้อยกว่า 5 วัน
        $responseText = "ชำระเงินไม่ได้";
        $newStatus = 'cancel_reserve';
        $newBoothStatus = 'available';
    } else {
        // หากมากกว่าหรือเท่ากับ 5 วัน
        $responseText = "ชำระเงินได้";
        $newStatus = 'reserve';
        $newBoothStatus = 'booked';
    }

    // อัปเดตสถานะการจองและบูธ
    $updateReservation = $conn->prepare("UPDATE Reservations SET status = ? WHERE reservation_id = ?");
    $updateReservation->bind_param("si", $newStatus, $reservation_id);
    $updateReservation->execute();

    $updateBooth = $conn->prepare("UPDATE Booths SET booth_status = ? WHERE booth_id = ?");
    $updateBooth->bind_param("si", $newBoothStatus, $reservation['booth_id']);
    $updateBooth->execute();

    $response->getBody()->write(json_encode([
        "message" => $responseText,
        "reservation_status" => $newStatus,
        "booth_status" => $newBoothStatus
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});