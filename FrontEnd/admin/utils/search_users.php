<?php
include("../../../BackEnd/config/dbconfig.php");

$search = $_POST['search'] ?? '';
$roleFilter = $_POST['role'] ?? 'all';
$statusFilter = $_POST['status'] ?? 'all';

$searchParam = "%$search%";
$conditions = ["(name LIKE ? OR email LIKE ?)"];
$params = [$searchParam, $searchParam];
$types = "ss";

if ($roleFilter !== 'all') {
    $conditions[] = "role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}
if ($statusFilter !== 'all') {
    $conditions[] = "LOWER(status) = ?";
    $params[] = strtolower($statusFilter);
    $types .= "s";
}

$where = implode(" AND ", $conditions);

$sql = "SELECT *
FROM ( SELECT adminid AS id, name, email, status, created_at, 'admin' AS role FROM admins
 UNION ALL SELECT supplier_id AS id, name, email, status, created_at, 'supplier' AS role FROM suppliers
 UNION ALL SELECT customer_id AS id, name, email, status, created_at, 'customer' AS role FROM customers
) AS users WHERE $where
ORDER BY CASE role WHEN 'admin' THEN 1 WHEN 'supplier' THEN 2 WHEN 'customer' THEN 3 END, name;";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$userresult = $stmt->get_result();

if ($userresult->num_rows > 0) {
    while ($userrow = $userresult->fetch_assoc()) {
        $id = $userrow['id'];
        $role = $userrow['role'];
        $status = strtolower($userrow['status'] ?: 'active');
        $statusClass = "status-$status";
        
        // The whole row is now clickable
        ?>
        <tr onclick="openUserPane(<?= $id ?>, '<?= $role ?>')" style="cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
            <td>
                <div style="font-weight: 500;"><?= htmlspecialchars($userrow['name']) ?></div>
            </td>
            <td><?= htmlspecialchars($userrow['email']) ?></td>
            <td><span class="card-chip badge-soft"><?= ucfirst($role) ?></span></td>
            <td>
                <span id="table-status-<?= $role ?>-<?= $id ?>" class="card-chip status-pill <?= $statusClass ?>">
                    <?= ucfirst($status) ?>
                </span>
            </td>
            <td><?= date("M d, Y", strtotime($userrow['created_at'])) ?></td>
        </tr>
        <?php
    }
} else {
    echo '<tr><td colspan="5" style="text-align:center;">No users found.</td></tr>';
}
?>