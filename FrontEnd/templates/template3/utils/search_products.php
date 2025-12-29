<?php
include '../../BackEnd/dbConfig.php'; // Your DB credentials

$search = $_POST['search'] ?? '';
$supplier_id = $_POST['supplier_id'] ?? null;

if ($supplier_id) {
    // Use prepared statements to prevent SQL Injection
    $stmt = $conn->prepare("SELECT * FROM products WHERE supplier_id = ? AND product_name LIKE ?");
    $searchTerm = "%$search%";
    $stmt->bind_param("is", $supplier_id, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['product_name']) . "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='3'>No products found.</td></tr>";
    }
}?>