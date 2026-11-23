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

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success'       => false,
        'require_login' => true,
        'message'       => 'Please log in to shortlist properties.'
    ]);
    exit;
}

$jsonBody = json_decode(file_get_contents('php://input'), true);
$rawPropertyId = is_array($jsonBody) && isset($jsonBody['property_id'])
    ? $jsonBody['property_id']
    : ($_POST['property_id'] ?? 0);

$propertyId = filter_var($rawPropertyId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$userId = (int) $_SESSION['user_id'];

if ($propertyId === false) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Valid property_id is required.'
    ]);
    exit;
}

try {
    $propStmt = $pdo->prepare('SELECT id FROM properties WHERE id = :property_id LIMIT 1');
    $propStmt->execute([':property_id' => $propertyId]);
    if (!$propStmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Property not found.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    $checkStmt = $pdo->prepare(
        'SELECT 1
         FROM interested_users
         WHERE user_id = :user_id AND property_id = :property_id
         LIMIT 1'
    );
    $checkStmt->execute([
        ':user_id'     => $userId,
        ':property_id' => $propertyId,
    ]);

    $alreadyInterested = (bool) $checkStmt->fetchColumn();

    if ($alreadyInterested) {
        $delStmt = $pdo->prepare(
            'DELETE FROM interested_users
             WHERE user_id = :user_id AND property_id = :property_id'
        );
        $delStmt->execute([
            ':user_id'     => $userId,
            ':property_id' => $propertyId,
        ]);
        $interested = false;
    } else {
        $insStmt = $pdo->prepare(
            'INSERT INTO interested_users (user_id, property_id)
             VALUES (:user_id, :property_id)'
        );
        $insStmt->execute([
            ':user_id'     => $userId,
            ':property_id' => $propertyId,
        ]);
        $interested = true;
    }

    $countStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM interested_users WHERE property_id = :property_id'
    );
    $countStmt->execute([':property_id' => $propertyId]);
    $interestedCount = (int) $countStmt->fetchColumn();

    $pdo->commit();

    echo json_encode([
        'success'          => true,
        'property_id'      => $propertyId,
        'interested'       => $interested,
        'interested_count' => $interestedCount,
        'message'          => $interested ? 'Added to shortlist.' : 'Removed from shortlist.',
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error while updating interest status.'
    ]);
}