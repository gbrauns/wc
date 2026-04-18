<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$file = __DIR__ . '/scores.json';

if (!file_exists($file)) {
    file_put_contents($file, '[]');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo file_get_contents($file);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['score']) || !is_numeric($input['score'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid score']);
        exit;
    }

    $name = isset($input['name']) ? substr(trim($input['name']), 0, 20) : 'Nezināmais';
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $score = (int) $input['score'];

    $scores = json_decode(file_get_contents($file), true);
    $scores[] = [
        'name' => $name,
        'score' => $score,
        'date' => date('d.m.Y')
    ];

    usort($scores, fn($a, $b) => $b['score'] - $a['score']);
    $scores = array_slice($scores, 0, 10);

    file_put_contents($file, json_encode($scores, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'scores' => $scores]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
