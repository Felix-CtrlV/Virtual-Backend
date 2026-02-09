<?php
$pageTitle = 'Review Management';
$pageSubtitle = 'Monitor and moderate mall & tenant reviews.';
include("partials/nav.php");

$per_page = 20;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $per_page;

$filter_company = isset($_GET['company']) ? (int)$_GET['company'] : 0;
$filter_rating = isset($_GET['rating']) ? $_GET['rating'] : 'all';
$filter_date = isset($_GET['date']) ? $_GET['date'] : 'all';
$filter_search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build WHERE clauses
$where = ["1=1"];
$params = [];
$types = "";

if ($filter_company > 0) {
    $where[] = "r.company_id = ?";
    $params[] = $filter_company;
    $types .= "i";
}

if ($filter_rating === 'poor') {
    $where[] = "r.rating < 3";
} elseif ($filter_rating === 'average') {
    $where[] = "r.rating BETWEEN 3 AND 4";
} elseif ($filter_rating === 'excellent') {
    $where[] = "r.rating = 5";
}

if ($filter_date === '7d') {
    $where[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filter_date === '30d') {
    $where[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($filter_date === 'month') {
    $where[] = "r.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
}

if ($filter_search !== '') {
    $where[] = "(cu.name LIKE ? OR r.review LIKE ?)";
    $searchTerm = '%' . $filter_search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$where_sql = implode(" AND ", $where);

$order_sql = "r.created_at DESC";
if ($filter_sort === 'oldest') $order_sql = "r.created_at ASC";
elseif ($filter_sort === 'lowest') $order_sql = "r.rating ASC, r.created_at DESC";
elseif ($filter_sort === 'highest') $order_sql = "r.rating DESC, r.created_at DESC";

// Summary stats (with same filters except pagination)
$stats_sql = "SELECT 
    COUNT(*) AS total,
    ROUND(AVG(r.rating), 1) AS avg_rating,
    SUM(CASE WHEN r.rating < 3 THEN 1 ELSE 0 END) AS poor_count
FROM reviews r
LEFT JOIN companies c ON r.company_id = c.company_id
LEFT JOIN customers cu ON r.customer_id = cu.customer_id
WHERE $where_sql";
$stats = ['total' => 0, 'avg_rating' => '—', 'poor_count' => 0];
$stats_stmt = $conn->prepare($stats_sql);
if ($stats_stmt) {
    if (!empty($params)) {
        $stats_stmt->bind_param($types, ...$params);
    }
    $stats_stmt->execute();
    $sr = $stats_stmt->get_result();
    if ($sr) $stats = $sr->fetch_assoc() ?: $stats;
    $stats_stmt->close();
}

$total_reviews = (int)($stats['total'] ?? 0);
$avg_rating = $stats['avg_rating'] ?? '—';
$poor_count = (int)($stats['poor_count'] ?? 0);
$total_pages = max(1, ceil($total_reviews / $per_page));

// Companies for dropdown
$companies_sql = "SELECT company_id, company_name FROM companies WHERE status = 'active' ORDER BY company_name";
$companies_res = mysqli_query($conn, $companies_sql);
$companies = [];
while ($c = mysqli_fetch_assoc($companies_res)) {
    $companies[] = $c;
}

// Main query with pagination
$params_limit = $params;
$params_limit[] = $offset;
$params_limit[] = $per_page;
$types_limit = $types . "ii";

$main_sql = "SELECT r.review_id, r.company_id, c.company_name, cu.name AS customer_name, r.review, r.rating, r.created_at
FROM reviews r
LEFT JOIN companies c ON r.company_id = c.company_id
LEFT JOIN customers cu ON r.customer_id = cu.customer_id
WHERE $where_sql
ORDER BY $order_sql
LIMIT ?, ?";

$reviews = [];
$main_stmt = $conn->prepare($main_sql);
if ($main_stmt) {
    if (!empty($params)) {
        $main_stmt->bind_param($types_limit, ...$params_limit);
    } else {
        $main_stmt->bind_param("ii", $offset, $per_page);
    }
    $main_stmt->execute();
    $rev_res = $main_stmt->get_result();
    $reviews = $rev_res ? $rev_res->fetch_all(MYSQLI_ASSOC) : [];
    $main_stmt->close();
}

$query_string = http_build_query(array_filter([
    'company' => $filter_company ?: null,
    'rating' => $filter_rating !== 'all' ? $filter_rating : null,
    'date' => $filter_date !== 'all' ? $filter_date : null,
    'q' => $filter_search ?: null,
    'sort' => $filter_sort !== 'newest' ? $filter_sort : null,
]));
$base_url = 'reviews.php' . ($query_string ? '?' . $query_string . '&' : '?');
?>

<section class="section active">
    <div class="reviews-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px;">
            <div style="font-size: 12px; color: var(--muted); margin-bottom: 4px;">Total reviews</div>
            <div style="font-size: 1.5rem; font-weight: 700;"><?= number_format($total_reviews) ?></div>
        </div>
        <div class="card" style="padding: 16px;">
            <div style="font-size: 12px; color: var(--muted); margin-bottom: 4px;">Average rating</div>
            <div style="font-size: 1.5rem; font-weight: 700;"><?= htmlspecialchars($avg_rating) ?><span style="color: #eab308;">★</span></div>
        </div>
        <div class="card" style="padding: 16px;">
            <div style="font-size: 12px; color: var(--muted); margin-bottom: 4px;">Needs attention (1–2★)</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger);"><?= number_format($poor_count) ?></div>
        </div>
    </div>

    <form method="get" action="reviews.php" class="reviews-filter-form" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 20px; padding: 16px; background: rgba(15,23,42,0.5); border-radius: 12px; border: 1px solid var(--border);">
        <input type="hidden" name="p" value="1">
        <input type="text" name="q" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Search reviewer or review..." style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text); font-size: 13px; min-width: 200px;">
        <select name="company" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text); font-size: 13px;">
            <option value="0">All companies</option>
            <?php foreach ($companies as $co): ?>
                <option value="<?= (int)$co['company_id'] ?>" <?= $filter_company === (int)$co['company_id'] ? 'selected' : '' ?>><?= htmlspecialchars($co['company_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="rating" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text); font-size: 13px;">
            <option value="all" <?= $filter_rating === 'all' ? 'selected' : '' ?>>All ratings</option>
            <option value="poor" <?= $filter_rating === 'poor' ? 'selected' : '' ?>>Poor (1–2★)</option>
            <option value="average" <?= $filter_rating === 'average' ? 'selected' : '' ?>>Average (3–4★)</option>
            <option value="excellent" <?= $filter_rating === 'excellent' ? 'selected' : '' ?>>Excellent (5★)</option>
        </select>
        <select name="date" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text); font-size: 13px;">
            <option value="all" <?= $filter_date === 'all' ? 'selected' : '' ?>>All time</option>
            <option value="7d" <?= $filter_date === '7d' ? 'selected' : '' ?>>Last 7 days</option>
            <option value="30d" <?= $filter_date === '30d' ? 'selected' : '' ?>>Last 30 days</option>
            <option value="month" <?= $filter_date === 'month' ? 'selected' : '' ?>>This month</option>
        </select>
        <select name="sort" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text); font-size: 13px;">
            <option value="newest" <?= $filter_sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
            <option value="oldest" <?= $filter_sort === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
            <option value="lowest" <?= $filter_sort === 'lowest' ? 'selected' : '' ?>>Lowest rating</option>
            <option value="highest" <?= $filter_sort === 'highest' ? 'selected' : '' ?>>Highest rating</option>
        </select>
        <button type="submit" class="btn-primary btn">Apply</button>
        <?php if ($filter_search || $filter_company || $filter_rating !== 'all' || $filter_date !== 'all'): ?>
            <a href="reviews.php" class="btn-ghost btn">Clear</a>
        <?php endif; ?>
    </form>

    <div class="card">
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Reviewer</th>
                        <th>Company</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--muted);">
                                No reviews match your filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $row): ?>
                            <?php
                            $status_class = $row['rating'] < 3 ? 'status-banned' : ($row['rating'] == 5 ? 'status-active' : 'status-pending');
                            $status_text = $row['rating'] < 3 ? 'Poor' : ($row['rating'] == 5 ? 'Excellent' : 'Average');
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['customer_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['company_name'] ?? '—') ?></td>
                                <td>
                                    <span class="card-chip status-pill <?= $status_class ?>"><?= $row['rating'] ?>★</span>
                                </td>
                                <td style="max-width: 280px;"><?= htmlspecialchars($row['review'] ?? '') ?></td>
                                <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="reviews-pagination" style="text-align: left; justify-content: center; align-items: center; gap: 8px; padding: 16px; border-top: 1px solid var(--border);">
                <?php if ($page > 1): ?>
                    <a href="<?= $base_url ?>p=<?= $page - 1 ?>" class="btn-ghost btn" style="text-align: center;">&larr; Prev</a>
                <?php endif; ?>
                <span style="font-size: 13px; color: var(--muted);">Page <?= $page ?> of <?= $total_pages ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="<?= $base_url ?>p=<?= $page + 1 ?>" class="btn-ghost btn" style="text-align: center;">Next &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script src="script.js"></script>
<script>
document.querySelector('.reviews-filter-form').addEventListener('change', function() {
    this.querySelector('input[name="p"]').value = '1';
});
</script>
</body>
</html>
