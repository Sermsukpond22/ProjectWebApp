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
