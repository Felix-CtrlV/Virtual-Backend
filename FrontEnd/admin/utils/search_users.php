<?php

include("../../../BackEnd/config/dbconfig.php");

$search = $_POST['search'] ?? '';

$sql = "SELECT *
FROM ( SELECT adminid AS id, name, email, status, created_at, 'admin' AS role FROM admins
 UNION ALL SELECT supplier_id AS id, name, email, status, created_at, 'supplier' AS role FROM suppliers
 UNION ALL SELECT customer_id AS id, name, email, status, created_at, 'customer' AS role FROM customers
) AS users WHERE (name LIKE ? OR email LIKE ? OR status LIKE ? OR role LIKE ?)
ORDER BY CASE role WHEN 'admin' THEN 1  WHEN 'supplier' THEN 2 WHEN 'customer' THEN 3 END, CASE status
WHEN 'active' THEN 1 WHEN 'inactive' THEN 2 ELSE 3 END, name;";
$stmt = $conn->prepare($sql);
$like = "$search%";
$stmt->bind_param("ssss", $like, $like, $like, $like);
$stmt->execute();
$userresult = $stmt->get_result();
if ($userresult->num_rows > 0) {
    while ($userrow = $userresult->fetch_assoc()) {
        $status = $userrow['status'];
        $statusClass = match ($status) {
            'active' => 'status-active',
            'inactive' => 'status-inactive',
            'banned' => 'status-banned',
            default => 'status-inactive'
        };
        ?>
        <tr>
            <td><?php echo htmlspecialchars($userrow['name']); ?></td>
            <td><?php echo htmlspecialchars($userrow['email']); ?></td>
            <td><span class="card-chip badge-soft"><?php echo htmlspecialchars($userrow['role']); ?></span></td>
            <td>
                <span class="card-chip status-pill <?= $statusClass ?>">
                    <?= ucfirst(htmlspecialchars($status)) ?>
                </span>
            </td>
            <td><?= date("M d, Y", strtotime($userrow['created_at'])) ?></td>
        </tr>
        <?php
    }
} else {
    echo "
    <tr>
        <td colspan='6' style='text-align:center;padding:30px;color:var(--muted);'>
            No suppliers found.
        </td>
    </tr>";
}

?>