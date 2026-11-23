<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/database_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
    exit;
}

$input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

$name     = trim((string) ($input['name'] ?? ''));
$email    = strtolower(trim((string) ($input['email'] ?? '')));
$phone    = trim((string) ($input['phone'] ?? ''));
$college  = trim((string) ($input['college'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($name === '' || $email === '' || $phone === '' || $password === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Name, email, phone, and password are required.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 6 characters long.'
    ]);
    exit;
}

try {
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkStmt->execute([':email' => $email]);

    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'An account with this email already exists.'
        ]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, phone, college)
         VALUES (:name, :email, :password, :phone, :college)'
    );
    $insertStmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':password' => $passwordHash,
        ':phone'    => $phone,
        ':college'  => $college !== '' ? $college : null,
    ]);

    $userId = (int) $pdo->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully.',
        'user'    => [
            'id'      => $userId,
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'college' => $college !== '' ? $college : null,
        ],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error while creating account.'
    ]);
}