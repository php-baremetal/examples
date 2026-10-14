<?php
// A tiny JSON API in the web-server model: this runs fresh for every request, behind the firmware's
// HTTP server. Route on $_SERVER, return JSON. Point a browser or curl at http://<board-ip>/.

header('Content-Type: application/json');

$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$reply = match (true) {
    $method === 'GET' && $path === '/' => [
        'service' => 'php-baremetal json-api',
        'php'     => PHP_VERSION,
        'routes'  => ['GET /', 'GET /ping', 'POST /echo'],
    ],
    $method === 'GET' && $path === '/ping' => [
        'pong' => true,
        'rand' => random_int(1000, 9999),
    ],
    $method === 'POST' && $path === '/echo' => [
        'you_sent' => json_decode(file_get_contents('php://input') ?: 'null', true),
    ],
    default => null,
};

if ($reply === null) {
    http_response_code(404);
    $reply = ['error' => 'not found', 'path' => $path];
}

echo json_encode($reply, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
