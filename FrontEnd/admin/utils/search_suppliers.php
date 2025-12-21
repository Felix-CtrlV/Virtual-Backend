<?php
include("../../../BackEnd/config/dbconfig.php");
$adminid = $_SESSION["adminid"] ?? null;

$search = $_POST['search'] ?? '';

$sql = "
SELECT s.*, sa.logo, sa.banner,
    (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE supplier_id = s.supplier_id) AS avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE supplier_id = s.supplier_id) AS review_count
FROM suppliers s
LEFT JOIN shop_assets sa ON sa.supplier_id = s.supplier_id
WHERE (
    s.company_name LIKE ?
    OR s.name LIKE ?
    OR s.email LIKE ?
    OR s.status LIKE ?
)
ORDER BY s.created_at DESC
";

$stmt = $conn->prepare($sql);
$like = "$search%";
$stmt->bind_param("ssss", $like, $like, $like, $like);
$stmt->execute();
$supplierresult = $stmt->get_result();

if ($supplierresult->num_rows > 0) {
    while ($supplierrow = $supplierresult->fetch_assoc()) {

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
                <div style="display:flex;align-items:center;gap:10px;">
                    <?php if (!empty($supplierrow['logo'])): ?>
                        <img src="../uploads/shops/<?= $supplierrow['supplier_id'] ?>/<?= $supplierrow['logo'] ?>"
                            style="width:32px;height:32px;border-radius:6px;object-fit:cover;">
                    <?php else: ?>
                        <div
                            style="width:32px;height:32px;border-radius:6px;background:var(--accent-soft);
                                    display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;">
                            <?= strtoupper(substr($supplierrow['company_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:500;">
                            <?= htmlspecialchars($supplierrow['company_name']) ?>
                        </div>
                        <div style="font-size:11px;color:var(--muted);">
                            <?= htmlspecialchars($supplierrow['name']) ?>
                        </div>
                    </div>
                </div>
            </td>

            <td>
                <div style="font-size:12px;">
                    <div><?= htmlspecialchars($supplierrow['email']) ?></div>
                    <div style="color:var(--muted);margin-top:2px;">
                        <?= htmlspecialchars($supplierrow['phone']) ?>
                    </div>
                </div>
            </td>

            <td>
                <?php if ($supplierrow['review_count'] > 0): ?>
                    <div style="display:flex;align-items:center;gap:4px;">
                        <span><?= $supplierrow['avg_rating'] ?></span>
                        <span style="color:#eab308;">★</span>
                        <span style="font-size:11px;color:var(--muted);">
                            (<?= $supplierrow['review_count'] ?>)
                        </span>
                    </div>
                <?php else: ?>
                    <span style="color:var(--muted);font-size:11px;">No reviews</span>
                <?php endif; ?>
            </td>

            <td>
                <span class="card-chip status-pill <?= $statusClass ?>">
                    <?= ucfirst($status) ?>
                </span>
            </td>

            <td><?= date("M d, Y", strtotime($supplierrow['created_at'])) ?></td>

            <td>
                <a href="suppliersmanagement.php?adminid=<?= $_SESSION['adminid'] ?>&supplierid=<?= $supplierrow['supplier_id'] ?>"
                    class="btn btn-ghost" style="padding:5px 12px;font-size:11px;">
                    View
                </a>
            </td>
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
