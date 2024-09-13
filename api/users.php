<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


//Get Users
$app->get('/users', function (Request $request, Response $response, array $args) {

    $conn = $GLOBALS['conn'];
    $stmt = $conn->prepare("SELECT * FROM users");
    $stmt->execute();

    $result = $stmt->get_result();
    $users = array();

    while ($row = $result->fetch_assoc()) {
        array_push($users, $row);
    }
    $response->getBody()->write(json_encode($users));
    return $response->withHeader('Content-Type', 'application/json');
});

//Insert
$app->post('/users/insert', function (Request $request, Response $response, array $args) {

    $body = $request->getBody(); 
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array
    $conn = $GLOBALS['conn'];
    
    $stmt = $conn->prepare("INSERT INTO users " .
        "(fname,lname, phone, email, password, role) " .
        "VALUES (?, ?, ?, ?, ?, ?)");

    $hashedPassword = password_hash($bodyArr['password'], PASSWORD_DEFAULT);

    // bind ค่าจาก array ที่รับมาเป็น parameter
    $stmt->bind_param("ssssss", 
        $bodyArr['fname'],  
        $bodyArr['lname'],        
        $bodyArr['phone'],        
        $bodyArr['email'],      
        $hashedPassword,         
        $bodyArr['role']          
    );

    // ดำเนินการคำสั่ง SQL
    $stmt->execute();
    $result = $stmt->affected_rows; 
    $response->getBody()->write(json_encode(["rows_affected" => $result]));

    return $response->withHeader('Content-Type', 'application/json');
});

//login user
$app->post('/users/login', function (Request $request, Response $response, array $args) {

    $body = $request->getBody();
    $bodyArr = json_decode($body, true); // แปลงข้อมูล JSON เป็น associative array

    // เชื่อมต่อฐานข้อมูล
    $conn = $GLOBALS['conn'];

    // เตรียมคำสั่ง SQL สำหรับตรวจสอบ email ของผู้ใช้
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $bodyArr['email']); // bind email ที่รับมาจาก body
    $stmt->execute();

    // ดึงผลลัพธ์จากการ query
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // ตรวจสอบว่าเจอผู้ใช้หรือไม่
    if ($user) {
        if (password_verify($bodyArr['password'], $user['password'])) {
            $response->getBody()->write(json_encode([
                "status" => "success",
                "message" => "เข้าสู่ระบบสำเร็จ",
                "user" => [
                    "id" => $user['user_id'],
                    "fname" => $user['fname'],
                    "lname" => $user['lname'],
                    "role" => $user['role']
                ]
            ]));
        } else {
            $response->getBody()->write(json_encode([
                "status" => "error",
                "message" => "รหัสผ่านไม่ถูกต้อง"
            ]));
        }
    } else {
        $response->getBody()->write(json_encode([
            "status" => "error",
            "message" => "ไม่พบผู้ใช้ที่มี email นี้"
        ]));
    }

    return $response->withHeader('Content-Type', 'application/json');
});

//users members
$app->get('/users/members', function (Request $request, Response $response, array $args) {
    // เชื่อมต่อฐานข้อมูล
    $conn = $GLOBALS['conn'];

    // สร้าง SQL สำหรับดึงข้อมูลสมาชิก
    $sql = "SELECT fname, lname, phone, email FROM users WHERE role = 'member'";

    // เตรียมคำสั่ง SQL
    $stmt = $conn->prepare($sql);
    
    // ดำเนินการคำสั่ง SQL
    $stmt->execute();
    
    // ดึงผลลัพธ์จากฐานข้อมูล
    $result = $stmt->get_result();

    // สร้าง array สำหรับเก็บข้อมูล
    $members = [];

    while ($row = $result->fetch_assoc()) {
        $members[] = $row;  // เพิ่มผลลัพธ์ใน array
    }

    // ส่งผลลัพธ์กลับไปในรูปแบบ JSON
    $response->getBody()->write(json_encode($members));

    return $response->withHeader('Content-Type', 'application/json');
});


 //ผู้ใช้แก้ไขข้อมูส่วนตัว
$app->put('/users/{id}/update', function (Request $request, Response $response, array $args) { 
    $conn = $GLOBALS['conn'];

    // รับค่า user_id จาก URL
    $user_id_from_url = $args['id'];

    // รับข้อมูลจาก body
    $body = $request->getBody();
    $bodyArr = json_decode($body, true);

    // ตรวจสอบว่ามีการส่งข้อมูลที่จำเป็นมาหรือไม่
    if (!isset($bodyArr['first_name'], $bodyArr['last_name'], $bodyArr['phone'], $bodyArr['email'], $bodyArr['password'])) {
        $response->getBody()->write(json_encode(["error" => "กรุณาระบุข้อมูลให้ครบถ้วน"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $first_name = $bodyArr['first_name'];
    $last_name = $bodyArr['last_name'];
    $phone = $bodyArr['phone'];
    $email = $bodyArr['email'];
    $password = password_hash($bodyArr['password'], PASSWORD_DEFAULT); // เข้ารหัสรหัสผ่านใหม่

    // ตรวจสอบว่าผู้ใช้มีบทบาทเป็น member หรือไม่
    $stmt = $conn->prepare("SELECT * FROM Users WHERE user_id = ? AND role = 'member'");
    $stmt->bind_param("i", $user_id_from_url);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $response->getBody()->write(json_encode(["error" => "ไม่พบผู้ใช้หรือสิทธิ์ไม่เพียงพอ"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // ตรวจสอบว่าอีเมลซ้ำกันหรือไม่ (ยกเว้นผู้ใช้ปัจจุบัน)
    $stmt = $conn->prepare("SELECT * FROM Users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id_from_url);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $response->getBody()->write(json_encode(["error" => "อีเมลนี้ถูกใช้ไปแล้ว"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
    }

    // อัปเดตข้อมูลส่วนตัว
    $stmt = $conn->prepare("UPDATE Users SET fname = ?, lname = ?, phone = ?, email = ?, password = ? WHERE user_id = ?");
    $stmt->bind_param("sssssi", $first_name, $last_name, $phone, $email, $password, $user_id_from_url);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response->getBody()->write(json_encode(["message" => "แก้ไขข้อมูลสำเร็จ"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "ไม่สามารถแก้ไขข้อมูลได้"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

//แสดงการจอง ของ members ที่ login เข้ามา
$app->get('/users/{id}/reservations', function (Request $request, Response $response, array $args) { 
    $conn = $GLOBALS['conn'];

    // รับค่า user_id จาก URL
    $user_id_from_url = $args['id'];

    // ดึงข้อมูลการจองของผู้ใช้จากฐานข้อมูล
    $stmt = $conn->prepare("
        SELECT 
            b.booth_name, 
            z.zone_name, 
            r.payment_status, 
            r.status, 
            b.price
        FROM 
            Reservations r
        JOIN 
            Booths b ON r.booth_id = b.booth_id
        JOIN 
            Zones z ON b.zone_id = z.zone_id
        WHERE 
            r.user_id = ?");
    $stmt->bind_param("i", $user_id_from_url);
    $stmt->execute();
    $reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (count($reservations) > 0) {
        $response->getBody()->write(json_encode($reservations));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["message" => "ไม่พบข้อมูลการจอง"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
});