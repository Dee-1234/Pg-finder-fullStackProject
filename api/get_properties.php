<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/database_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
    exit;
}

$city     = trim((string) ($_GET['city'] ?? ''));
$gender   = trim((string) ($_GET['gender'] ?? ''));
$maxPrice = (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) ? (int) $_GET['max_price'] : 0;
$userId   = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

$sql = '
    SELECT
        p.id,
        p.name,
        p.city,
        p.address,
        p.price,
        p.gender,
        p.rating,
        p.description,
        p.image_url,
        COUNT(DISTINCT iu_all.user_id) AS interested_count,
        MAX(CASE WHEN iu_me.user_id = :current_user_id THEN 1 ELSE 0 END) AS is_interested
    FROM properties p
    LEFT JOIN interested_users iu_all
        ON iu_all.property_id = p.id
    LEFT JOIN interested_users iu_me
        ON iu_me.property_id = p.id AND iu_me.user_id = :current_user_id_join
    WHERE 1 = 1
';

$params = [
    ':current_user_id'      => $userId,
    ':current_user_id_join' => $userId,
];

if ($city !== '') {
    $sql .= ' AND LOWER(p.city) LIKE LOWER(:city)';
    $params[':city'] = '%' . $city . '%';
}

if (in_array($gender, ['Male', 'Female', 'Unisex'], true)) {
    $sql .= ' AND p.gender = :gender';
    $params[':gender'] = $gender;
}

if ($maxPrice > 0) {
    $sql .= ' AND p.price <= :max_price';
    $params[':max_price'] = $maxPrice;
}

$sql .= ' GROUP BY p.id ORDER BY p.rating DESC, p.id ASC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $properties = array_map(static function (array $row): array {
        return [
            'id'               => (int) $row['id'],
            'name'             => (string) $row['name'],
            'city'             => (string) $row['city'],
            'address'          => (string) $row['address'],
            'price'            => (int) $row['price'],
            'gender'           => (string) $row['gender'],
            'rating'           => (float) $row['rating'],
            'description'      => (string) $row['description'],
            'image_url'        => (string) $row['image_url'],
            'interested_count' => (int) $row['interested_count'],
            'is_interested'    => (bool) $row['is_interested'],
        ];
    }, $rows);

    echo json_encode([
        'success'    => true,
        'count'      => count($properties),
        'logged_in'  => $userId > 0,
        'properties' => $properties,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error while fetching properties.'
    ]);
}