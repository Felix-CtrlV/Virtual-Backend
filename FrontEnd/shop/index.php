<?php
include '../../BackEnd/config/dbconfig.php';

$supplier_id = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;

if ($supplier_id <= 0) {
    die("Invalid supplier ID.");
}

$stmt = mysqli_prepare($conn, "
    SELECT s.*, t.template_folder 
    FROM suppliers s
    JOIN templates t ON s.template_id = t.template_id
    WHERE s.supplier_id = ?
    ");

if (!$stmt) {
    die("Database query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$supplier = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$supplier) {
    die("Supplier not found.");
}

$template_path = "../templates/" . $supplier['template_folder'] . "/index.php";

if (file_exists($template_path)) {
    include($template_path);
} else {
    die("Template not found: " . htmlspecialchars($template_path));
}
?>

<?php
if (!defined('PREVIEW_MODE')) {
    include '../../BackEnd/config/dbconfig.php';

    $supplier_id = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;
    if ($supplier_id <= 0)
        die("Invalid supplier ID.");

    $stmt = mysqli_prepare($conn, "
        SELECT s.*, t.template_folder 
        FROM suppliers s
        JOIN templates t ON s.template_id = t.template_id
        WHERE s.supplier_id = ?
    ");
    if (!$stmt)
        die("Database query preparation failed: " . mysqli_error($conn));

    mysqli_stmt_bind_param($stmt, "i", $supplier_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $supplier = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$supplier)
        die("Supplier not found.");
}
