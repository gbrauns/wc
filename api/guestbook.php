<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$file = __DIR__ . '/guestbook.json';

if (!file_exists($file)) {
    file_put_contents($file, '[]');
}

$entries = json_decode(file_get_contents($file), true);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    usort($entries, function ($a, $b) {
        return strtotime(str_replace(
            ['.', ' '],
            ['-', ' '],
            preg_replace('/(\d{2})\.(\d{2})\.(\d{4})/', '$3-$2-$1', $b['created_at'])
        )) - strtotime(str_replace(
            ['.', ' '],
            ['-', ' '],
            preg_replace('/(\d{2})\.(\d{2})\.(\d{4})/', '$3-$2-$1', $a['created_at'])
        ));
    });
    echo json_encode($entries, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['name']) || trim($input['name']) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Vārds ir obligāts']);
        exit;
    }

    $name = substr(trim($input['name']), 0, 20);
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $comment = isset($input['comment']) ? substr(trim($input['comment']), 0, 500) : '';
    $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

    foreach ($entries as $entry) {
        if (mb_strtolower($entry['name']) === mb_strtolower($name)) {
            echo json_encode(['exists' => true, 'entry' => $entry]);
            exit;
        }
    }

    $entries[] = [
        'name' => $name,
        'comment' => $comment,
        'created_at' => date('d.m.Y H:i:s'),
    ];

    file_put_contents($file, json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['name']) || trim($input['name']) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Vārds ir obligāts']);
        exit;
    }

    $name = substr(trim($input['name']), 0, 20);
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $comment = isset($input['comment']) ? substr(trim($input['comment']), 0, 500) : '';
    $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

    $found = false;
    foreach ($entries as &$entry) {
        if (mb_strtolower($entry['name']) === mb_strtolower($name)) {
            $entry['comment'] = $comment;
            $entry['updated_at'] = date('d.m.Y H:i:s');
            $found = true;
            break;
        }
    }
    unset($entry);

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Ieraksts nav atrasts']);
        exit;
    }

    file_put_contents($file, json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
