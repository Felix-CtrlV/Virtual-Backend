<?php
header('Content-Type: application/json');

// Ensure this path is correct relative to where this file is located
include("../../../BackEnd/config/dbconfig.php");

$sql = "SELECT 
    s.supplier_id,
    c.company_name,
    s.name AS supplier_name,
    c.description,
    c.status AS company_status,
    sa.logo,
    sa.banner
FROM suppliers s
INNER JOIN companies c 
    ON c.supplier_id = s.supplier_id
LEFT JOIN shop_assets sa 
    ON sa.supplier_id = s.supplier_id
WHERE s.status = 'active'
  AND c.status = 'active'
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
        // Adjust this path if necessary to match your actual folder structure
        $logoUrl = "../uploads/shops/{$supplierId}/" . $row['logo'];
    }

    $bannerUrl = null;
    if (!empty($row['banner'])) {
        // Adjust this path if necessary
        $bannerUrl = "../uploads/shops/{$supplierId}/" . $row['banner'];
    }

    $suppliers[] = [
        'supplier_id' => $supplierId,
        'company_name' => $row['company_name'],
        // FIX: Use the alias 'supplier_name' instead of 'name'
        'owner_name' => $row['supplier_name'], 
        'description' => $row['description'],
        'logo_url' => $logoUrl,
        'banner_url' => $bannerUrl
    ];
}

echo json_encode([
    'success' => true,
    'suppliers' => $suppliers
]);
?>