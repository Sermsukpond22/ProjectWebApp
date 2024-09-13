<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// โหลด autoload ของ Composer
require __DIR__ . '/vendor/autoload.php';


// สร้างแอปพลิเคชัน Slim
$app = AppFactory::create();
$app->setBasePath('/ProjectWebapp2');


require __DIR__ . '/dbconnect.php';
require __DIR__ . '/api/users.php';
require __DIR__ . '/api/zones.php';
require __DIR__ . '/api/booths.php';
require __DIR__ . '/api/Reservations.php';
require __DIR__ . '/api/Events.php';
// รันแอปพลิเคชัน
$app->run();
