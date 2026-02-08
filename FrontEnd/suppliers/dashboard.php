<?php
include("partials/nav.php");
?>

<section class="welcome-section" style="margin-left: 45px;">
    <h1>Welcome back, <b><?= $supplierName; ?></b></h1>
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-val">$<?= number_format($totalRevenue, 2) ?></span>
            <span class="stat-label">Revenue</span>
        </div>
        <div class="stat-item">
            <span class="stat-val"><?= $totalOrder ?></span>
            <span class="stat-label">Total Order</span>
        </div>
    </div>
</section>

<div class="grid-layout">

    <div class="col-left">
        <div class="profile-card"
            style="background-image: url('../uploads/shops/<?= $supplierid ?>/<?= htmlspecialchars($row['logo']) ?>');">
            <div class="profile-overlay">
                <h3><?= $row['company_name'] ?></h3>
                <small>Dashboard Console</small>
            </div>
        </div>

        <div class="glass-panel" style="height: auto; max-height: 250px; overflow: auto;">
            <div style="padding: 15px; display: flex; flex-direction: column; flex-grow: 1; overflow: hidden;">
                <h4 style="margin-bottom: 10px; font-size: 1rem;">Recent Reviews <small>(7 Days)</small></h4>

                <div class="reviews-scroll" style="overflow-y: auto; flex-grow: 1; padding-right: 5px;">
                    <?php if (empty($recentReviews)): ?>
                        <p style="color: #999; font-size: 0.9rem; text-align: center;">No reviews this week.</p>
                    <?php else: ?>
                        <?php foreach ($recentReviews as $review): ?>
                            <div class="review-item"
                                onclick='openReviewModal(event, <?php echo htmlspecialchars(json_encode($review), ENT_QUOTES, 'UTF-8'); ?>)'
                                style="display: flex; gap: 10px; margin-bottom: 15px; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s;">

                                <img src="../assets/customer_profiles/<?= htmlspecialchars($review['image'] ?? 'default.png') ?>"
                                    onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($review['customer_name']) ?>&background=random'"
                                    style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">

                                <div style="width: 100%;">
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                        <h5 style="margin: 0; font-size: 0.9rem;">
                                            <?= htmlspecialchars($review['customer_name']) ?>
                                        </h5>
                                        <div class="stars" style="font-size: 0.8rem;">
                                            <?php for ($i = 1; $i <= 5; $i++)
                                                echo ($i <= $review['rating']) ? '<span class="star-gold">★</span>' : '<span style="color:#ddd">★</span>'; ?>
                                        </div>
                                    </div>
                                    <p
                                        style="margin: 4px 0 0 0; font-size: 0.8rem; color: #666; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= htmlspecialchars($review['review']) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-center">

        <?php
        if ($diff >= 0) {
            $difftext = $diff . ' Days';
        } else {
            $difftext = 'Overdue';
        }
        ?>

        <div class="charts-row">
            <div class="glass-panel">
                <div style="display:flex; justify-content:space-between;">
                    <h4>Inventory Control</h4>
                </div>
                <div class="chart-container"
                    style="position: relative; height: 160px; width: 100%; display:flex; justify-content:center;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <div class="glass-panel">
                <div style="display:flex; justify-content:space-between;">
                    <h4>Monthly Rent</h4>
                </div>
                <div class="rent-widget">
                    <div class="circle-progress"
                        style="background: conic-gradient(var(--primary) <?= $percent ?>%, #eee 0);">
                        <div class="inner-circle"><?= $difftext ?></div>
                    </div>
                    <small>Due:
                        <?= $contractRow['contract_end'] ? date('M d, Y', strtotime($contractRow['contract_end'])) : 'N/A' ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="glass-panel timeline">
            <div class="timeline-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h4>Revenue Analytics</h4>

                <form method="GET" action="dashboard.php" style="margin:0;">
                    <select name="year" onchange="this.form.submit()"
                        style="padding: 5px 10px; border-radius: 6px; border: 1px solid #ccc; background: #fff; font-size: 0.9rem; cursor: pointer;">
                        <?php foreach ($availableYears as $yr): ?>
                            <option value="<?= $yr ?>" <?= ($selectedYear == $yr) ? 'selected' : '' ?>>
                                <?= $yr ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <canvas id="performanceChart" style="width:100%; max-height:250px;"></canvas>
        </div>

    </div>

    <div class="col-right">

        <div class="top-stats">
            <div style="text-align:center;">
                <div class="big-num"><?= $productCount; ?></div>
                <small style="font-weight: bold;">Total Items</small>
            </div>
            <div style="text-align:center; color: #ea982a;">
                <div class="big-num"><?= $pendingOrders ?></div>
                <small style="font-weight: bold;">Pending</small>
            </div>
            <div style="text-align:center; color: #cb4444;">
                <div class="big-num"><?= $cancelledOrders ?></div>
                <small style="font-weight: bold;">Cancelled</small>
            </div>
        </div>

        <div class="dark-card">

            <div class="dark-header">
                <div>
                    <h4 style="color: white; margin:0;">Best Sellers</h4>
                    <small style="color: rgba(255,255,255,0.8);">Top Performing Items</small>
                </div>
                <div class="card-chip" style="background: rgba(255,255,255,0.2); color: white;">Top 5</div>
            </div>

            <ul class="menu-list">
                <?php $rank = 1;
                foreach ($bestsellers as $item) { ?>
                    <li style="border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 8px;">
                        <span
                            style="background: var(--primary); color: white; width: 20px; height: 20px; display: inline-flex; justify-content: center; align-items: center; border-radius: 50%; font-size: 0.7rem; margin-right: 8px;">#<?= $rank ?></span>
                        <b><?= htmlspecialchars($item['product_name']) ?></b>
                        <small
                            style="margin-left: auto; color: #666; font-weight: 600;">(<?= (int) $item['best_variant_sold'] ?>
                            sold)</small>
                    </li>
                    <?php $rank++;
                } ?>
            </ul>
        </div>

    </div>
</div>

<div id="reviewModal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-modal" onclick="closeReviewModal()">&times;</span>
        <div style="text-align: center; margin-bottom: 15px;">
            <img id="modalImg" src=""
                style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 2px solid #eee;">
            <h3 id="modalName" style="margin: 0; color: #333;"></h3>
            <div id="modalStars" style="font-size: 1.2rem; margin-top: 5px;"></div>
        </div>
        <h5 id="modalProduct" style="color: var(--primary); text-align: center; margin-bottom: 15px;"></h5>
        <div style="background: var(--surface-secondary); padding: 15px; border-radius: 8px; max-height: 200px; overflow-y: auto;">
    <p id="modalComment" style="color: var(--text-main); line-height: 1.6; margin: 0;"></p>
</div>
        <div style="text-align: right; margin-top: 10px;">
            <small id="modalDate" style="color: #999;"></small>
        </div>
    </div>
</div>

<script>
    // HELPER: Get CSS Variable Color
    function getThemeColor(variable) {
        return getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
    }

    // 1. PERFORMANCE CHART (Line Chart)
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const performanceData = <?= json_encode(array_values($monthlyRevenue ?? [])) ?>;
    const primaryColor = getThemeColor('--primary') || '#4f46e5';
    const textColor = getThemeColor('--text-main'); // Dynamic Text Color
    const gridColor = getThemeColor('--border-color'); // Dynamic Grid Color
    
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
    gradient.addColorStop(0, primaryColor + '88');
    gradient.addColorStop(1, primaryColor + '00');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                data: performanceData,
                borderColor: primaryColor,
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: primaryColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { 
                    ticks: { color: textColor }, 
                    grid: { color: gridColor },
                    title: { display: true, text: 'Month', color: textColor } 
                },
                y: { 
                    beginAtZero: true, 
                    ticks: { color: textColor }, 
                    grid: { color: gridColor },
                    title: { display: true, text: 'Revenue ($)', color: textColor } 
                }
            }
        }
    });

    // 2. INVENTORY CHART (Pie Chart)
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryLabels = <?= json_encode($categoryLabels) ?>;
    const categoryData = <?= json_encode($categoryData) ?>;

    // Generate colors dynamically
    const bgColors = [primaryColor, '#ea982a', '#cb4444', '#4caf50', '#673ab7', '#3f51b5'];

    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryData,
                backgroundColor: bgColors,
                borderWidth: 0, // Remove white border for clean look
                borderColor: getThemeColor('--bg-color') // Match background if border needed
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'right', 
                    labels: { 
                        boxWidth: 10, 
                        font: { size: 10 },
                        color: textColor // Dynamic Legend Text
                    } 
                }
            }
        }
    });

    // 3. REVIEW MODAL LOGIC (Kept same, but style handled by CSS now)
    const modal = document.getElementById('reviewModal');

    function openReviewModal(event, review) {
        event.stopPropagation();
        event.preventDefault();

        document.getElementById('modalName').textContent = review.customer_name;
        document.getElementById('modalProduct').textContent = review.product_name ? 'Purchased: ' + review.product_name : 'Verified Purchase';
        document.getElementById('modalComment').textContent = review.review;
        document.getElementById('modalDate').textContent = review.created_at;

        const img = document.getElementById('modalImg');
        const defaultImg = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(review.customer_name) + '&background=random';

        if (review.image) {
            img.src = '../assets/customer_profiles/' + review.image;
        } else {
            img.src = defaultImg;
        }
        img.onerror = function () { this.src = defaultImg; };

        let starsHtml = '';
        for (let i = 1; i <= 5; i++) {
            starsHtml += (i <= review.rating) ? '<span class="star-gold">★</span>' : '<span style="color:var(--border-color)">★</span>';
        }
        document.getElementById('modalStars').innerHTML = starsHtml;
        modal.style.display = 'flex';
    }

    function closeReviewModal() {
        modal.style.display = 'none';
    }

    window.onclick = function (event) {
        if (event.target == modal) {
            closeReviewModal();
        }
    }
</script>

</body>

</html>