<?php
$pageTitle = 'View Suppliers';
$pageSubtitle = 'Browse and manage all suppliers in the mall.';
include("partials/nav.php");
?>

<?php
$supplierquery = "SELECT s.*, sa.logo, sa.banner,
    (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE supplier_id = s.supplier_id) as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE supplier_id = s.supplier_id) as review_count
    FROM suppliers s 
    LEFT JOIN shop_assets sa ON sa.supplier_id = s.supplier_id 
    ORDER BY s.created_at DESC";
$supplierresult = mysqli_query($conn, $supplierquery);
?>

<section class="section active">
    <div class="card">
        <div class="section-header" style="margin-bottom: 12px;">
            <div>
                <p>Showing <?= mysqli_num_rows($supplierresult) ?> supplier(s)</p>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Contact</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($supplierresult) > 0) {
                    while ($supplierrow = mysqli_fetch_assoc($supplierresult)) {
                        $status = $supplierrow['status'];
                        $statusClass = match ($status) {
                            'active' => 'status-active',
                            'inactive' => 'status-inactive',
                            'banned' => 'status-banned',
                            default => 'status-inactive'
                        };
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if (!empty($supplierrow['logo'])): ?>
                                        <img src="../uploads/shops/<?= $supplierrow['supplier_id'] ?>/<?= $supplierrow['logo'] ?>"
                                            alt="Logo" style="width: 32px; height: 32px; border-radius: 6px; object-fit: cover;">
                                    <?php else: ?>
                                        <div
                                            style="width: 32px; height: 32px; border-radius: 6px; background: var(--accent-soft); 
                                            display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">
                                            <?= strtoupper(substr($supplierrow['company_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($supplierrow['company_name']) ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--muted);">
                                            <?= htmlspecialchars($supplierrow['name']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 12px;">
                                    <div><?= htmlspecialchars($supplierrow['email']) ?></div>
                                    <div style="color: var(--muted); margin-top: 2px;">
                                        <?= htmlspecialchars($supplierrow['phone']) ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($supplierrow['review_count'] > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <span style="font-weight: 500;"><?= $supplierrow['avg_rating'] ?></span>
                                        <span style="color: #eab308;">★</span>
                                        <span style="font-size: 11px; color: var(--muted);">
                                            (<?= $supplierrow['review_count'] ?>)
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--muted); font-size: 11px;">No reviews</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="card-chip status-pill <?= $statusClass ?>">
                                    <?= ucfirst(htmlspecialchars($status)) ?>
                                </span>
                            </td>
                            <td><?= date("M d, Y", strtotime($supplierrow['created_at'])) ?></td>
                            <td>
                                <a href="suppliersmanagement.php?adminid=<?= urlencode($adminid) ?>&supplierid=<?= $supplierrow['supplier_id'] ?>"
                                    class="btn btn-primary" style="padding: 5px 12px; font-size: 11px;">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: var(--muted);">
                            No suppliers found.
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<script src="script.js"></script>
</body>

</html>