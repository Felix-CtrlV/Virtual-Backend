<?php
$pageTitle = 'User Management';
$pageSubtitle = 'View and manage shopper accounts across the mall.';
include("partials/nav.php");
?>

<section class="section active">

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = "SELECT adminid AS id, name, email, status, created_at, 'admin' AS role FROM admins
                UNION ALL
                SELECT supplier_id AS id, name, email, status, created_at, 'supplier' AS role FROM suppliers
                UNION ALL
                SELECT customer_id AS id, name, email, status, created_at, 'customer' AS role FROM customers
                ORDER BY 
                CASE role
                    WHEN 'admin' THEN 1
                    WHEN 'supplier' THEN 2
                    WHEN 'customer' THEN 3
                END, CASE status
                    WHEN 'active' THEN 1
                    WHEN 'inactive' THEN 2
                END,name;";
                $result = mysqli_query($conn, $users);
                while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><span class="card-chip badge-soft"><?php echo htmlspecialchars($row['role']); ?></span></td>
                        <td>
                            <?php
                            $status = $row['status'];

                            $statusClass = match ($status) {
                                'active' => 'status-active',
                                'inactive' => 'status-inactive',
                                'banned' => 'status-banned',
                                default => 'status-inactive'
                            };
                            ?>

                            <span class="card-chip status-pill <?= $statusClass ?>">
                                <?= ucfirst(htmlspecialchars($status)) ?>
                            </span>

                        </td>
                        <td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<script src="script.js"></script>
</body>

</html>