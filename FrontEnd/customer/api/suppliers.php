<?php
header('Content-Type: application/json');

include("../../../BackEnd/config/dbconfig.php");

$sql = "SELECT s.supplier_id, s.company_name, s.name, s.description, s.status, sa.logo, sa.banner
        FROM suppliers s
        LEFT JOIN shop_assets sa ON sa.supplier_id = s.supplier_id
        WHERE s.status = 'active'
        ORDER BY s.supplier_id ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load suppliers.'
    ]);
    exit;
}

$suppliers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $supplierId = (int) $row['supplier_id'];

    $logoUrl = null;
    if (!empty($row['logo'])) {
        $logoUrl = "../uploads/shops/{$supplierId}/" . $row['logo'];
    }

    $bannerUrl = null;
    if (!empty($row['banner'])) {
        $bannerUrl = "../uploads/shops/{$supplierId}/" . $row['banner'];
    }

    $suppliers[] = [
        'supplier_id' => $supplierId,
        'company_name' => $row['company_name'],
        'owner_name' => $row['name'],
        'description' => $row['description'],
        'logo_url' => $logoUrl,
        'banner_url' => $bannerUrl
    ];
}

echo json_encode([
    'success' => true,
    'suppliers' => $suppliers
]);
