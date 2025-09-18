<?php
// php-api/index.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/SocialNetworkAPI.php';

use App\SocialNetworkAPI;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$api = new SocialNetworkAPI();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');
$segments = explode('/', $path);

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($api, $segments);
            break;
        case 'POST':
            handlePostRequest($api, $segments);
            break;
        case 'DELETE':
            handleDeleteRequest($api, $segments);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($api, $segments) {
    switch ($segments[0] ?? '') {
        case '':
        case 'status':
            echo json_encode(['status' => 'API is running', 'timestamp' => date('Y-m-d H:i:s')]);
            break;
        case 'users':
            if (isset($segments[1])) {
                // Get specific user
                $user = $api->getUser($segments[1]);
                echo json_encode($user);
            } else {
                // Get all users
                $users = $api->getAllUsers();
                echo json_encode($users);
            }
            break;
        case 'friends':
            if (isset($segments[1])) {
                $friends = $api->getFriends($segments[1]);
                echo json_encode($friends);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'User ID required']);
            }
            break;
        case 'recommendations':
            if (isset($segments[1])) {
                $recommendations = $api->getFriendRecommendations($segments[1]);
                echo json_encode($recommendations);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'User ID required']);
            }
            break;
        case 'path':
            if (isset($segments[1]) && isset($segments[2])) {
                $path = $api->getShortestPath($segments[1], $segments[2]);
                echo json_encode($path);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Two user IDs required']);
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handlePostRequest($api, $segments) {
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($segments[0] ?? '') {
        case 'users':
            $user = $api->createUser($input['name'], $input['email'] ?? '', $input['age'] ?? null);
            echo json_encode($user);
            break;
        case 'friendship':
            $friendship = $api->createFriendship($input['user1'], $input['user2']);
            echo json_encode($friendship);
            break;
        case 'seed':
            $result = $api->seedSampleData();
            echo json_encode($result);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($api, $segments) {
    switch ($segments[0] ?? '') {
        case 'friendship':
            if (isset($segments[1]) && isset($segments[2])) {
                $result = $api->deleteFriendship($segments[1], $segments[2]);
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Two user IDs required']);
            }
            break;
        case 'clear':
            $result = $api->clearDatabase();
            echo json_encode($result);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}