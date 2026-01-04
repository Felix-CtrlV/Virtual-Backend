<?php
include("partials/nav.php");
?>

<section class="welcome-section">
    <h1>Welcome back, <b><?= $supplierName; ?></b></h1>
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-val">$<?= $totalRevenue ?></span>
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

        <div class="glass-panel" style="height: 310px;">
            <div style="display: flex; justify-content: space-between;">
                <h4 style="margin-bottom:15px">Best Sellers</h4>
                <div class="card-chip">Top-5</div>
            </div>
            <ul class="menu-list">
                <?php foreach ($bestsellers as $item) { ?>
            <li> <?= htmlspecialchars($item['product_name']) ?> <span>&rsaquo;</span></li>
                <?php } ?>
            </ul>

        </div>
    </div>

    <div class="col-center">

        <?php
        if ($diff >= 0) {
            $difftext = $diff . 'Days';
        } else {
            $difftext = 'Overdue';
        }
        ?>

        <div class="charts-row">
            <div class="glass-panel">
                <div style="display:flex; justify-content:space-between;">
                    <h4>Inventory Control</h4>
                </div>
                <h2 style="margin-top:5px; font-weight:300;">4.2k <small
                        style="font-size:0.8rem; color:#888;">units</small></h2>

                <div class="chart-container">
                    <?php foreach ($inventory as $item): ?>
                        <div class="bar-group">
                            <div class="bar <?php echo ($item['level'] < 35) ? 'low' : ''; ?>"
                                style="height: <?php echo $item['level']; ?>%;"></div>
                            <span class="bar-label"><?php echo $item['day']; ?></span>
                        </div>
                    <?php endforeach; ?>
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
            <div class="timeline-header">
                <h4>2024</h4>
                <span>...</span>
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

        <div class="glass-panel" style="margin-bottom: 20px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <h4>Performance</h4>
                <h4>18%</h4>
            </div>
            <div style="background:#ddd; height:8px; border-radius:4px; overflow:hidden;">
                <div style="width:18%; background:var(--primary); height:100%;"></div>
            </div>
        </div>

        <div class="dark-card">
            <div class="dark-header">
                <div>
                    <h4>Approve Orders</h4>
                    <small>2/8 Pending</small>
                </div>
                <small>See All</small>
            </div>

            <ul class="task-list">
                <?php foreach ($orders as $order): ?>
                    <li class="task-item">
                        <div class="task-icon">#</div>
                        <div class="task-info">
                            <h4><?php echo $order['item']; ?></h4>
                            <p><?php echo $order['time']; ?> • <?php echo $order['status']; ?></p>
                        </div>
                        <div class="approve-btn <?php echo ($order['status'] == 'Approved') ? 'checked' : ''; ?>"></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>
</div>
</div>
</body>

<script>
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const performanceData = [1200, 1500, 1300, 1600, 1800, 1700, 1900, 2000, 2100, 2200, 2400, 2500];

    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
    const secondaryColor = getComputedStyle(document.documentElement).getPropertyValue('--secondary').trim();

    const ctx = document.getElementById('performanceChart').getContext('2d');

    const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
    gradient.addColorStop(0, primaryColor + '88');
    gradient.addColorStop(1, primaryColor + '00');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Revenue ($)',
                data: performanceData,
                borderColor: primaryColor,
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointRadius: 2,
                pointBackgroundColor: primaryColor,
                pointHoverRadius: 4,
                pointHoverBackgroundColor: secondaryColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                    position: 'top',
                    labels: { color: primaryColor, font: { size: 14 } }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: 'white',
                    bodyColor: 'white'
                }
            },
            scales: {
                x: {
                    display: true,
                    ticks: {
                        display: true,
                        color: '#000000',
                        font: { size: 12 }
                    },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    title: { display: true, text: 'Month', color: '#000000', font: { size: 14 } }
                },
                y: {
                    display: true,
                    ticks: {
                        display: true,
                        color: '#000000',
                        font: { size: 12 }
                    },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    title: { display: true, text: 'Revenue ($)', color: '#000000', font: { size: 14 } },
                    beginAtZero: true
                }
            }
        }
    });
</script>


</html>